<?php

/**
 * Nintendo Switch and Switch 2, through the play history the Nintendo Store app
 * shows (undocumented; Nintendo can change it): every game played, total
 * minutes, days played, first and last played. Was api/nintendo-exporter +
 * tools/nintendo-sync.mjs.
 *
 * It signs in as the Nintendo Store app does: Settings → Connections opens the
 * Nintendo Account's sign-in, and the link on its "Select this person" button is
 * pasted back. That gives a session token, which lasts about two years.
 *
 * A game is tied to the one Switch or Switch 2 copy with the same name (or what
 * it is listed as), else a name that starts with it; of several, the ones on the
 * console it was played on. A game with no copy at all was bought on the eShop,
 * so it gets a new digital one. Apps and what comes with Nintendo Switch Online
 * are never added.
 */
final class NintendoSync extends PlatformSync
{
    private const CLIENT_ID = '5c38e31cd085304b';
    private const REDIRECT_URI = 'npf' . self::CLIENT_ID . '://auth';
    private const SCOPE = 'openid user user.mii user.email user.links[].id';
    private const ACCOUNTS = 'https://accounts.nintendo.com/connect/1.0.0';
    private const API = 'https://app-api.znej.nintendo.com/api/v2.0/users/me/play_histories';
    private const LOCALE = 'en-GB';
    private const CONSOLES = ['switch', 'switch2'];
    // apps, and games that come with Nintendo Switch Online rather than being bought: no copy for them
    private const NOT_OWNED = '/^(YouTube|Netflix|Disney\+|Spotify|Twitch|Pokémon HOME|TETRIS 99|PAC-MAN 99|SUPER MARIO BROS\. 35|F-ZERO 99|.* – Nintendo Classics|.* Nintendo Switch Online)$/iu';

    public function connected(): bool
    {
        return credential('nintendo', 'session_token') !== null;
    }

    /** Settings → Connections, step 1: the sign-in page. $verifier and $state are kept in the admin's session. */
    public static function signInUrl(string $verifier, string $state): string
    {
        return self::ACCOUNTS . '/authorize?' . http_build_query([
            'state' => $state, 'redirect_uri' => self::REDIRECT_URI, 'client_id' => self::CLIENT_ID, 'scope' => self::SCOPE,
            'response_type' => 'session_token_code',
            'session_token_code_challenge' => b64url(hash('sha256', $verifier, true)),
            'session_token_code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** Step 2: the link copied from the "Select this person" button. */
    public static function connect(string $link, string $verifier, string $state): ?string
    {
        $link = trim($link);
        if (!str_starts_with($link, self::REDIRECT_URI . '#')) {
            throw new RuntimeException('That isn\'t the link: it starts with ' . self::REDIRECT_URI . '# (the button\'s link, not the address bar).');
        }
        parse_str(substr($link, strpos($link, '#') + 1), $frag);
        if (($frag['state'] ?? null) !== $state) {
            throw new RuntimeException('That link is from another sign-in: open the sign-in link again.');
        }
        $r = Http::request('POST', self::ACCOUNTS . '/api/session_token', ['Content-Type: application/x-www-form-urlencoded'], http_build_query([
            'client_id' => self::CLIENT_ID, 'session_token_code' => $frag['session_token_code'] ?? '', 'session_token_code_verifier' => $verifier,
        ]), ['retries' => 0]);
        $token = $r->json()['session_token'] ?? null;
        if ($r->status !== 200 || !$token) {
            throw new RuntimeException('Sign-in failed (HTTP ' . $r->status . '). The link works once, for a few minutes: sign in again.');
        }
        set_credential('nintendo', 'session_token', $token);
        self::accessToken();

        return null;
    }

    private static function accessToken(): string
    {
        $r = Http::request('POST', self::ACCOUNTS . '/api/token', ['Content-Type: application/json; charset=utf-8'], json_encode([
            'client_id' => self::CLIENT_ID, 'session_token' => credential('nintendo', 'session_token'),
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer-session-token',
        ]), ['retries' => 1]);
        $access = $r->json()['access_token'] ?? null;
        if ($r->status !== 200 || !$access) {
            throw new AuthError('Nintendo refused the sign-in (HTTP ' . $r->status . '). It may have run out: connect it again.');
        }

        return $access;
    }

    public function fetch(SyncRun $run): array
    {
        $r = Http::get(self::API, ['Authorization: Bearer ' . self::accessToken(), 'Accept: application/json', 'gentry-locale: ' . self::LOCALE]);
        if ($r->status === 401) {
            throw new AuthError('Nintendo refused the sign-in (HTTP 401). Connect it again.');
        }
        $data = $r->json();
        if (!$r->ok() || !is_array($data)) {
            throw new RuntimeException('Nintendo play history: HTTP ' . $r->status . ' ' . $r->excerpt());
        }
        $run->log(count($data['playHistories'] ?? []) . ' games played.');

        return ['exported_at_utc' => gmdate('c'), 'locale' => self::LOCALE, 'data' => $data];
    }

    private static function clean(?string $s): string
    {
        return clean_name(preg_replace('/\s*[–—-]?\s*Nintendo Switch(?: 2)? Edition\b/iu', '', (string) $s));
    }

    /** As tools/nintendo-sync.mjs's fromExport(). */
    public function normalize(array $raw, SyncRun $run): array
    {
        $titles = [];
        foreach ($raw['data']['playHistories'] ?? [] as $h) {
            $id = $h['titleId'] ?? $h['applicationId'] ?? null;
            if (!$id) {
                continue;
            }
            $titles[$id] = [
                'id' => $id,
                'name' => self::clean($h['titleName'] ?? $h['name'] ?? ''),
                // the console it was played on: "Nintendo Switch 2" (or its code, BEE) or the Switch (HAC)
                'console' => preg_match('/switch\s*2|switch_?2|^bee$|ounce/i', (string) ($h['platform'] ?? $h['deviceType'] ?? '')) ? 'switch2' : 'switch',
                'playtime' => $h['totalPlayedMinutes'] ?? null,
                'playDays' => $h['totalPlayedDays'] ?? null,
                'firstPlayed' => iso_date($h['firstPlayedAt'] ?? null),
                'lastPlayed' => iso_date($h['lastPlayedAt'] ?? null),
                'image' => $h['imageUrl'] ?? null,
            ];
        }

        return [
            'account' => ['synced' => $raw['exported_at_utc'], 'source' => 'nintendo'],
            'collections' => ['titles' => $titles],
        ];
    }

    public function merge(array $data, SyncRun $run): void
    {
        $titles = $data['collections']['titles'];
        $tied = [];
        foreach ($run->games as $g) {
            foreach (game_links($g, 'nintendoTitleId') as $id) {
                $tied[$id] = true;
            }
        }

        foreach ($titles as $t) {
            if (isset($tied[$t['id']])) {
                continue;
            }
            $n = norm_name($t['name']);
            $twins = array_filter($titles, fn ($o) => $o['id'] !== $t['id'] && !isset($tied[$o['id']]) && norm_name($o['name']) === $n);
            $cs = $this->candidates($run, $t);

            if (count($cs) === 1 && !$twins) {
                $run->link(array_key_first($cs), 'nintendoTitleId', $t['id'], $t['name']);
                $tied[$t['id']] = true;
            } elseif ($cs) {
                $run->report('ambiguous', 'titles', $t['id'], $t['name'], ids_of($cs), $twins ? 'Another entry has the same name.' : null);
            } elseif (preg_match(self::NOT_OWNED, $t['name'])) {
                continue; // an app, or part of Switch Online
            } else {
                // bought or downloaded on the eShop: a new digital copy, with only what the play history says
                $run->add([
                    'id' => $t['console'] . '-' . slugify($t['name']), 'console' => $t['console'], 'title' => $t['name'], 'region' => 'PAL',
                    'released' => null, 'dateSource' => 'manual', 'publisher' => null, 'developer' => null, 'genre' => null,
                    'format' => 'digital', 'nintendoTitleId' => $t['id'], 'cover' => null, 'colors' => null,
                ], '-digital', $t['name']);
                $tied[$t['id']] = true;
            }
        }

        foreach ($run->games as $id => $g) {
            foreach (game_links($g, 'nintendoTitleId') as $extId) {
                if (!isset($titles[$extId])) {
                    $run->change('missing', $id, 'nintendoTitleId', null, $extId, $g['title'], 'Not in the play history any more; the copy stays tied.');
                }
            }
        }
    }

    /** A Switch or Switch 2 copy not tied yet, the same name, else a name that starts with it; of several, the ones on its console. */
    private function candidates(SyncRun $run, array $t): array
    {
        $free = array_filter($run->games, fn ($g) => in_array($g['console'], self::CONSOLES, true) && !game_links($g, 'nintendoTitleId'));
        $n = norm_name($t['name']);
        $same = array_filter($free, fn ($g) => in_array($n, names_of($g, 'norm_name'), true));
        // (but a name followed by a number is another game: Just Dance isn't Just Dance 2022)
        $cs = $same ?: array_filter($free, function ($g) use ($n) {
            foreach (names_of($g, 'norm_name') as $m) {
                if (str_starts_with($m, $n . ' ') && !preg_match('/^\d|^(ii|iii|iv|v|vi)\b/', substr($m, strlen($n) + 1))) {
                    return true;
                }
            }
            return false;
        });
        $here = array_filter($cs, fn ($g) => $g['console'] === $t['console']);

        return $here ?: $cs;
    }
}
