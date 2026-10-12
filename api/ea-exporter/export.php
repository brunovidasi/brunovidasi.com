<?php
declare(strict_types=1);
/**
 * EA app library exporter. PHP 8+ and cURL, nothing to install.
 *
 *   php export.php login               once: sign in to the EA account, saves session.json
 *   php export.php                     saves exports/owned-games.json, owned-addons.json, play-time.json, achievements.json, profile.json
 *   php export.php --no-achievements   skips the achievement lists
 *
 * It signs in the way the EA app does (EA's JUNO_PC_CLIENT with PKCE) and reads the GraphQL
 * service the EA app uses. That service is undocumented and EA can change it at any time.
 * It only reads; it changes nothing on the account. session.json is a login credential: never commit it.
 */

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Run this exporter from the command line; see README.md.\n"); }
if (!function_exists('curl_init')) { fwrite(STDERR, "PHP cURL extension is required.\n"); exit(1); }

// the EA app's own client (the same one the GOG Galaxy EA plugin uses)
const CLIENT_ID = 'JUNO_PC_CLIENT';
const CLIENT_SECRET = '4mRLtYMb6vq9qglomWEaT4ChxsXWcyqbQpuBNfMPOYOiDmYYQmjuaBsF2Zp0RyVeWkfqhE9TuGgAw7te';
const REDIRECT_URI = 'qrc:///html/login_successful.html';
const ACCOUNTS = 'https://accounts.ea.com/connect';
const GRAPHQL = 'https://service-aggregation-layer.juno.ea.com/graphql';
// the EA app signs each sign-in with a hash of the PC; EA accepts it with placeholder hardware values
const PC_SIGN_KEY = 'ISa3dpGOc8wW7Adn4auACSQmaccrOyR2';
const BATCH = 50;

$session = __DIR__ . '/session.json';
$outDir = __DIR__ . '/exports';
$args = array_slice($argv, 1);

function b64url(string $bytes): string { return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='); }

function http(string $method, string $url, array $headers = [], ?string $body = null): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 12, CURLOPT_TIMEOUT => 60,
        // EA's GraphQL host drops HTTP/2 connections from cURL
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_USERAGENT => 'EAApp/PC/13.680.0.6193', CURLOPT_HTTPHEADER => $headers,
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

function pcSign(): string {
    $payload = b64url(json_encode([
        'av' => 'v1', 'bsn' => 'None', 'gid' => 0, 'hsn' => 'None', 'mid' => (string)random_int(10 ** 15, PHP_INT_MAX),
        'msn' => 'None', 'sv' => 'v1', 'ts' => gmdate('Y-m-d H:i:s') . ':000',
    ], JSON_UNESCAPED_SLASHES));
    return $payload . '.' . b64url(hash_hmac('sha256', $payload, PC_SIGN_KEY, true));
}

function tokenRequest(array $params): array {
    [$status, $body] = http('POST', ACCOUNTS . '/token', ['Content-Type: application/x-www-form-urlencoded'],
        http_build_query($params + ['client_id' => CLIENT_ID, 'client_secret' => CLIENT_SECRET]));
    $data = json_decode($body, true);
    return [$status, is_array($data) ? $data : [], $body];
}

function login(string $session): void {
    $verifier = b64url(random_bytes(48));
    $url = ACCOUNTS . '/auth?' . http_build_query([
        'response_type' => 'code', 'client_id' => CLIENT_ID, 'display' => 'junoClient/login', 'redirect_uri' => REDIRECT_URI,
        'locale' => 'en_US', 'pc_sign' => pcSign(), 'code_challenge' => b64url(hash('sha256', $verifier, true)), 'code_challenge_method' => 'S256',
    ], '', '&', PHP_QUERY_RFC3986);
    fwrite(STDERR, "1) In a browser, open the developer tools on the Network tab (tick \"Preserve log\"/\"Keep log\", filter All or Doc, not Fetch/XHR),\n   then open this link and sign in to your EA account:\n\n{$url}\n\n");
    fwrite(STDERR, "2) After you sign in the page seems to do nothing: the EA app's address it goes to doesn't open in a browser.\n");
    fwrite(STDERR, "   Type connect/auth in the Network filter, click the last auth?… request (status 302) and copy the value of\n   the \"location\" response header. It starts with " . REDIRECT_URI . "?code=\n\n");
    $link = readHidden('3) Paste it here (it is not shown): ');
    parse_str((string)parse_url($link, PHP_URL_QUERY), $query);
    $code = $query['code'] ?? (preg_match('/^[\w.-]{20,}$/', $link) ? $link : null);
    if (!$code) exit("That isn't the address: it should contain ?code= (it starts with " . REDIRECT_URI . ").\n");
    [$status, $data, $body] = tokenRequest([
        'grant_type' => 'authorization_code', 'code' => $code, 'code_verifier' => $verifier, 'redirect_uri' => REDIRECT_URI, 'token_format' => 'JWS',
    ]);
    if ($status !== 200 || empty($data['refresh_token'])) exit("Sign-in failed: HTTP {$status} " . substr($body, 0, 200) . "\n(The code works once, for a few minutes: run login again.)\n");
    writeSecret($session, ['refresh_token' => $data['refresh_token'], 'saved_at_utc' => gmdate('c')]);
    echo "Signed in. The session is saved in session.json and renewed by each export. Now run: php export.php\n";
}

function accessToken(string $session): string {
    $refresh = getenv('EA_REFRESH_TOKEN') ?: (is_file($session) ? (json_decode((string)file_get_contents($session), true)['refresh_token'] ?? null) : null);
    if (!$refresh) { fwrite(STDERR, "Not signed in: run php export.php login first.\n"); exit(1); }
    [$status, $data, $body] = tokenRequest(['grant_type' => 'refresh_token', 'refresh_token' => $refresh]);
    if ($status !== 200 || empty($data['access_token'])) { fwrite(STDERR, "Could not get an access token: HTTP {$status} " . substr($body, 0, 200) . "\n(If the session has expired, run php export.php login again.)\n"); exit(1); }
    // EA may hand out a new refresh token; keep it so the session lasts
    if (!getenv('EA_REFRESH_TOKEN') && !empty($data['refresh_token'])) writeSecret($session, ['refresh_token' => $data['refresh_token'], 'saved_at_utc' => gmdate('c')]);
    return $data['access_token'];
}

function gql(string $token, string $query): array {
    for ($attempt = 1; ; $attempt++) {
        [$status, $body] = http('GET', GRAPHQL . '?' . http_build_query(['query' => $query], '', '&', PHP_QUERY_RFC3986), [
            'Authorization: Bearer ' . $token, 'Accept: application/json', 'x-client-id: EAX-JUNO-CLIENT', 'Referer: https://pc.ea.com/',
        ]);
        if (($status === 429 || $status >= 500) && $attempt < 4) { sleep(2 ** $attempt); continue; }
        $res = json_decode($body, true);
        if ($status !== 200 || !is_array($res) || !isset($res['data'])) throw new RuntimeException("EA request failed: HTTP {$status} " . substr($body, 0, 300));
        foreach ($res['errors'] ?? [] as $e) fwrite(STDERR, 'EA warning: ' . ($e['message'] ?? json_encode($e)) . "\n");
        return $res['data'];
    }
}

function jwtClaims(string $jwt): array {
    $part = explode('.', $jwt)[1] ?? '';
    return json_decode((string)base64_decode(strtr($part, '-_', '+/')), true) ?: [];
}

function save(string $outDir, string $name, mixed $data): void {
    $file = "{$outDir}/{$name}";
    $json = json_encode(['exported_at_utc' => gmdate('c'), 'data' => $data], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false || file_put_contents($file, $json . PHP_EOL, LOCK_EX) === false) throw new RuntimeException("Could not write {$file}");
}

const OWNED_QUERY = 'query { me { ownedGameProducts(
    storefronts: [EA], locale: "DEFAULT", paging: {limit: 9999, next: null}, productFound: true,
    orderBy: {field: NAME, direction: ASC}, ownershipMethod: [PURCHASE, REDEMPTION, ENTITLEMENT_GRANT],
    downloadableOnly: false, entitlementEnabled: true, platforms: [PC]
) { items {
    originOfferId status
    product { id name downloadable gameSlug trialDetails { trialType }
        baseItem(availabilities: [VISIBLE]) { id title baseGameSlug gameType }
        gameProductUser(storefronts: [EA]) { ownershipMethods initialEntitlementDate entitlementId status } }
} } } }';

// add-ons (expansions, packs, kits) only come back when asked for by type; the EA app's own library asks so
const ADDONS_QUERY = 'query { me { ownedGameProducts(
    locale: "DEFAULT", entitlementEnabled: true, storefronts: [EA], platforms: [PC], paging: {limit: 9999},
    type: [DIGITAL_EXTRA_CONTENT, PACKAGED_EXTRA_CONTENT]
) { items {
    originOfferId
    product { id name gameSlug baseItem { title gameType }
        gameProductUser { ownershipMethods initialEntitlementDate } }
} } } }';

function export(string $session, string $outDir, bool $withAchievements): void {
    $token = accessToken($session);
    if (!is_dir($outDir) && !mkdir($outDir, 0700, true) && !is_dir($outDir)) throw new RuntimeException("Cannot create {$outDir}");

    $profile = gql($token, 'query { me { player { pd psd displayName } } }')['me']['player'] ?? [];
    $persona = (string)($profile['psd'] ?? jwtClaims($token)['nexus']['psid'] ?? '');
    save($outDir, 'profile.json', ['displayName' => $profile['displayName'] ?? null]);

    $owned = gql($token, OWNED_QUERY)['me']['ownedGameProducts']['items'] ?? [];
    save($outDir, 'owned-games.json', $owned);
    echo 'Owned: ' . count($owned) . " games\n";

    // an add-on EA has no product page for comes back without one: its legacy offer still names it
    $addons = gql($token, ADDONS_QUERY)['me']['ownedGameProducts']['items'] ?? [];
    $unnamed = array_column(array_filter($addons, fn($a) => empty($a['product'])), 'originOfferId');
    $legacy = [];
    foreach (array_chunk($unnamed, BATCH) as $chunk) {
        $q = 'query { legacyOffers(offerIds: ' . json_encode($chunk) . ', locale: "DEFAULT") { offerId: id displayName displayType } }';
        foreach (gql($token, $q)['legacyOffers'] ?? [] as $o) $legacy[$o['offerId']] = $o;
    }
    foreach ($addons as &$a) if (empty($a['product'])) $a['legacyOffer'] = $legacy[$a['originOfferId']] ?? null;
    unset($a);
    save($outDir, 'owned-addons.json', $addons);
    echo 'Add-ons: ' . count($addons) . " items (with the games' own offers among them)\n";

    // play time is kept per game slug, so add-ons share their game's
    $slugs = array_values(array_unique(array_filter(array_map(fn($o) => $o['product']['gameSlug'] ?? null, $owned))));
    $played = [];
    foreach (array_chunk($slugs, BATCH) as $chunk) {
        $q = 'query { me { recentGames(gameSlugs: ' . json_encode($chunk) . ') { items { gameSlug lastSessionEndDate totalPlayTimeSeconds } } } }';
        foreach (gql($token, $q)['me']['recentGames']['items'] ?? [] as $g) $played[$g['gameSlug']] = $g;
    }
    save($outDir, 'play-time.json', $played);
    $seconds = array_sum(array_map(fn($g) => (int)($g['totalPlayTimeSeconds'] ?? 0), $played));
    echo 'Played: ' . count($played) . ' games, ' . round($seconds / 3600) . " hours in all\n";

    if (!$withAchievements) { echo "Export finished (no achievements): exports/\n"; return; }
    if ($persona === '') { fwrite(STDERR, "No persona id in the profile, so achievements were skipped.\n"); return; }
    // an offer's achievement set comes from its legacy offer record
    $sets = [];
    foreach (array_chunk(array_column($owned, 'originOfferId'), BATCH) as $chunk) {
        $q = 'query { legacyOffers(offerIds: ' . json_encode($chunk) . ', locale: "DEFAULT") { offerId: id achievementSetOverride } }';
        foreach (gql($token, $q)['legacyOffers'] ?? [] as $o) if (!empty($o['achievementSetOverride'])) $sets[$o['offerId']] = (string)$o['achievementSetOverride'];
    }
    $achievements = [];
    foreach (array_unique($sets) as $set) {
        $q = 'query { achievements(achievementSetIds: ' . json_encode([$set]) . ', playerPsd: ' . json_encode($persona) . ', showHidden: true) { id achievements { id name awardCount date } } }';
        try { $achievements[$set] = gql($token, $q)['achievements'][0]['achievements'] ?? []; }
        catch (RuntimeException $e) { fwrite(STDERR, "Achievements for set {$set} failed: {$e->getMessage()}\n"); }
    }
    save($outDir, 'achievements.json', ['offerToSet' => $sets, 'sets' => $achievements]);
    $unlocked = array_sum(array_map(fn($list) => count(array_filter($list, fn($a) => ($a['awardCount'] ?? 0) > 0)), $achievements));
    echo 'Achievements: ' . count($achievements) . " games, {$unlocked} unlocked\n";
    echo "Export finished: exports/\n";
}

try {
    ($args[0] ?? '') === 'login' ? login($session) : export($session, $outDir, !in_array('--no-achievements', $args, true));
} catch (RuntimeException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
