<?php
declare(strict_types=1);
/**
 * Rockstar Games exporter. PHP 8+ and cURL, nothing to install.
 *
 *   php export.php login               once: paste the site's sign-in check (copied as cURL), saves session.json
 *   php export.php                     saves exports/profile.json, games.json, last-played.json, achievements.json
 *   php export.php --no-achievements   skips the achievement lists
 *
 * It reads the Social Club API (scapi.rockstargames.com) with the bearer token rockstargames.com itself uses. That
 * token lasts five minutes, so each export gets a new one the way the site does: it replays the site's silent
 * sign-in check (signin.rockstargames.com/connect/cors/check/rsg, with the sign-in cookies and the browser's
 * fingerprint), whose code auth/gateway.json trades for a token. That API is undocumented and Rockstar can
 * change it at any time. Rockstar keeps no play time anyone can read, so there is none here.
 * It only reads; it changes nothing on the account. session.json is a login credential: never commit it.
 */

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Run this exporter from the command line; see README.md.\n"); }
if (!function_exists('curl_init')) { fwrite(STDERR, "PHP cURL extension is required.\n"); exit(1); }

const WWW = 'https://www.rockstargames.com';
const CHECK = 'https://signin.rockstargames.com/connect/cors/check/rsg';
const SCAPI = 'https://scapi.rockstargames.com';
const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36';

$session = __DIR__ . '/session.json';
$outDir = __DIR__ . '/exports';
$args = array_slice($argv, 1);

/** @return array{int, string, array<string, string>} status, body and the cookies the response set */
function http(string $method, string $url, array $headers = [], ?string $body = null): array {
    $set = [];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 12, CURLOPT_TIMEOUT => 60,
        CURLOPT_ENCODING => '', CURLOPT_USERAGENT => UA, CURLOPT_HTTPHEADER => $headers,
        CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$set) {
            if (preg_match('/^set-cookie:\s*([^=;\s]+)=([^;]*)/i', $line, $m)) $set[$m[1]] = $m[2];
            return strlen($line);
        },
    ] + ($body === null ? [] : [CURLOPT_POSTFIELDS => $body]));
    $res = curl_exec($ch); $err = curl_error($ch); $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($res === false) throw new RuntimeException('Request failed: ' . $err);
    return [$status, (string)$res, $set];
}

function writeSecret(string $path, array $data): void {
    if (file_put_contents($path, json_encode($data, JSON_UNESCAPED_SLASHES) . PHP_EOL, LOCK_EX) === false) throw new RuntimeException("Could not write {$path}");
    chmod($path, 0600);
}

// a cookie is several thousand characters, and a terminal takes a typed line of 1024 at most (macOS), so the
// line is read a key at a time instead (-icanon), with nothing shown; piped in (pbpaste |) it is read whole
function readHidden(string $prompt): string {
    if (!stream_isatty(STDIN)) return trim((string)stream_get_contents(STDIN));
    fwrite(STDERR, $prompt);
    $saved = trim((string)shell_exec('stty -g'));
    $restore = function () use ($saved) { static $done = false; if (!$done) { $done = true; shell_exec('stty ' . escapeshellarg($saved)); } };
    register_shutdown_function($restore);
    if (function_exists('pcntl_signal')) { pcntl_async_signals(true); pcntl_signal(SIGINT, function () use ($restore) { $restore(); exit(130); }); }
    shell_exec('stty -echo -icanon min 1 time 0');
    $line = '';
    while (($c = fgetc(STDIN)) !== false && $c !== "\n" && $c !== "\r") $line .= $c;
    $restore();
    fwrite(STDERR, PHP_EOL);
    // a terminal may wrap a paste in bracketed-paste marks
    return trim(str_replace(["\e[200~", "\e[201~"], '', $line));
}

/** "a=1; b=2" → ['a' => '1', 'b' => '2'] */
function parseCookies(string $header): array {
    $jar = [];
    foreach (explode(';', preg_replace('/^cookie:\s*/i', '', trim($header))) as $pair) {
        [$k, $v] = array_pad(explode('=', trim($pair), 2), 2, '');
        if ($k !== '') $jar[$k] = $v;
    }
    return $jar;
}

function cookieHeader(array $jar): string {
    return implode('; ', array_map(fn($k, $v) => "{$k}={$v}", array_keys($jar), $jar));
}

function jwtClaims(string $jwt): array {
    $part = explode('.', $jwt)[1] ?? '';
    return json_decode((string)base64_decode(strtr($part, '-_', '+/')), true) ?: [];
}

// the bearer token is a JWT that lasts five minutes; one about to run out is as good as none
function fresh(?string $jwt): bool {
    return $jwt !== null && $jwt !== '' && (int)(jwtClaims($jwt)['exp'] ?? 0) > time() + 30;
}

/** The words of a shell command, as bash reads them: '…', $'…', "…" and \ line breaks (what "Copy as cURL" gives). */
function shellWords(string $cmd): array {
    $words = []; $w = null; $n = strlen($cmd);
    for ($i = 0; $i < $n; $i++) {
        $c = $cmd[$i];
        if ($c === '\\' && ($cmd[$i + 1] ?? '') === "\n") { $i++; continue; }
        if (ctype_space($c)) { if ($w !== null) { $words[] = $w; $w = null; } continue; }
        $w ??= '';
        if ($c === "'") { $j = strpos($cmd, "'", $i + 1); $w .= substr($cmd, $i + 1, ($j === false ? $n : $j) - $i - 1); $i = $j === false ? $n : $j; }
        elseif ($c === '$' && ($cmd[$i + 1] ?? '') === "'") {
            for ($i += 2; $i < $n && $cmd[$i] !== "'"; $i++) {
                if ($cmd[$i] === '\\' && $i + 1 < $n) { $e = $cmd[++$i]; $w .= ['n' => "\n", 't' => "\t", 'r' => "\r"][$e] ?? $e; }
                else $w .= $cmd[$i];
            }
        } elseif ($c === '"') {
            for ($i++; $i < $n && $cmd[$i] !== '"'; $i++) $w .= ($cmd[$i] === '\\' && in_array($cmd[$i + 1] ?? '', ['"', '\\', '$', '`'], true)) ? $cmd[++$i] : $cmd[$i];
        } elseif ($c === '\\' && $i + 1 < $n) $w .= $cmd[++$i];
        else $w .= $c;
    }
    if ($w !== null) $words[] = $w;
    return $words;
}

/** A request copied as cURL: its headers (without the cookie and length), its cookies and its body. */
function parseCurl(string $cmd): array {
    $words = shellWords(str_replace("\r", '', $cmd));
    $headers = []; $jar = []; $body = null; $url = null;
    for ($i = 1; $i < count($words); $i++) {
        $a = $words[$i];
        if (in_array($a, ['-H', '--header'], true)) {
            $h = $words[++$i] ?? '';
            if (preg_match('/^cookie:\s*(.*)$/is', $h, $m)) $jar += parseCookies($m[1]);
            elseif (!preg_match('/^(content-length|accept-encoding|host):/i', $h)) $headers[] = $h;
        } elseif (in_array($a, ['-b', '--cookie'], true)) $jar += parseCookies($words[++$i] ?? '');
        elseif (in_array($a, ['--data-raw', '--data', '-d', '--data-binary', '--data-urlencode'], true)) $body = $words[++$i] ?? '';
        elseif (in_array($a, ['-X', '--request', '-A', '--user-agent', '-e', '--referer'], true)) {
            $v = $words[++$i] ?? '';
            if ($a === '-A' || $a === '--user-agent') $headers[] = 'User-Agent: ' . $v;
            if ($a === '-e' || $a === '--referer') $headers[] = 'Referer: ' . $v;
        } elseif ($a[0] !== '-' && $url === null) $url = $a;
    }
    return ['url' => $url, 'headers' => $headers, 'cookies' => $jar, 'body' => $body];
}

function login(string $session): void {
    fwrite(STDERR, "1) In a browser, sign in at " . WWW . "/ (the top-right Sign In; tick \"Keep me signed in\").\n");
    fwrite(STDERR, "2) Open the developer tools on the Network tab and type  check/rsg  in its filter. Wait five minutes and\n");
    fwrite(STDERR, "   reload the page: the site then checks the sign-in again, and a request named  rsg  shows up\n");
    fwrite(STDERR, "   (to signin.rockstargames.com, a POST). Right-click it > Copy > Copy as cURL (bash, on Windows).\n");
    fwrite(STDERR, "3) Then press Ctrl-C here and run:  pbpaste | php export.php login\n");
    fwrite(STDERR, "   (or paste it at the prompt below as one line).\n\n");
    $req = parseCurl(readHidden('Paste it here (it is not shown): '));
    if (!str_contains((string)$req['url'], 'connect/cors/check')) exit("That isn't the rsg request: copy the POST to " . CHECK . " as cURL.\n");
    if (!$req['cookies'] || !str_contains((string)$req['body'], 'fingerprint')) exit("That request has no cookies or no fingerprint: copy it as cURL (bash), not as fetch.\n");
    writeSecret($session, ['check' => $req, 'saved_at_utc' => gmdate('c')]);
    [$token] = bearer($session);
    $c = jwtClaims($token);
    echo 'Signed in as ' . ($c['scAuth.Nickname'] ?? '?') . ". The session is saved in session.json and renewed by each export. Now run: php export.php\n";
}

/** @return array{string} a bearer token that works for the next few minutes */
function bearer(string $session): array {
    if ($env = getenv('ROCKSTAR_BEARER')) return [$env];
    $saved = is_file($session) ? json_decode((string)file_get_contents($session), true) : null;
    $req = $saved['check'] ?? null;
    if (!$req) { fwrite(STDERR, "Not signed in (or signed in by an older version of this exporter): run php export.php login.\n"); exit(1); }
    // the site's silent sign-in check: the sign-in cookies and the fingerprint answer with a one-time code…
    [$status, $body, $set] = http('POST', CHECK, [...$req['headers'], 'Cookie: ' . cookieHeader($req['cookies'])], (string)$req['body']);
    // …and Rockstar may hand out new sign-in cookies with it: keep them, so the session lasts
    $req['cookies'] = array_merge($req['cookies'], $set);
    writeSecret($session, ['check' => $req, 'saved_at_utc' => gmdate('c')]);
    $code = json_decode($body, true);
    if ($status !== 200 || !is_string($code) || $code === '') {
        fwrite(STDERR, "Rockstar didn't renew the sign-in (HTTP {$status} " . substr($body, 0, 160) . ").\nSign in on www.rockstargames.com again and run php export.php login.\n");
        exit(1);
    }
    // …which the site trades for the bearer token
    $ua = array_values(array_filter($req['headers'], fn($h) => stripos($h, 'user-agent:') === 0));
    [$status, $body] = http('GET', WWW . '/auth/gateway.json?' . http_build_query(['code' => $code]), [...$ua, 'Accept: application/json', 'Referer: ' . WWW . '/']);
    $token = json_decode($body, true)['bearerToken'] ?? null;
    if (!is_string($token) || !fresh($token)) {
        fwrite(STDERR, "Rockstar gave no token for the code (HTTP {$status} " . substr($body, 0, 160) . ").\nSign in on www.rockstargames.com again and run php export.php login.\n");
        exit(1);
    }
    return [$token];
}

function scapi(string $token, string $path, array $query = []): array {
    $url = SCAPI . '/' . $path . ($query ? '?' . http_build_query($query) : '');
    for ($attempt = 1; ; $attempt++) {
        [$status, $body] = http('GET', $url, [
            'Authorization: Bearer ' . $token, 'Accept: application/json', 'X-Requested-With: XMLHttpRequest',
            'Origin: ' . WWW, 'Referer: ' . WWW . '/',
        ]);
        if (($status === 429 || $status >= 500) && $attempt < 4) { sleep(2 ** $attempt); continue; }
        $res = json_decode($body, true);
        if ($status === 401) throw new RuntimeException("Rockstar refused the token for {$path} (HTTP 401): run php export.php login again.");
        if ($status !== 200 || !is_array($res)) throw new RuntimeException("Rockstar request {$path} failed: HTTP {$status} " . substr($body, 0, 300));
        return $res;
    }
}

function save(string $outDir, string $name, mixed $data): void {
    $file = "{$outDir}/{$name}";
    $json = json_encode(['exported_at_utc' => gmdate('c'), 'data' => $data], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false || file_put_contents($file, $json . PHP_EOL, LOCK_EX) === false) throw new RuntimeException("Could not write {$file}");
}

function export(string $session, string $outDir, bool $withAchievements): void {
    [$token] = bearer($session);
    if (!is_dir($outDir) && !mkdir($outDir, 0700, true) && !is_dir($outDir)) throw new RuntimeException("Cannot create {$outDir}");

    // who the token is for: rockstarId (nameid) and the Social Club nickname
    $claims = jwtClaims($token);
    $rockstarId = (string)($claims['nameid'] ?? '');
    $nickname = (string)($claims['scAuth.Nickname'] ?? '');
    if ($rockstarId === '' || $nickname === '') {
        $basic = scapi($token, 'profile/getbasicprofile')['accounts'][0]['rockstarAccount'] ?? [];
        $rockstarId = (string)($basic['rockstarId'] ?? $rockstarId);
        $nickname = (string)($basic['displayName'] ?? $nickname);
    }
    save($outDir, 'profile.json', ['nickname' => $nickname, 'rockstarId' => $rockstarId]);

    // every game the account has played, on each platform, with when it was last seen in it
    $profile = scapi($token, 'profile/getprofile', ['nickname' => $nickname, 'maxFriends' => 0]);
    $games = [];
    foreach ($profile['accounts'] ?? [] as $account) foreach ($account['rockstarAccount']['gamesOwned'] ?? [] as $g) $games[] = $g;
    save($outDir, 'games.json', $games);
    echo 'Games: ' . count($games) . " (a game on two platforms counts twice)\n";

    // what the site's account menu shows as the games played
    try {
        $last = scapi($token, 'profile/lastPlayed');
        save($outDir, 'last-played.json', $last['all'] ?? $last);
    } catch (RuntimeException $e) { fwrite(STDERR, "Last played skipped: {$e->getMessage()}\n"); }

    if (!$withAchievements) { echo "Export finished (no achievements): exports/\n"; return; }
    // a PC game's achievement list is under its short name (gtav, rdr2, lan, mp3, gtaiv, gta3unreal…)
    $titles = [];
    foreach ($games as $g) {
        $name = strtolower((string)($g['name'] ?? $g['title'] ?? ''));
        // pcalt is the PC too (GTA V's second PC version, Enhanced), with the same achievements
        if ($name !== '' && $name !== 'launcher' && in_array(strtolower((string)($g['platform'] ?? 'pc')), ['pc', 'pcalt'], true)) $titles[$name] = true;
    }
    $achievements = [];
    foreach (array_keys($titles) as $title) {
        try {
            $res = scapi($token, 'achievements/awardedAchievements', ['title' => $title, 'platform' => 'pc', 'rockstarId' => $rockstarId]);
            $achievements[$title] = $res['awardedAchievements'] ?? [];
        } catch (RuntimeException $e) {
            // a game with no achievements on the Social Club (Bully, the older GTAs) has no list
            if (!str_contains($e->getMessage(), 'HTTP 404')) fwrite(STDERR, "Achievements for {$title} failed: {$e->getMessage()}\n");
        }
    }
    save($outDir, 'achievements.json', $achievements);
    $unlocked = array_sum(array_map('count', $achievements));
    echo 'Achievements: ' . count($achievements) . " games, {$unlocked} unlocked\n";
    echo "Export finished: exports/\n";
}

try {
    ($args[0] ?? '') === 'login' ? login($session) : export($session, $outDir, !in_array('--no-achievements', $args, true));
} catch (RuntimeException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
