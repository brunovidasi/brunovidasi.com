<?php

/**
 * Xbox, through the services the Xbox app uses (undocumented; Microsoft can
 * change them): every game played on the account, on Xbox 360, Xbox One, Series
 * X|S or PC, the minutes Xbox keeps for each, and their achievements. Was
 * api/xbox-exporter + tools/xbox-sync.mjs.
 *
 * It signs in as the Xbox app does: Settings → Connections opens Microsoft's
 * sign-in, and the address it ends on is pasted back. That gives a refresh
 * token, which every sync renews (Microsoft hands a new one back each time).
 *
 * A game is tied to the one copy on a console it was played on with the same
 * name, else a name that starts with it. No copy is ever added.
 */
final class XboxSync extends PlatformSync
{
    private const CLIENT_ID = '000000004C12AE6F';
    private const REDIRECT_URI = 'https://login.live.com/oauth20_desktop.srf';
    private const SCOPE = 'service::user.auth.xboxlive.com::MBI_SSL';
    private const LIVE = 'https://login.live.com';
    private const LOCALE = 'en-GB';

    private ?array $xbl = null;

    public function connected(): bool
    {
        return credential('xbox', 'refresh_token') !== null;
    }

    /** Settings → Connections, step 1: the sign-in page, and the state to check the pasted address against. */
    public static function signInUrl(string $state): string
    {
        return self::LIVE . '/oauth20_authorize.srf?' . http_build_query([
            'client_id' => self::CLIENT_ID, 'redirect_uri' => self::REDIRECT_URI, 'response_type' => 'code', 'scope' => self::SCOPE,
            'state' => $state, 'display' => 'touch', 'locale' => 'en',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** Step 2: the address the sign-in ended on. Returns the gamertag. */
    public static function connect(string $link, string $state): ?string
    {
        $link = trim($link);
        $query = parse_url($link, PHP_URL_QUERY);
        if (!str_starts_with($link, self::REDIRECT_URI) || !$query) {
            throw new RuntimeException('That isn\'t the address: it starts with ' . self::REDIRECT_URI . '?code=');
        }
        parse_str($query, $q);
        if (!empty($q['error'])) {
            throw new RuntimeException("Microsoft said: {$q['error']} " . ($q['error_description'] ?? ''));
        }
        if (($q['state'] ?? null) !== $state) {
            throw new RuntimeException('That address is from another sign-in: open the sign-in link again.');
        }
        $tok = self::liveToken(['grant_type' => 'authorization_code', 'code' => $q['code'] ?? '']);
        if (empty($tok['refresh_token'])) {
            throw new RuntimeException('Microsoft gave no refresh token, so the sign-in can\'t be kept.');
        }
        $xbl = self::xboxAuth($tok['access_token']);
        set_credential('xbox', 'refresh_token', $tok['refresh_token']);

        return $xbl['gamertag'];
    }

    /** A Microsoft account token, from a sign-in code or the saved refresh token. */
    private static function liveToken(array $grant): array
    {
        $r = Http::request('POST', self::LIVE . '/oauth20_token.srf', ['Content-Type: application/x-www-form-urlencoded'],
            http_build_query(['client_id' => self::CLIENT_ID, 'redirect_uri' => self::REDIRECT_URI, 'scope' => self::SCOPE] + $grant), ['retries' => 1]);
        $data = $r->json();
        if ($r->status !== 200 || empty($data['access_token'])) {
            throw new AuthError('Microsoft sign-in failed (HTTP ' . $r->status . ' ' . $r->excerpt(150) . '). Connect Xbox again.');
        }

        return $data;
    }

    /** Microsoft account token → Xbox Live user token → XSTS token for the Xbox Live services. */
    private static function xboxAuth(string $msaToken): array
    {
        $json = ['Content-Type: application/json', 'Accept: application/json', 'x-xbl-contract-version: 1'];
        $r = Http::request('POST', 'https://user.auth.xboxlive.com/user/authenticate', $json, json_encode([
            'RelyingParty' => 'http://auth.xboxlive.com', 'TokenType' => 'JWT',
            'Properties' => ['AuthMethod' => 'RPS', 'SiteName' => 'user.auth.xboxlive.com', 'RpsTicket' => 't=' . $msaToken],
        ]));
        $user = $r->json()['Token'] ?? null;
        if ($r->status !== 200 || !$user) {
            throw new AuthError('Xbox Live sign-in failed: HTTP ' . $r->status . ' ' . $r->excerpt(150));
        }
        $r = Http::request('POST', 'https://xsts.auth.xboxlive.com/xsts/authorize', $json, json_encode([
            'RelyingParty' => 'http://xboxlive.com', 'TokenType' => 'JWT',
            'Properties' => ['UserTokens' => [$user], 'SandboxId' => 'RETAIL'],
        ]));
        $xsts = $r->json() ?: [];
        $claims = $xsts['DisplayClaims']['xui'][0] ?? [];
        if ($r->status !== 200 || empty($xsts['Token']) || empty($claims['uhs'])) {
            $why = ['2148916233' => 'this Microsoft account has no Xbox profile', '2148916238' => 'this is a child account: an adult in the family has to allow it'][(string) ($xsts['XErr'] ?? '')] ?? $r->excerpt(150);
            throw new AuthError("Xbox Live authorization failed: $why");
        }

        return ['auth' => "XBL3.0 x={$claims['uhs']};{$xsts['Token']}", 'xuid' => $claims['xid'] ?? null, 'gamertag' => $claims['gtg'] ?? null];
    }

    private function signIn(): array
    {
        if ($this->xbl === null) {
            $tok = self::liveToken(['grant_type' => 'refresh_token', 'refresh_token' => credential('xbox', 'refresh_token')]);
            // the newest refresh token, kept at once, so the sign-in lasts as long as it keeps being used
            if (!empty($tok['refresh_token'])) {
                set_credential('xbox', 'refresh_token', $tok['refresh_token']);
            }
            $this->xbl = self::xboxAuth($tok['access_token']);
            if (!$this->xbl['xuid']) {
                throw new AuthError('Xbox Live didn\'t say which profile this is.');
            }
        }

        return $this->xbl;
    }

    /** A request to an Xbox Live service ([status, decoded body]); Http waits when it says to slow down. */
    private function xbl(string $method, string $url, int $contract, ?array $body = null): array
    {
        $headers = ['Authorization: ' . $this->signIn()['auth'], 'x-xbl-contract-version: ' . $contract, 'Accept: application/json', 'Accept-Language: ' . self::LOCALE];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }
        $r = Http::request($method, $url, $headers, $body === null ? null : json_encode($body), ['retries' => 5]);
        if ($r->status === 401) {
            throw new AuthError('Xbox Live refused the sign-in (HTTP 401). Connect Xbox again.');
        }
        usleep(250000);

        return [$r->status, $r->json()];
    }

    /** Every page of an achievements list, or null when the service has nothing for it. */
    private function pages(string $url, int $contract): ?array
    {
        $out = [];
        $token = null;
        do {
            [$status, $res] = $this->xbl('GET', $url . '&maxItems=1000' . ($token ? '&continuationToken=' . urlencode($token) : ''), $contract);
            if ($status !== 200 || !isset($res['achievements'])) {
                return $out ?: null;
            }
            array_push($out, ...$res['achievements']);
            $token = $res['pagingInfo']['continuationToken'] ?? null;
        } while ($token);

        return $out;
    }

    public function fetch(SyncRun $run): array
    {
        $xbl = $this->signIn();
        $xuid = $xbl['xuid'];
        $run->account = $xbl['gamertag'];

        [$status, $profile] = $this->xbl('GET', "https://profile.xboxlive.com/users/xuid({$xuid})/profile/settings?settings=Gamertag,Gamerscore", 3);
        if ($status !== 200) {
            $profile = null;
        }

        [$status, $history] = $this->xbl('GET', "https://titlehub.xboxlive.com/users/xuid({$xuid})/titles/titlehistory/decoration/achievement,image,detail,scid,gamepass,productid?maxItems=5000", 2);
        if ($status !== 200 || !isset($history['titles'])) {
            throw new RuntimeException("Xbox title history: HTTP $status");
        }
        $games = array_values(array_filter($history['titles'], fn ($t) => ($t['type'] ?? 'Game') === 'Game'));
        $run->log(count($history['titles']) . ' titles played (' . count($games) . ' games).');

        // minutes played, which Xbox keeps per game (Xbox One, Series and PC games; not Xbox 360 ones)
        $minutes = [];
        foreach (array_chunk($games, 25) as $chunk) {
            $stats = array_map(fn ($t) => ['name' => 'MinutesPlayed', 'titleid' => (string) $t['titleId']], $chunk);
            [$status, $res] = $this->xbl('POST', 'https://userstats.xboxlive.com/batch', 2, ['arrangebyfield' => 'xuid', 'xuids' => [(string) $xuid], 'stats' => $stats]);
            if ($status !== 200) {
                $run->log("Minutes played: HTTP $status (carrying on).");
                continue;
            }
            foreach ($res['statlistscollection'][0]['stats'] ?? [] as $i => $s) {
                $id = (string) ($s['titleid'] ?? $s['titleId'] ?? ($stats[$i]['titleid'] ?? ''));
                if ($id !== '' && isset($s['value']) && $s['value'] !== '') {
                    $minutes[$id] = (int) $s['value'];
                }
            }
        }

        // each game's achievements: newer games (contract 2) list every one, locked or not; Xbox 360 ones (contract 1)
        // need the full list and the unlocked ones asked for separately
        $all = [];
        foreach ($games as $t) {
            if (($t['achievement']['totalAchievements'] ?? 0) <= 0 && ($t['achievement']['currentAchievements'] ?? 0) <= 0) {
                continue;
            }
            $id = (string) $t['titleId'];
            $devices = $t['devices'] ?? [];
            $is360 = in_array('Xbox360', $devices, true) && !array_intersect(['XboxOne', 'XboxSeries', 'PC', 'Win32'], $devices);
            if (!$is360) {
                $list = $this->pages("https://achievements.xboxlive.com/users/xuid({$xuid})/achievements?titleId={$id}", 2);
                // an Xbox 360 game played on an Xbox One comes back empty here: its achievements are the 360 ones
                if ($list || ($list !== null && !in_array('Xbox360', $devices, true))) {
                    $all[$id] = ['contract' => 2, 'achievements' => $list];
                    continue;
                }
            }
            $defined = $this->pages("https://achievements.xboxlive.com/users/xuid({$xuid})/titleachievements?titleId={$id}", 1);
            $earned = $this->pages("https://achievements.xboxlive.com/users/xuid({$xuid})/achievements?titleId={$id}", 1);
            if ($defined !== null || $earned !== null) {
                $all[$id] = ['contract' => 1, 'achievements' => $defined ?? [], 'earned' => $earned ?? []];
            }
        }

        return ['exported_at_utc' => gmdate('c'), 'profile' => $profile, 'history' => $history, 'minutes' => $minutes, 'achievements' => $all];
    }

    /** The consoles a title was played on, as the shelf names them; a 360 game played on an Xbox One is still a 360 game. */
    private static function consolesOf(array $devices): array
    {
        if (in_array('Xbox360', $devices, true)) {
            return ['x360'];
        }
        $cs = [];
        if (array_intersect(['XboxOne', 'XboxSeries'], $devices)) {
            $cs[] = 'xone';
        }
        if (in_array('PC', $devices, true)) {
            $cs[] = 'pc';
        }

        return $cs;
    }

    private static function when(?string $t): ?string
    {
        return $t && !str_starts_with($t, '0001-01-01') ? iso_date($t) : null;
    }

    private static function achievementsOf(?array $entry): ?array
    {
        if (!$entry) {
            return null;
        }
        $live = array_values(array_filter($entry['achievements'], fn ($a) => empty($a['isRevoked'])));
        if ($entry['contract'] === 2) {
            return array_map(function ($a) {
                $got = ($a['progressState'] ?? '') === 'Achieved';
                $score = 0;
                $icon = null;
                foreach ($a['rewards'] ?? [] as $r) {
                    if (($r['type'] ?? '') === 'Gamerscore') {
                        $score = (int) $r['value'];
                        break;
                    }
                }
                foreach ($a['mediaAssets'] ?? [] as $m) {
                    if (($m['type'] ?? '') === 'Icon') {
                        $icon = $m['url'];
                        break;
                    }
                }
                return [
                    'name' => $a['name'],
                    'description' => $got || empty($a['isSecret']) ? (($a['description'] ?? '') ?: ($a['lockedDescription'] ?? null)) : null,
                    'gamerscore' => $score,
                    'unlocked' => $got ? (self::when($a['progression']['timeUnlocked'] ?? null) ?? true) : null,
                    'rarity' => $a['rarity']['currentPercentage'] ?? null,
                    'icon' => $icon,
                ];
            }, $live);
        }

        $got = array_column($entry['earned'] ?? [], null, 'id');

        return array_map(function ($a) use ($got) {
            $e = $got[$a['id']] ?? null;
            return [
                'name' => $a['name'],
                'description' => $e || empty($a['isSecret']) ? (($a['description'] ?? '') ?: ($a['lockedDescription'] ?? null)) : null,
                'gamerscore' => $a['gamerscore'] ?? 0,
                'unlocked' => $e ? (self::when($e['timeUnlocked'] ?? null) ?? true) : null,
                'rarity' => null,
                'icon' => null,
            ];
        }, $live);
    }

    /** As tools/xbox-sync.mjs's fromExport(). */
    public function normalize(array $raw, SyncRun $run): array
    {
        $titles = [];
        foreach ($raw['history']['titles'] ?? [] as $t) {
            if (($t['type'] ?? 'Game') !== 'Game') {
                continue;
            }
            $id = (string) $t['titleId'];
            $a = $t['achievement'] ?? [];
            $list = self::achievementsOf($raw['achievements'][$id] ?? null);
            $unlocked = array_values(array_filter($list ?? [], fn ($x) => $x['unlocked']));
            usort($unlocked, fn ($x, $y) => strcmp(is_string($y['unlocked']) ? $y['unlocked'] : '', is_string($x['unlocked']) ? $x['unlocked'] : ''));

            $titles[$id] = [
                'id' => $id,
                'name' => clean_name($t['name'] ?? ''),
                'consoles' => self::consolesOf($t['devices'] ?? []),
                'devices' => $t['devices'] ?? [],
                'playtime' => $raw['minutes'][$id] ?? null,
                'lastPlayed' => self::when($t['titleHistory']['lastTimePlayed'] ?? null),
                'gamePass' => !empty($t['gamePass']['isGamePass']),
                'image' => ($t['displayImage'] ?? '') !== '' ? preg_replace('/^http:/', 'https:', $t['displayImage']) : null,
                'achievements' => ($list || !empty($a['totalAchievements'])) ? [
                    // the title history sometimes says 0 achievements for a game that has them: the list knows
                    'total' => ($list ? count($list) : 0) ?: ($a['totalAchievements'] ?? 0),
                    'earned' => $list !== null ? count($unlocked) : ($a['currentAchievements'] ?? 0),
                    'gamerscore' => $a['currentGamerscore'] ?? 0,
                    'gamerscoreTotal' => ($a['totalGamerscore'] ?? 0) ?: array_sum(array_column($list ?? [], 'gamerscore')),
                    'unlocked' => array_map(fn ($x) => [
                        'name' => $x['name'], 'description' => $x['description'], 'gamerscore' => $x['gamerscore'],
                        'date' => $x['unlocked'] === true ? null : $x['unlocked'], 'rarity' => $x['rarity'], 'icon' => $x['icon'],
                    ], $unlocked),
                ] : null,
            ];
        }

        $settings = array_column($raw['profile']['profileUsers'][0]['settings'] ?? [], 'value', 'id');

        return [
            'account' => [
                'synced' => $raw['exported_at_utc'],
                'source' => 'xbox',
                'gamerscore' => isset($settings['Gamerscore']) ? (int) $settings['Gamerscore'] : array_sum(array_map(fn ($t) => $t['achievements']['gamerscore'] ?? 0, $titles)),
            ],
            'collections' => ['titles' => $titles],
        ];
    }

    public static function norm(string $s): string
    {
        $s = mb_strtolower(strip_accents(clean_name($s)));
        $s = preg_replace(['/\bgta\b/', '/\bed\./'], ['grand theft auto', 'edition'], $s);

        return norm_name($s);
    }

    public function merge(array $data, SyncRun $run): void
    {
        $titles = $data['collections']['titles'];
        $tied = [];
        foreach ($run->games as $g) {
            foreach (game_links($g, 'xboxTitleId') as $id) {
                $tied[$id] = true;
            }
        }

        foreach ($titles as $t) {
            if (isset($tied[$t['id']])) {
                continue;
            }
            if (!$t['consoles']) {
                continue; // a Windows game the Game Bar noticed
            }
            $n = self::norm($t['name']);
            $free = array_filter($run->games, fn ($g) => in_array($g['console'], $t['consoles'], true) && empty($g['hidden']) && !game_links($g, 'xboxTitleId'));
            $same = array_filter($free, fn ($g) => in_array($n, names_of($g, [self::class, 'norm']), true));
            $cs = $same ?: array_filter($free, function ($g) use ($n) {
                foreach (names_of($g, [self::class, 'norm']) as $x) {
                    if (str_starts_with($x, $n . ' ')) {
                        return true;
                    }
                }
                return false;
            });
            $how = $t['name'] . ' (' . implode('/', $t['consoles']) . ($t['gamePass'] ? ', Game Pass' : '') . ')';

            if (count($cs) === 1) {
                $run->link(array_key_first($cs), 'xboxTitleId', $t['id'], $t['name']);
                $tied[$t['id']] = true;
            } elseif ($cs) {
                $run->report('ambiguous', 'titles', $t['id'], $how, ids_of($cs));
            } else {
                $run->report('unmatched', 'titles', $t['id'], $how);
            }
        }

        foreach ($run->games as $id => $g) {
            foreach (game_links($g, 'xboxTitleId') as $extId) {
                if (!isset($titles[$extId])) {
                    $run->change('missing', $id, 'xboxTitleId', null, $extId, $g['title'], 'Not in the Xbox title history any more; the copy stays tied.');
                }
            }
        }
    }
}
