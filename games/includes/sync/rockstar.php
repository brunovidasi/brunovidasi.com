<?php

/**
 * Rockstar Games, through the Social Club API rockstargames.com itself uses
 * (undocumented; Rockstar can change it): every game the account has played,
 * where and when, and the achievements unlocked on PC. Rockstar keeps no play
 * time anyone can read. Was api/rockstar-exporter + tools/rockstar-sync.mjs.
 *
 * Its token lasts five minutes, so each sync gets a new one the way the site
 * does: it replays the site's silent sign-in check (the sign-in cookies and the
 * browser's fingerprint, pasted once in Settings → Connections as the request
 * "Copy as cURL" gives), and keeps the cookies Rockstar hands back, so the
 * sign-in lasts as long as the one on rockstargames.com.
 *
 * A game played on PC is tied to the one PC copy with the same name; of
 * several, the one on the Rockstar Games Launcher. One with no copy at all was
 * bought in the launcher, so it gets a new digital one. The console games are
 * left to PSN and Xbox, which know their play time.
 */
final class RockstarSync extends PlatformSync
{
    private const WWW = 'https://www.rockstargames.com';
    private const CHECK = 'https://signin.rockstargames.com/connect/cors/check/rsg';
    private const SCAPI = 'https://scapi.rockstargames.com';
    private const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36';

    // Rockstar's short names (the Social Club's, and the launcher's), the same game under each
    public const TITLES = [
        'gtav' => 'Grand Theft Auto V',
        'gtavgen9' => 'Grand Theft Auto V',
        'gtaiv' => 'Grand Theft Auto IV',
        'gtaeflc' => 'Grand Theft Auto: Episodes from Liberty City',
        'gtasa' => 'Grand Theft Auto: San Andreas',
        'gtavc' => 'Grand Theft Auto: Vice City',
        'gtaiii' => 'Grand Theft Auto III',
        'gta3unreal' => 'Grand Theft Auto III – The Definitive Edition',
        'gtavcunreal' => 'Grand Theft Auto: Vice City – The Definitive Edition',
        'gtasaunreal' => 'Grand Theft Auto: San Andreas – The Definitive Edition',
        'rdr' => 'Red Dead Redemption',
        'rdr2' => 'Red Dead Redemption 2',
        'lan' => 'L.A. Noire',
        'lanvr' => 'L.A. Noire: The VR Case Files',
        'mp3' => 'Max Payne 3',
        'bully' => 'Bully: Scholarship Edition',
    ];
    // the same game, one entry: GTA V Enhanced is GTA V
    private const SAME = ['gtavgen9' => 'gtav'];

    private ?string $bearer = null;

    public function connected(): bool
    {
        return credential('rockstar', 'check') !== null;
    }

    /** Settings → Connections: the site's sign-in check, copied as cURL. Returns the Social Club nickname. */
    public static function connect(string $curl): ?string
    {
        $req = self::parseCurl($curl);
        if (!str_contains((string) $req['url'], 'connect/cors/check')) {
            throw new RuntimeException('That isn\'t the rsg request: copy the POST to ' . self::CHECK . ' as cURL.');
        }
        if (!$req['cookies'] || !str_contains((string) $req['body'], 'fingerprint')) {
            throw new RuntimeException('That request has no cookies or no fingerprint: copy it as cURL (bash), not as fetch.');
        }
        set_credential('rockstar', 'check', json_encode($req, JSON_UNESCAPED_SLASHES));
        $token = (new self('rockstar'))->bearer();

        return jwt_claims($token)['scAuth.Nickname'] ?? null;
    }

    /** A bearer token good for the next few minutes. */
    private function bearer(): string
    {
        if ($this->bearer !== null && (int) (jwt_claims($this->bearer)['exp'] ?? 0) > time() + 30) {
            return $this->bearer;
        }
        $req = json_decode((string) credential('rockstar', 'check'), true);
        if (!$req) {
            throw new AuthError('Rockstar isn\'t connected: paste the sign-in check in Settings → Connections.');
        }

        // the site's silent sign-in check: the sign-in cookies and the fingerprint answer with a one-time code…
        $r = Http::request('POST', self::CHECK, [...$req['headers'], 'Cookie: ' . self::cookieHeader($req['cookies'])], (string) $req['body'], ['user_agent' => self::UA, 'retries' => 1]);
        // …and Rockstar may hand out new sign-in cookies with it: kept at once, so the sign-in lasts
        if ($r->cookies) {
            $req['cookies'] = array_merge($req['cookies'], $r->cookies);
            set_credential('rockstar', 'check', json_encode($req, JSON_UNESCAPED_SLASHES));
        }
        $code = $r->json();
        if ($r->status !== 200 || !is_string($code) || $code === '') {
            throw new AuthError('Rockstar didn\'t renew the sign-in (HTTP ' . $r->status . '). Sign in on rockstargames.com again and paste a new sign-in check.');
        }

        // …which the site trades for the bearer token
        $ua = array_values(array_filter($req['headers'], fn ($h) => stripos($h, 'user-agent:') === 0));
        $r = Http::get(self::WWW . '/auth/gateway.json?' . http_build_query(['code' => $code]), [...$ua, 'Accept: application/json', 'Referer: ' . self::WWW . '/'], ['user_agent' => self::UA]);
        $token = $r->json()['bearerToken'] ?? null;
        if (!is_string($token) || (int) (jwt_claims($token)['exp'] ?? 0) <= time() + 30) {
            throw new AuthError('Rockstar gave no token for the sign-in (HTTP ' . $r->status . '). Paste a new sign-in check.');
        }

        return $this->bearer = $token;
    }

    private function scapi(string $path, array $query = []): array
    {
        $r = Http::get(self::SCAPI . '/' . $path . ($query ? '?' . http_build_query($query) : ''), [
            'Authorization: Bearer ' . $this->bearer(), 'Accept: application/json', 'X-Requested-With: XMLHttpRequest',
            'Origin: ' . self::WWW, 'Referer: ' . self::WWW . '/',
        ], ['user_agent' => self::UA]);
        if ($r->status === 401) {
            throw new AuthError("Rockstar refused the token for $path (HTTP 401). Paste a new sign-in check.");
        }
        $res = $r->json();
        if ($r->status !== 200 || !is_array($res)) {
            throw new RuntimeException("Rockstar $path: HTTP {$r->status} " . $r->excerpt());
        }

        return $res;
    }

    public function fetch(SyncRun $run): array
    {
        $claims = jwt_claims($this->bearer());
        $rockstarId = (string) ($claims['nameid'] ?? '');
        $nickname = (string) ($claims['scAuth.Nickname'] ?? '');
        if ($rockstarId === '' || $nickname === '') {
            $basic = $this->scapi('profile/getbasicprofile')['accounts'][0]['rockstarAccount'] ?? [];
            $rockstarId = (string) ($basic['rockstarId'] ?? $rockstarId);
            $nickname = (string) ($basic['displayName'] ?? $nickname);
        }
        $run->account = $nickname ?: null;

        // every game the account has played, on each platform, with when it was last seen in it
        $profile = $this->scapi('profile/getprofile', ['nickname' => $nickname, 'maxFriends' => 0]);
        $games = [];
        foreach ($profile['accounts'] ?? [] as $account) {
            foreach ($account['rockstarAccount']['gamesOwned'] ?? [] as $g) {
                $games[] = $g;
            }
        }
        $run->log(count($games) . ' games played (a game on two platforms counts twice).');

        // a PC game's achievement list is under its short name; pcalt is the PC too (GTA V Enhanced)
        $titles = [];
        foreach ($games as $g) {
            $name = strtolower((string) ($g['name'] ?? $g['title'] ?? ''));
            if ($name !== '' && $name !== 'launcher' && in_array(strtolower((string) ($g['platform'] ?? 'pc')), ['pc', 'pcalt'], true)) {
                $titles[$name] = true;
            }
        }
        $achievements = [];
        foreach (array_keys($titles) as $title) {
            try {
                $achievements[$title] = $this->scapi('achievements/awardedAchievements', ['title' => $title, 'platform' => 'pc', 'rockstarId' => $rockstarId])['awardedAchievements'] ?? [];
            } catch (RuntimeException $e) {
                if ($e instanceof AuthError) {
                    throw $e;
                }
                // a game with no achievements on the Social Club (Bully, the older GTAs) has no list
            }
        }

        return ['exported_at_utc' => gmdate('c'), 'games' => $games, 'achievements' => $achievements];
    }

    /** As tools/rockstar-sync.mjs's fromExport(). */
    public function normalize(array $raw, SyncRun $run): array
    {
        $out = [];
        $unknown = [];
        foreach ($raw['games'] as $g) {
            // "GTAV_PC", "GTAV" → "gtav"
            $code = preg_replace('/_(pc|ps\d|xbox\w*|xb\w*)$/', '', strtolower((string) ($g['name'] ?? $g['title'] ?? $g['titleName'] ?? '')));
            if ($code === '' || $code === 'launcher') {
                continue;
            }
            $key = self::SAME[$code] ?? $code;
            if (!isset(self::TITLES[$key])) {
                $unknown[$code] = true;
            }
            $out[$key] ??= ['code' => $key, 'name' => self::TITLES[$key] ?? strtoupper($code), 'platforms' => [], 'lastPlayed' => null, 'achievements' => null];
            $platform = preg_replace('/^pcalt$/', 'pc', strtolower((string) ($g['platform'] ?? (isset($g['titleName']) ? explode('_', $g['titleName'])[1] ?? 'pc' : 'pc'))));
            $seen = $g['lastSeen'] ?? $g['lastPlayed'] ?? null;
            $out[$key]['platforms'][$platform] = latest_date($out[$key]['platforms'][$platform] ?? null, $seen);
            $out[$key]['lastPlayed'] = latest_date($out[$key]['lastPlayed'], $seen);
        }

        foreach ($raw['achievements'] as $code => $list) {
            $key = self::SAME[$code] ?? $code;
            if (!isset($out[$key])) {
                continue;
            }
            $unlocked = [];
            foreach ($list ?: [] as $id => $a) {
                $unlocked[] = ['id' => (string) $id, 'date' => $a['dateAchieved'] ?? null];
            }
            if (!$unlocked) {
                continue;
            }
            // Rockstar gives only the ones unlocked, so how many there are in all isn't known here
            $all = [...($out[$key]['achievements']['unlocked'] ?? []), ...$unlocked];
            usort($all, fn ($x, $y) => strcmp((string) $y['date'], (string) $x['date']));
            $out[$key]['achievements'] = ['earned' => count($all), 'unlocked' => $all];
        }

        $unknown = array_keys($unknown);
        sort($unknown);

        return [
            'account' => [
                'synced' => $raw['exported_at_utc'],
                'source' => 'rockstar',
                'totals' => ['games' => count($out), 'pc' => count(array_filter($out, fn ($e) => array_key_exists('pc', $e['platforms'])))],
                'unknown' => $unknown,
            ],
            'collections' => ['games' => $out],
        ];
    }

    private static function norm(string $s): string
    {
        return norm_name(str_replace('.', '', $s));
    }

    public function merge(array $data, SyncRun $run): void
    {
        $rs = $data['collections']['games'];
        $tied = [];
        foreach ($run->games as $g) {
            if (!empty($g['rockstarTitle'])) {
                $tied[$g['rockstarTitle']] = true;
            }
        }

        foreach ($rs as $game) {
            if (isset($tied[$game['code']]) || !array_key_exists('pc', $game['platforms']) || !isset(self::TITLES[$game['code']])) {
                continue;
            }
            $n = self::norm($game['name']);
            $same = array_filter($run->games, fn ($g) => $g['console'] === 'pc' && empty($g['hidden']) && empty($g['rockstarTitle'])
                && in_array($n, array_map([self::class, 'norm'], array_filter([$g['title'], $g['listedAs'] ?? null])), true));
            $cs = count($same) < 2 ? $same : (array_filter($same, fn ($g) => ($g['launcher'] ?? null) === 'rockstar') ?: $same);

            if (count($cs) === 1) {
                $run->link(array_key_first($cs), 'rockstarTitle', $game['code'], $game['name']);
            } elseif ($cs) {
                $run->report('ambiguous', 'games', $game['code'], $game['name'], ids_of($cs));
            } else {
                $run->add([
                    'id' => 'pc-' . slugify($game['name']), 'console' => 'pc', 'title' => $game['name'], 'region' => 'PAL',
                    'released' => null, 'publisher' => 'Rockstar Games', 'developer' => null, 'genre' => null,
                    'format' => 'digital', 'launcher' => 'rockstar', 'rockstarTitle' => $game['code'], 'cover' => null, 'colors' => null,
                ], '-rockstar', $game['name']);
            }
            $tied[$game['code']] = true;
        }

        foreach ($data['account']['unknown'] as $code) {
            $run->report('unmatched', 'games', $code, strtoupper($code), [], 'A Rockstar code the site doesn\'t know a name for yet.');
        }

        foreach ($run->games as $id => $g) {
            if (!empty($g['rockstarTitle']) && !isset($rs[$g['rockstarTitle']])) {
                $run->change('missing', $id, 'rockstarTitle', null, $g['rockstarTitle'], $g['title'], 'Not in Rockstar\'s answer any more; the copy stays tied.');
            }
        }
    }

    /* ---------- reading a request copied as cURL ---------- */

    private static function parseCookies(string $header): array
    {
        $jar = [];
        foreach (explode(';', preg_replace('/^cookie:\s*/i', '', trim($header))) as $pair) {
            [$k, $v] = array_pad(explode('=', trim($pair), 2), 2, '');
            if ($k !== '') {
                $jar[$k] = $v;
            }
        }

        return $jar;
    }

    private static function cookieHeader(array $jar): string
    {
        return implode('; ', array_map(fn ($k, $v) => "$k=$v", array_keys($jar), $jar));
    }

    /** The words of a shell command, as bash reads them: '…', $'…', "…" and \ line breaks. */
    private static function shellWords(string $cmd): array
    {
        $words = [];
        $w = null;
        $n = strlen($cmd);
        for ($i = 0; $i < $n; $i++) {
            $c = $cmd[$i];
            if ($c === '\\' && ($cmd[$i + 1] ?? '') === "\n") {
                $i++;
                continue;
            }
            if (ctype_space($c)) {
                if ($w !== null) {
                    $words[] = $w;
                    $w = null;
                }
                continue;
            }
            $w ??= '';
            if ($c === "'") {
                $j = strpos($cmd, "'", $i + 1);
                $w .= substr($cmd, $i + 1, ($j === false ? $n : $j) - $i - 1);
                $i = $j === false ? $n : $j;
            } elseif ($c === '$' && ($cmd[$i + 1] ?? '') === "'") {
                for ($i += 2; $i < $n && $cmd[$i] !== "'"; $i++) {
                    if ($cmd[$i] === '\\' && $i + 1 < $n) {
                        $e = $cmd[++$i];
                        $w .= ['n' => "\n", 't' => "\t", 'r' => "\r"][$e] ?? $e;
                    } else {
                        $w .= $cmd[$i];
                    }
                }
            } elseif ($c === '"') {
                for ($i++; $i < $n && $cmd[$i] !== '"'; $i++) {
                    $w .= ($cmd[$i] === '\\' && in_array($cmd[$i + 1] ?? '', ['"', '\\', '$', '`'], true)) ? $cmd[++$i] : $cmd[$i];
                }
            } elseif ($c === '\\' && $i + 1 < $n) {
                $w .= $cmd[++$i];
            } else {
                $w .= $c;
            }
        }
        if ($w !== null) {
            $words[] = $w;
        }

        return $words;
    }

    /** A request copied as cURL: its URL, headers (without the cookie and length), cookies and body. */
    private static function parseCurl(string $cmd): array
    {
        $words = self::shellWords(str_replace("\r", '', trim($cmd)));
        $headers = [];
        $jar = [];
        $body = null;
        $url = null;
        for ($i = 1; $i < count($words); $i++) {
            $a = $words[$i];
            if (in_array($a, ['-H', '--header'], true)) {
                $h = $words[++$i] ?? '';
                if (preg_match('/^cookie:\s*(.*)$/is', $h, $m)) {
                    $jar += self::parseCookies($m[1]);
                } elseif (!preg_match('/^(content-length|accept-encoding|host):/i', $h)) {
                    $headers[] = $h;
                }
            } elseif (in_array($a, ['-b', '--cookie'], true)) {
                $jar += self::parseCookies($words[++$i] ?? '');
            } elseif (in_array($a, ['--data-raw', '--data', '-d', '--data-binary', '--data-urlencode'], true)) {
                $body = $words[++$i] ?? '';
            } elseif (in_array($a, ['-X', '--request', '-A', '--user-agent', '-e', '--referer'], true)) {
                $v = $words[++$i] ?? '';
                if ($a === '-A' || $a === '--user-agent') {
                    $headers[] = 'User-Agent: ' . $v;
                }
                if ($a === '-e' || $a === '--referer') {
                    $headers[] = 'Referer: ' . $v;
                }
            } elseif ($a !== '' && $a[0] !== '-' && $url === null) {
                $url = $a;
            }
        }

        return ['url' => $url, 'headers' => $headers, 'cookies' => $jar, 'body' => $body];
    }
}
