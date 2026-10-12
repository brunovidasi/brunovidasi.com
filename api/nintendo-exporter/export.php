<?php
declare(strict_types=1);
/**
 * Nintendo Switch play-history exporter. PHP 8+ and cURL, nothing to install.
 *
 *   php export.php login    once: sign in to the Nintendo Account, saves session.json (about two years)
 *   php export.php          saves exports/play-histories.json
 *
 * It reads the same play history the Nintendo Store app shows (every Switch and Switch 2 game
 * played on the account: total minutes, days played, first and last played). The API is
 * undocumented and can change at any time. session.json is a login credential: never commit it.
 */

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Run this exporter from the command line; see README.md.\n"); }
if (!function_exists('curl_init')) { fwrite(STDERR, "PHP cURL extension is required.\n"); exit(1); }

// the Nintendo Store app's sign-in, so Nintendo's page says it is linking to "Nintendo Store"
const CLIENT_ID = '5c38e31cd085304b';
const REDIRECT_URI = 'npf' . CLIENT_ID . '://auth';
const SCOPE = 'openid user user.mii user.email user.links[].id';
const ACCOUNTS = 'https://accounts.nintendo.com/connect/1.0.0';
const API = 'https://app-api.znej.nintendo.com/api/v2.0/users/me/play_histories';

$session = __DIR__ . '/session.json';
$outDir = __DIR__ . '/exports';
$locale = getenv('NINTENDO_LOCALE') ?: 'en-GB'; // the language of the game names

function b64url(string $bytes): string { return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='); }

function http(string $method, string $url, array $headers = [], ?string $body = null): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 12, CURLOPT_TIMEOUT => 35,
        CURLOPT_USERAGENT => 'games-website-nintendo-exporter/1.0', CURLOPT_HTTPHEADER => $headers,
    ] + ($body === null ? [] : [CURLOPT_POSTFIELDS => $body]));
    $res = curl_exec($ch); $err = curl_error($ch); $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($res === false) throw new RuntimeException('Request failed: ' . $err);
    return [$status, (string)$res];
}

function writeSecret(string $path, array $data): void {
    if (file_put_contents($path, json_encode($data) . PHP_EOL, LOCK_EX) === false) throw new RuntimeException("Could not write {$path}");
    chmod($path, 0600);
}

function readHidden(string $prompt): string {
    fwrite(STDERR, $prompt);
    $tty = stream_isatty(STDIN);
    if ($tty) shell_exec('stty -echo');
    $line = trim((string)fgets(STDIN));
    if ($tty) { shell_exec('stty echo'); fwrite(STDERR, PHP_EOL); }
    return $line;
}

function login(string $session): void {
    $verifier = b64url(random_bytes(32));
    $state = b64url(random_bytes(36));
    $url = ACCOUNTS . '/authorize?' . http_build_query([
        'state' => $state, 'redirect_uri' => REDIRECT_URI, 'client_id' => CLIENT_ID, 'scope' => SCOPE,
        'response_type' => 'session_token_code',
        'session_token_code_challenge' => b64url(hash('sha256', $verifier, true)),
        'session_token_code_challenge_method' => 'S256',
    ], '', '&', PHP_QUERY_RFC3986);
    fwrite(STDERR, "1) Open this link in a browser and sign in to your Nintendo Account:\n\n{$url}\n\n");
    fwrite(STDERR, "2) On the \"Select this person\" page, don't click the button: right-click it and copy the link address.\n");
    fwrite(STDERR, "   It starts with " . REDIRECT_URI . "#\n\n");
    $link = readHidden('3) Paste it here (it is not shown): ');
    if (!str_starts_with($link, REDIRECT_URI . '#')) exit("That isn't the link: it should start with " . REDIRECT_URI . "# (not the address bar's URL).\n");
    parse_str(substr($link, strpos($link, '#') + 1), $frag);
    if (($frag['state'] ?? null) !== $state) exit("That link is from another sign-in: run php export.php login again.\n");
    [$status, $body] = http('POST', ACCOUNTS . '/api/session_token', ['Content-Type: application/x-www-form-urlencoded'], http_build_query([
        'client_id' => CLIENT_ID, 'session_token_code' => $frag['session_token_code'] ?? '', 'session_token_code_verifier' => $verifier,
    ]));
    $token = json_decode($body, true)['session_token'] ?? null;
    if ($status !== 200 || !$token) exit("Sign-in failed: HTTP {$status} " . substr($body, 0, 200) . "\n(The link works once, for a few minutes: run login again.)\n");
    writeSecret($session, ['session_token' => $token, 'saved_at_utc' => gmdate('c')]);
    echo "Signed in. The session is saved in session.json (about two years). Now run: php export.php\n";
}

function accessToken(string $session): string {
    $token = getenv('NINTENDO_SESSION_TOKEN') ?: (is_file($session) ? (json_decode((string)file_get_contents($session), true)['session_token'] ?? null) : null);
    if (!$token) { fwrite(STDERR, "Not signed in: run php export.php login first.\n"); exit(1); }
    [$status, $body] = http('POST', ACCOUNTS . '/api/token', ['Content-Type: application/json; charset=utf-8'], json_encode([
        'client_id' => CLIENT_ID, 'session_token' => $token, 'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer-session-token',
    ]));
    $access = json_decode($body, true)['access_token'] ?? null;
    if ($status !== 200 || !$access) { fwrite(STDERR, "Could not get an access token: HTTP {$status} " . substr($body, 0, 200) . "\n(If the session has expired, run php export.php login again.)\n"); exit(1); }
    return $access;
}

function export(string $session, string $outDir, string $locale): void {
    [$status, $body] = http('GET', API, ['Authorization: Bearer ' . accessToken($session), 'Accept: application/json', 'gentry-locale: ' . $locale]);
    if ($status !== 200) { fwrite(STDERR, "Play history request failed: HTTP {$status} " . substr($body, 0, 300) . "\n"); exit(1); }
    $data = json_decode($body, true);
    if (!is_array($data)) { fwrite(STDERR, "The play history was not valid JSON.\n"); exit(1); }
    if (!is_dir($outDir) && !mkdir($outDir, 0700, true) && !is_dir($outDir)) { fwrite(STDERR, "Cannot create {$outDir}\n"); exit(1); }
    $file = $outDir . '/play-histories.json';
    $json = json_encode(['exported_at_utc' => gmdate('c'), 'locale' => $locale, 'data' => $data], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false || file_put_contents($file, $json . PHP_EOL, LOCK_EX) === false) { fwrite(STDERR, "Could not write {$file}\n"); exit(1); }
    $games = $data['playHistories'] ?? [];
    $minutes = array_sum(array_map(fn($g) => (int)($g['totalPlayedMinutes'] ?? 0), $games));
    echo "Export finished: exports/play-histories.json\n";
    echo 'Games played: ' . count($games) . ', ' . round($minutes / 60) . " hours in all\n";
}

($argv[1] ?? '') === 'login' ? login($session) : export($session, $outDir, $locale);
