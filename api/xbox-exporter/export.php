<?php
declare(strict_types=1);
/**
 * Xbox exporter. PHP 8+ and cURL, nothing to install.
 *
 *   php export.php login              once: sign in to the Microsoft account, saves session.json
 *   php export.php                    saves exports/*.json (games, play time, achievements)
 *   php export.php --no-achievements  skips the per-game achievement lists (much faster)
 *
 * It signs in the way the Xbox app does (Microsoft account, the Xbox app's client id), so no Azure
 * app or API key is needed, and two-step verification happens in the browser. It reads the same
 * Xbox Live services the Xbox app uses: the title history (every game played on the account, on
 * Xbox 360, Xbox One, Series X|S or PC, with its gamerscore and achievement counts), the minutes
 * played that Xbox keeps for each game, and each game's achievements. These services are
 * undocumented for outside use and can change at any time. It only reads; it changes nothing.
 * session.json is a login credential: never commit it.
 */

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Run this exporter from the command line; see README.md.\n"); }
if (!function_exists('curl_init')) { fwrite(STDERR, "PHP cURL extension is required.\n"); exit(1); }

// the Xbox app's sign-in: Microsoft's page sends the browser to REDIRECT_URI with ?code=…
const CLIENT_ID = '000000004C12AE6F';
const REDIRECT_URI = 'https://login.live.com/oauth20_desktop.srf';
const SCOPE = 'service::user.auth.xboxlive.com::MBI_SSL';
const LIVE = 'https://login.live.com';

$session = __DIR__ . '/session.json';
$outDir = __DIR__ . '/exports';
$locale = getenv('XBOX_LOCALE') ?: 'en-GB'; // the language of the game names
$args = array_slice($argv, 1);

function http(string $method, string $url, array $headers = [], ?string $body = null): array {
    $ch = curl_init($url);
    $respHeaders = [];
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 12, CURLOPT_TIMEOUT => 45,
        CURLOPT_USERAGENT => 'games-website-xbox-exporter/1.0', CURLOPT_HTTPHEADER => $headers,
        CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$respHeaders) {
            if (str_contains($line, ':')) { [$k, $v] = explode(':', $line, 2); $respHeaders[strtolower(trim($k))] = trim($v); }
            return strlen($line);
        },
    ] + ($body === null ? [] : [CURLOPT_POSTFIELDS => $body]));
    $res = curl_exec($ch); $err = curl_error($ch); $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($res === false) throw new RuntimeException('Request failed: ' . $err);
    return [$status, (string)$res, $respHeaders];
}

function writeJson(string $path, mixed $data, bool $secret = false): void {
    $json = json_encode($data, $secret ? 0 : JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false || file_put_contents($path, $json . PHP_EOL, LOCK_EX) === false) throw new RuntimeException("Could not write {$path}");
    if ($secret) chmod($path, 0600);
}

function readHidden(string $prompt): string {
    fwrite(STDERR, $prompt);
    $tty = stream_isatty(STDIN);
    if ($tty) shell_exec('stty -echo');
    $line = trim((string)fgets(STDIN));
    if ($tty) { shell_exec('stty echo'); fwrite(STDERR, PHP_EOL); }
    return $line;
}

/* ---------- signing in ---------- */

// a Microsoft account token, from a sign-in code or the saved refresh token; Microsoft hands back a new refresh token each time
function liveToken(array $grant): array {
    [$status, $body] = http('POST', LIVE . '/oauth20_token.srf', ['Content-Type: application/x-www-form-urlencoded'],
        http_build_query(['client_id' => CLIENT_ID, 'redirect_uri' => REDIRECT_URI, 'scope' => SCOPE] + $grant));
    $data = json_decode($body, true);
    if ($status !== 200 || empty($data['access_token'])) {
        fwrite(STDERR, "Microsoft sign-in failed: HTTP {$status} " . substr($body, 0, 300) . "\n(Run php export.php login again.)\n");
        exit(1);
    }
    return $data;
}

function login(string $session): void {
    $state = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    $url = LIVE . '/oauth20_authorize.srf?' . http_build_query([
        'client_id' => CLIENT_ID, 'redirect_uri' => REDIRECT_URI, 'response_type' => 'code', 'scope' => SCOPE,
        'state' => $state, 'display' => 'touch', 'locale' => 'en',
    ], '', '&', PHP_QUERY_RFC3986);
    fwrite(STDERR, "1) Open this link in a browser and sign in with the Microsoft account your Xbox profile uses:\n\n{$url}\n\n");
    fwrite(STDERR, "2) After signing in the page goes blank. Copy the whole address from the address bar.\n");
    fwrite(STDERR, "   It starts with " . REDIRECT_URI . "?code=\n\n");
    $link = readHidden('3) Paste it here (it is not shown): ');
    $query = parse_url($link, PHP_URL_QUERY);
    if (!str_starts_with($link, REDIRECT_URI) || !$query) exit("That isn't the link: it should start with " . REDIRECT_URI . "?code=\n");
    parse_str($query, $q);
    if (!empty($q['error'])) exit("Microsoft said: {$q['error']} " . ($q['error_description'] ?? '') . "\n");
    if (($q['state'] ?? null) !== $state) exit("That link is from another sign-in: run php export.php login again.\n");
    $tok = liveToken(['grant_type' => 'authorization_code', 'code' => $q['code'] ?? '']);
    if (empty($tok['refresh_token'])) exit("Microsoft gave no refresh token, so the session can't be saved.\n");
    $xbl = xboxAuth($tok['access_token']);
    writeJson($session, ['refresh_token' => $tok['refresh_token'], 'saved_at_utc' => gmdate('c')], true);
    echo "Signed in as {$xbl['gamertag']}. The session is saved in session.json. Now run: php export.php\n";
}

// Microsoft account token → Xbox Live user token → XSTS token for the Xbox Live services
function xboxAuth(string $msaToken): array {
    $json = ['Content-Type: application/json', 'Accept: application/json', 'x-xbl-contract-version: 1'];
    [$status, $body] = http('POST', 'https://user.auth.xboxlive.com/user/authenticate', $json, json_encode([
        'RelyingParty' => 'http://auth.xboxlive.com', 'TokenType' => 'JWT',
        'Properties' => ['AuthMethod' => 'RPS', 'SiteName' => 'user.auth.xboxlive.com', 'RpsTicket' => 't=' . $msaToken],
    ]));
    $user = json_decode($body, true)['Token'] ?? null;
    if ($status !== 200 || !$user) { fwrite(STDERR, "Xbox Live sign-in failed: HTTP {$status} " . substr($body, 0, 300) . "\n"); exit(1); }
    [$status, $body] = http('POST', 'https://xsts.auth.xboxlive.com/xsts/authorize', $json, json_encode([
        'RelyingParty' => 'http://xboxlive.com', 'TokenType' => 'JWT',
        'Properties' => ['UserTokens' => [$user], 'SandboxId' => 'RETAIL'],
    ]));
    $xsts = json_decode($body, true);
    $claims = $xsts['DisplayClaims']['xui'][0] ?? [];
    if ($status !== 200 || empty($xsts['Token']) || empty($claims['uhs'])) {
        // XErr 2148916233: no Xbox profile on this account; 2148916238: a child account that needs a family adult's consent
        $why = ['2148916233' => 'this Microsoft account has no Xbox profile', '2148916238' => 'this is a child account: an adult in the family has to allow it'][(string)($xsts['XErr'] ?? '')] ?? '';
        fwrite(STDERR, "Xbox Live authorization failed: HTTP {$status} " . ($why ?: substr($body, 0, 300)) . "\n");
        exit(1);
    }
    return ['auth' => "XBL3.0 x={$claims['uhs']};{$xsts['Token']}", 'xuid' => $claims['xid'] ?? null, 'gamertag' => $claims['gtg'] ?? null];
}

function signIn(string $session): array {
    $saved = is_file($session) ? json_decode((string)file_get_contents($session), true) : null;
    $refresh = getenv('XBOX_REFRESH_TOKEN') ?: ($saved['refresh_token'] ?? null);
    if (!$refresh) { fwrite(STDERR, "Not signed in: run php export.php login first.\n"); exit(1); }
    $tok = liveToken(['grant_type' => 'refresh_token', 'refresh_token' => $refresh]);
    // keep the newest refresh token, so the session lasts as long as it keeps being used
    if (!getenv('XBOX_REFRESH_TOKEN') && !empty($tok['refresh_token'])) writeJson($session, ['refresh_token' => $tok['refresh_token'], 'saved_at_utc' => gmdate('c')], true);
    $xbl = xboxAuth($tok['access_token']);
    if (!$xbl['xuid']) { fwrite(STDERR, "Xbox Live didn't say which profile this is.\n"); exit(1); }
    return $xbl;
}

/* ---------- Xbox Live ---------- */

// a request to an Xbox Live service, waiting and trying again when it says to slow down
function xbl(array $xbl, string $method, string $url, int $contract, ?array $body = null): array {
    global $locale;
    $headers = ['Authorization: ' . $xbl['auth'], 'x-xbl-contract-version: ' . $contract, 'Accept: application/json', 'Accept-Language: ' . $locale];
    if ($body !== null) $headers[] = 'Content-Type: application/json';
    for ($try = 0; ; $try++) {
        [$status, $res, $h] = http($method, $url, $headers, $body === null ? null : json_encode($body));
        if ($status !== 429 || $try >= 5) break;
        $wait = max(5, (int)($h['retry-after'] ?? 15));
        fwrite(STDERR, "  Xbox Live asks to slow down, waiting {$wait}s…\n");
        sleep($wait);
    }
    usleep(250000);
    return [$status, json_decode($res, true), $res];
}

function export(string $session, string $outDir, bool $withAchievements): void {
    $xbl = signIn($session);
    $xuid = $xbl['xuid'];
    if (!is_dir($outDir) && !mkdir($outDir, 0700, true) && !is_dir($outDir)) { fwrite(STDERR, "Cannot create {$outDir}\n"); exit(1); }
    $stamp = gmdate('c');
    echo "Signed in as {$xbl['gamertag']}\n";

    // the profile: gamertag, gamerscore, picture
    [$status, $profile] = xbl($xbl, 'GET', "https://profile.xboxlive.com/users/xuid({$xuid})/profile/settings?settings=Gamertag,ModernGamertag,ModernGamertagSuffix,Gamerscore,GameDisplayPicRaw,AccountTier,TenureLevel", 3);
    if ($status === 200) writeJson("{$outDir}/profile.json", ['exported_at_utc' => $stamp, 'data' => $profile]);
    else fwrite(STDERR, "Profile request failed: HTTP {$status} (carrying on)\n");

    // every title played on the account, with its achievement counts, image, details and store ids
    [$status, $history, $raw] = xbl($xbl, 'GET', "https://titlehub.xboxlive.com/users/xuid({$xuid})/titles/titlehistory/decoration/achievement,image,detail,scid,gamepass,productid?maxItems=5000", 2);
    if ($status !== 200 || !isset($history['titles'])) { fwrite(STDERR, "Title history request failed: HTTP {$status} " . substr($raw, 0, 300) . "\n"); exit(1); }
    $titles = $history['titles'];
    writeJson("{$outDir}/title-history.json", ['exported_at_utc' => $stamp, 'data' => $history]);
    $games = array_values(array_filter($titles, fn($t) => ($t['type'] ?? 'Game') === 'Game'));
    echo 'Titles played: ' . count($titles) . ' (' . count($games) . " games)\n";

    // minutes played, which Xbox keeps per game (Xbox One, Series and PC games; not Xbox 360 ones)
    $minutes = [];
    foreach (array_chunk($games, 25) as $chunk) {
        $stats = array_map(fn($t) => ['name' => 'MinutesPlayed', 'titleid' => (string)$t['titleId']], $chunk);
        [$status, $res] = xbl($xbl, 'POST', 'https://userstats.xboxlive.com/batch', 2, ['arrangebyfield' => 'xuid', 'xuids' => [(string)$xuid], 'stats' => $stats]);
        if ($status !== 200) { fwrite(STDERR, "  Minutes played request failed: HTTP {$status} (carrying on)\n"); continue; }
        $got = $res['statlistscollection'][0]['stats'] ?? [];
        foreach ($got as $i => $s) {
            // each stat names its title; if not, they come back in the order they were asked for
            $id = (string)($s['titleid'] ?? $s['titleId'] ?? ($stats[$i]['titleid'] ?? ''));
            if ($id !== '' && isset($s['value']) && $s['value'] !== '') $minutes[$id] = (int)$s['value'];
        }
    }
    writeJson("{$outDir}/minutes-played.json", ['exported_at_utc' => $stamp, 'data' => $minutes]);
    echo 'Games with minutes played: ' . count($minutes) . ', ' . round(array_sum($minutes) / 60) . " hours in all\n";

    // each game's achievements: newer games (contract 2) list every achievement, locked or not; Xbox 360 ones (contract 1)
    // need the full list and the unlocked ones asked for separately
    if ($withAchievements) {
        $all = [];
        $played = array_values(array_filter($games, fn($t) => ($t['achievement']['totalAchievements'] ?? 0) > 0 || ($t['achievement']['currentAchievements'] ?? 0) > 0));
        foreach ($played as $n => $t) {
            $id = (string)$t['titleId'];
            fwrite(STDERR, sprintf("  achievements %d/%d %s\n", $n + 1, count($played), $t['name'] ?? $id));
            $is360 = in_array('Xbox360', $t['devices'] ?? [], true) && !array_intersect(['XboxOne', 'XboxSeries', 'PC', 'Win32'], $t['devices'] ?? []);
            if (!$is360) {
                $list = pages($xbl, "https://achievements.xboxlive.com/users/xuid({$xuid})/achievements?titleId={$id}", 2);
                // an Xbox 360 game played on an Xbox One comes back empty here: its achievements are the 360 ones
                if ($list || ($list !== null && !in_array('Xbox360', $t['devices'] ?? [], true))) { $all[$id] = ['contract' => 2, 'achievements' => $list]; continue; }
            }
            $defined = pages($xbl, "https://achievements.xboxlive.com/users/xuid({$xuid})/titleachievements?titleId={$id}", 1);
            $earned = pages($xbl, "https://achievements.xboxlive.com/users/xuid({$xuid})/achievements?titleId={$id}", 1);
            if ($defined !== null || $earned !== null) $all[$id] = ['contract' => 1, 'achievements' => $defined ?? [], 'earned' => $earned ?? []];
        }
        writeJson("{$outDir}/achievements.json", ['exported_at_utc' => $stamp, 'data' => $all]);
        echo 'Achievement lists: ' . count($all) . " games\n";
    }

    $gs = array_sum(array_map(fn($t) => (int)($t['achievement']['currentGamerscore'] ?? 0), $titles));
    echo "Gamerscore: {$gs}\nExport finished: exports/\n";
}

// every page of an achievements list, or null when the service has nothing for it
function pages(array $xbl, string $url, int $contract): ?array {
    $out = []; $token = null;
    do {
        [$status, $res] = xbl($xbl, 'GET', $url . '&maxItems=1000' . ($token ? '&continuationToken=' . urlencode($token) : ''), $contract);
        if ($status !== 200 || !isset($res['achievements'])) return $out ?: null;
        array_push($out, ...$res['achievements']);
        $token = $res['pagingInfo']['continuationToken'] ?? null;
    } while ($token);
    return $out;
}

($args[0] ?? '') === 'login' ? login($session) : export($session, $outDir, !in_array('--no-achievements', $args, true));
