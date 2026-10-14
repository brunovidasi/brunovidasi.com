<?php

/**
 * PlayStation Network, through the services the PlayStation app uses (they are
 * undocumented, and Sony can change them): the PS4 and PS5 play history, and
 * the trophies of every PS3, PS4 and PS5 game. Was api/psn-exporter (the
 * tustin/psn-php library) + tools/psn-sync.mjs; it now talks to PSN itself, so
 * nothing has to be installed on the server.
 *
 * Connecting takes an NPSSO token once (Settings → Connections), traded at once
 * for a refresh token, which is what is kept. It lasts about two months, and is
 * renewed by every sync.
 *
 * The PlayStation games are discs on the shelf, so the sync never adds a copy:
 * it ties one with psnTitleId (CUSA…, PPSA…: its play history) and psnTrophySet
 * (NPWR…: its trophies), to the one copy on its console with the same name, or
 * a name that starts with it. None, or several: it is left to tie by hand.
 */
final class PsnSync extends PlatformSync
{
    private const AUTH = 'https://ca.account.sony.com/api/authz/v3/oauth';
    private const API = 'https://m.np.playstation.com/api';
    // the PlayStation app's own client
    private const CLIENT_ID = '09515159-7237-4370-9b40-3806e67c0891';
    private const CLIENT_SECRET = 'ucPjka5tntB2KqsP';
    private const REDIRECT = 'com.scee.psxandroid.scecompcall://redirect';
    private const SCOPE = 'psn:mobile.v2.core psn:clientapp';
    // apps that aren't games: they stay out
    private const NOT_A_GAME = '/media_app|nongame|videoservice|web_app/';

    private ?string $access = null;

    public function connected(): bool
    {
        return credential('psn', 'refresh_token') !== null;
    }

    /** Settings → Connections: an NPSSO token for a refresh token. Returns the account's name. */
    public static function connect(string $npsso): ?string
    {
        $npsso = trim($npsso);
        // pasted as the whole {"npsso":"…"} the page shows
        if (str_starts_with($npsso, '{')) {
            $npsso = (string) (json_decode($npsso, true)['npsso'] ?? '');
        }
        if (!preg_match('/^[A-Za-z0-9]{40,}$/', $npsso)) {
            throw new RuntimeException('That isn\'t an NPSSO token: it is 64 letters and digits.');
        }

        $r = Http::get(self::AUTH . '/authorize?' . http_build_query([
            'access_type' => 'offline', 'client_id' => self::CLIENT_ID, 'redirect_uri' => self::REDIRECT,
            'response_type' => 'code', 'scope' => self::SCOPE,
        ]), ['Cookie: npsso=' . $npsso], ['follow' => false, 'retries' => 0]);
        parse_str((string) parse_url($r->headers['location'] ?? '', PHP_URL_QUERY), $q);
        if (empty($q['code'])) {
            throw new RuntimeException('PSN didn\'t accept that NPSSO token (it may have run out: get a new one).');
        }

        $tokens = self::token(['code' => $q['code'], 'redirect_uri' => self::REDIRECT, 'grant_type' => 'authorization_code', 'token_format' => 'jwt']);
        set_credential('psn', 'refresh_token', $tokens['refresh_token']);
        set_credential('psn', 'refresh_expires', (string) (time() + (int) ($tokens['refresh_token_expires_in'] ?? 5184000)));

        return self::onlineId($tokens['access_token']);
    }

    private static function token(array $params): array
    {
        $r = Http::request('POST', self::AUTH . '/token', [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode(self::CLIENT_ID . ':' . self::CLIENT_SECRET),
        ], http_build_query($params), ['retries' => 1]);
        $data = $r->json();
        if (!$r->ok() || empty($data['access_token'])) {
            throw new AuthError('PSN sign-in failed (HTTP ' . $r->status . ' ' . $r->excerpt(120) . '). Connect it again with a new NPSSO token.');
        }

        return $data;
    }

    private static function onlineId(string $access): ?string
    {
        try {
            $r = Http::get('https://us-prof.np.community.playstation.net/userProfile/v1/users/me/profile2?fields=onlineId', ['Authorization: Bearer ' . $access], ['retries' => 0]);
            return $r->json()['profile']['onlineId'] ?? null;
        } catch (Throwable) {
            return null;
        }
    }

    private function accessToken(): string
    {
        if ($this->access === null) {
            $tokens = self::token([
                'refresh_token' => credential('psn', 'refresh_token'), 'grant_type' => 'refresh_token',
                'token_format' => 'jwt', 'scope' => self::SCOPE,
            ]);
            // PSN may hand out a new refresh token: kept at once, or the next sync couldn't sign in
            if (!empty($tokens['refresh_token'])) {
                set_credential('psn', 'refresh_token', $tokens['refresh_token']);
                if (!empty($tokens['refresh_token_expires_in'])) {
                    set_credential('psn', 'refresh_expires', (string) (time() + (int) $tokens['refresh_token_expires_in']));
                }
            }
            $this->access = $tokens['access_token'];
        }

        return $this->access;
    }

    private function get(string $path, array $query = []): array
    {
        $r = Http::get(self::API . $path . ($query ? '?' . http_build_query($query) : ''), [
            'Authorization: Bearer ' . $this->accessToken(), 'Accept: application/json', 'Accept-Language: en-GB',
        ]);
        if ($r->status === 401) {
            throw new AuthError('PSN refused the sign-in (HTTP 401). Connect it again with a new NPSSO token.');
        }
        $data = $r->json();
        if (!$r->ok() || !is_array($data)) {
            throw new RuntimeException("PSN $path: HTTP {$r->status} " . $r->excerpt());
        }

        return $data;
    }

    public function fetch(SyncRun $run): array
    {
        $run->account = self::onlineId($this->accessToken());

        $titles = [];
        for ($offset = 0; ; ) {
            // every category, as the old exporter asked: a game PSN no longer sells comes back as "not_found"
            // (SingStar), and the apps that aren't games are left out by normalize()
            $page = $this->get('/gamelist/v2/users/me/titles', ['limit' => 200, 'offset' => $offset]);
            array_push($titles, ...($page['titles'] ?? []));
            if (empty($page['nextOffset']) || $page['nextOffset'] <= $offset) {
                break;
            }
            $offset = (int) $page['nextOffset'];
        }

        $trophies = [];
        for ($offset = 0; ; ) {
            $page = $this->get('/trophy/v1/users/me/trophyTitles', ['limit' => 800, 'offset' => $offset]);
            array_push($trophies, ...($page['trophyTitles'] ?? []));
            if (empty($page['nextOffset']) || $page['nextOffset'] <= $offset) {
                break;
            }
            $offset = (int) $page['nextOffset'];
        }

        try {
            $summary = $this->get('/trophy/v1/users/me/trophySummary');
        } catch (RuntimeException $e) {
            $run->log('No trophy summary: ' . $e->getMessage());
            $summary = null;
        }
        $run->log(count($titles) . ' games played, ' . count($trophies) . ' trophy sets.');

        return ['exported_at_utc' => gmdate('c'), 'titles' => $titles, 'trophyTitles' => $trophies, 'trophySummary' => $summary];
    }

    private static function clean(?string $s): string
    {
        $s = preg_replace('/\s*\(PlayStation\s*\d\)\s*$/i', '', preg_replace('/[™®©]/u', '', (string) $s));
        $s = preg_replace('/\s+Troph(y|ies)( Collection)?$/i', '', $s);

        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    /** "PT26H12M11S" → 1572 (minutes) */
    private static function minutes(?string $d): ?int
    {
        if (!preg_match('/^P(?:(\d+)D)?T?(?:(\d+)H)?(?:(\d+)M)?(?:([\d.]+)S)?$/', (string) $d, $m)) {
            return null;
        }

        return (int) round(((int) ($m[1] ?? 0)) * 1440 + ((int) ($m[2] ?? 0)) * 60 + ((int) ($m[3] ?? 0)) + ((float) ($m[4] ?? 0)) / 60);
    }

    /** As tools/psn-sync.mjs's fromExport(). */
    public function normalize(array $raw, SyncRun $run): array
    {
        $titles = [];
        foreach ($raw['titles'] as $t) {
            if (preg_match(self::NOT_A_GAME, (string) ($t['category'] ?? ''))) {
                continue;
            }
            $id = $t['titleId'];
            $console = str_starts_with((string) ($t['category'] ?? ''), 'ps5') || str_starts_with($id, 'PPSA') ? 'ps5'
                : (str_starts_with((string) ($t['category'] ?? ''), 'ps4') || str_starts_with($id, 'CUSA') ? 'ps4' : null);
            $titles[$id] = [
                'id' => $id,
                'name' => self::clean($t['localizedName'] ?? $t['name'] ?? ''),
                'console' => $console,
                'playtime' => self::minutes($t['playDuration'] ?? null),
                'playCount' => $t['playCount'] ?? null,
                'firstPlayed' => $t['firstPlayedDateTime'] ?? null,
                'lastPlayed' => $t['lastPlayedDateTime'] ?? null,
                'image' => $t['localizedImageUrl'] ?? $t['imageUrl'] ?? null,
            ];
        }

        $grades = fn (?array $o) => ['platinum' => $o['platinum'] ?? 0, 'gold' => $o['gold'] ?? 0, 'silver' => $o['silver'] ?? 0, 'bronze' => $o['bronze'] ?? 0];
        $sets = [];
        foreach ($raw['trophyTitles'] as $s) {
            $defined = $grades($s['definedTrophies'] ?? null);
            $earned = $grades($s['earnedTrophies'] ?? null);
            $sets[$s['npCommunicationId']] = [
                'id' => $s['npCommunicationId'],
                'name' => self::clean($s['trophyTitleName'] ?? ''),
                'detail' => ($s['trophyTitleDetail'] ?? '') ?: null,
                // the newer trophy service is the PS5's; the older one serves the PS3, PS4 and Vita
                'consoles' => ($s['npServiceName'] ?? '') === 'trophy2' ? ['ps5'] : ['ps4', 'ps3'],
                'version' => $s['trophySetVersion'] ?? null,
                'icon' => $s['trophyTitleIconUrl'] ?? null,
                'total' => array_sum($defined),
                'defined' => $defined,
                'earned' => $earned,
                'earnedTotal' => array_sum($earned),
                'progress' => $s['progress'] ?? null,
                'lastUpdated' => $s['lastUpdatedDateTime'] ?? null,
            ];
        }

        $sum = $raw['trophySummary'] ?? null;

        return [
            'account' => [
                'synced' => $raw['exported_at_utc'],
                'source' => 'psn',
                'trophyLevel' => $sum ? [
                    'level' => isset($sum['trophyLevel']) ? (int) $sum['trophyLevel'] : null,
                    'tier' => $sum['tier'] ?? null,
                    'progress' => $sum['progress'] ?? null,
                    'points' => $sum['trophyPoint'] ?? platform_account('psn')['trophyLevel']['points'] ?? null,
                    'earned' => $grades($sum['earnedTrophies'] ?? null),
                ] : (platform_account('psn')['trophyLevel'] ?? null),
            ],
            'collections' => ['titles' => $titles, 'trophySets' => $sets],
        ];
    }

    public function merge(array $data, SyncRun $run): void
    {
        $this->tie($run, 'titles', $data['collections']['titles'], 'psnTitleId', fn ($t) => [$t['console']]);
        $this->tie($run, 'trophySets', $data['collections']['trophySets'], 'psnTrophySet', fn ($s) => $s['consoles']);
    }

    private function tie(SyncRun $run, string $collection, array $entries, string $field, callable $consolesOf): void
    {
        $tied = [];
        foreach ($run->games as $g) {
            foreach (game_links($g, $field) as $id) {
                $tied[$id] = true;
            }
        }

        foreach ($entries as $e) {
            if (isset($tied[$e['id']])) {
                continue;
            }
            $n = norm_name($e['name']);
            $consoles = array_filter($consolesOf($e));
            // a name PSN uses for more than one entry on these consoles can't say which copy is which
            $twins = array_filter($entries, fn ($o) => $o['id'] !== $e['id'] && !isset($tied[$o['id']]) && norm_name($o['name']) === $n
                && array_intersect(array_filter($consolesOf($o)), $consoles));
            $free = array_filter($run->games, fn ($g) => in_array($g['console'], $consoles, true) && !game_links($g, $field));
            $same = array_filter($free, fn ($g) => norm_name($g['title']) === $n);
            $cs = $same ?: array_filter($free, fn ($g) => str_starts_with(norm_name($g['title']), $n . ' '));

            if (count($cs) === 1 && !$twins) {
                $run->link(array_key_first($cs), $field, $e['id'], $e['name']);
                $tied[$e['id']] = true;
            } elseif ($cs) {
                $run->report('ambiguous', $collection, $e['id'], $e['name'], ids_of($cs), $twins ? 'Another PSN entry has the same name.' : null);
            } else {
                $run->report('unmatched', $collection, $e['id'], $e['name'] . ' (' . implode('/', $consoles) . ')');
            }
        }

        foreach ($run->games as $id => $g) {
            foreach (game_links($g, $field) as $extId) {
                if (!isset($entries[$extId])) {
                    $run->change('missing', $id, $field, null, $extId, $g['title'], 'Not in PSN\'s answer any more; the copy stays tied.');
                }
            }
        }
    }
}
