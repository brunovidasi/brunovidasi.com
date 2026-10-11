<?php
declare(strict_types=1);
/**
 * Steam personal data exporter. PHP 8+ and cURL required.
 * Run: php export.php
 * Output: exports/steam-export-YYYYMMDD-HHMMSS.json
 */
$config = require __DIR__ . '/config.php';
$key = trim((string)($config['api_key'] ?? ''));
$sid = trim((string)($config['steam_id'] ?? ''));
$delayMs = max(0, (int)($config['request_delay_ms'] ?? 200));
if ($key === '' || $key === 'YOUR_STEAM_WEB_API_KEY') { fwrite(STDERR, "Add your Steam Web API key to config.php\n"); exit(1); }
if (!preg_match('/^\d{17}$/', $sid)) { fwrite(STDERR, "Add your 17-digit SteamID64 to config.php\n"); exit(1); }
if (!function_exists('curl_init')) { fwrite(STDERR, "PHP cURL extension is required.\n"); exit(1); }

function getJson(string $url, int $timeout = 35): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>12, CURLOPT_TIMEOUT=>$timeout, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_USERAGENT=>'SteamPersonalDataExporter/2.0', CURLOPT_HTTPHEADER=>['Accept: application/json']]);
    $body = curl_exec($ch); $err = curl_error($ch); $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($body === false) throw new RuntimeException('Request failed: '.$err);
    if ($status < 200 || $status >= 300) throw new RuntimeException("HTTP {$status}: ".substr((string)$body, 0, 250));
    $json = json_decode((string)$body, true);
    if (!is_array($json)) throw new RuntimeException('Response was not valid JSON.');
    return $json;
}
function api(string $interface, string $method, array $params, string $key): array {
    $params['key'] = $key; $params['format'] = 'json';
    return getJson('https://api.steampowered.com/'.$interface.'/'.$method.'/v1/?'.http_build_query($params));
}
function attempt(callable $fn, string $section, array &$errors): ?array {
    try { return $fn(); } catch (Throwable $e) { $errors[] = ['section'=>$section, 'error'=>$e->getMessage()]; return null; }
}
function pause(int $ms): void { if ($ms > 0) usleep($ms * 1000); }

$errors = [];
$out = [
    'exported_at_utc'=>gmdate('c'), 'steam_id64'=>$sid,
    'data'=>[], 'errors'=>[],
    'limitations'=>[
        'Only data exposed by Steam APIs and public Steam Store endpoints can be collected. Private profile/game settings can hide data.',
        'Steam does not provide a complete purchase/transaction history, a full session-by-session play history, or every title-specific statistic through the public Web API.',
        'Inventory contents are not included by default because inventories vary by game/context and can be large. Some inventories may be private.',
        'Friends list access is restricted by Steam privacy and API access rules.',
        'A game may not expose achievements or stats. Failures are recorded in errors rather than treated as proof of zero achievements.',
    ],
];

// Profile and public account details.
$r = attempt(fn()=>api('ISteamUser','GetPlayerSummaries',['steamids'=>$sid],$key), 'profile', $errors);
$out['data']['profile'] = $r['response']['players'][0] ?? null;
$r = attempt(fn()=>api('ISteamUser','GetPlayerBans',['steamids'=>$sid],$key), 'ban_status', $errors);
$out['data']['ban_status'] = $r['players'][0] ?? null;
$r = attempt(fn()=>api('IPlayerService','GetSteamLevel',['steamid'=>$sid],$key), 'steam_level', $errors);
$out['data']['steam_level'] = $r['response']['player_level'] ?? null;
$r = attempt(fn()=>api('IPlayerService','GetBadges',['steamid'=>$sid],$key), 'badges', $errors);
$out['data']['badges'] = $r['response'] ?? null;

// Full library and playtime.
$params = ['steamid'=>$sid, 'include_appinfo'=>1, 'include_played_free_games'=>!empty($config['include_played_free_games'])?1:0, 'include_free_sub'=>1, 'include_extended_appinfo'=>1];
if (!empty($config['include_family_shared_games'])) $params['include_family_shared_games'] = 1;
$r = attempt(fn()=>api('IPlayerService','GetOwnedGames',$params,$key), 'owned_games', $errors);
$games = $r['response']['games'] ?? [];
$out['data']['owned_games'] = $games;
$total = 0; $twoWeeks = 0;
foreach ($games as $g) { $total += (int)($g['playtime_forever'] ?? 0); $twoWeeks += (int)($g['playtime_2weeks'] ?? 0); }
$out['data']['library_summary'] = ['game_count_returned'=>count($games), 'total_playtime_minutes'=>$total, 'total_playtime_hours'=>round($total/60,2), 'recent_playtime_minutes_from_library'=>$twoWeeks, 'recent_playtime_hours_from_library'=>round($twoWeeks/60,2)];
$r = attempt(fn()=>api('IPlayerService','GetRecentlyPlayedGames',['steamid'=>$sid,'count'=>0],$key), 'recently_played', $errors);
$out['data']['recently_played'] = $r['response']['games'] ?? [];

// Per-game achievements and game schemas (achievement names/descriptions/icons).
$maxAch = max(0,(int)($config['max_achievement_games'] ?? 0));
$gamesForAchievements = $maxAch === 0 ? $games : array_slice($games,0,$maxAch);
$achievements = []; $schemas = [];
foreach ($gamesForAchievements as $i=>$g) {
    $app = (int)($g['appid'] ?? 0); if ($app < 1) continue;
    $a = attempt(fn()=>api('ISteamUserStats','GetPlayerAchievements',['steamid'=>$sid,'appid'=>$app,'l'=>'english'],$key), 'achievements_'.$app, $errors);
    if ($a !== null) $achievements[(string)$app] = ['appid'=>$app,'name'=>$g['name'] ?? null,'playerstats'=>$a['playerstats'] ?? null];
    if (!empty($config['include_game_schemas'])) {
        $s = attempt(fn()=>api('ISteamUserStats','GetSchemaForGame',['appid'=>$app,'l'=>'english'],$key), 'game_schema_'.$app, $errors);
        if ($s !== null) $schemas[(string)$app] = ['appid'=>$app,'name'=>$g['name'] ?? null,'schema'=>$s['game'] ?? $s];
    }
    if (($i % 10) === 9) echo 'Processed achievement/schema data for '.($i+1).' games...'.PHP_EOL;
    pause($delayMs);
}
$out['data']['achievements_by_appid'] = $achievements;
$out['data']['game_schemas_by_appid'] = $schemas;
$out['data']['achievement_games_attempted'] = count($gamesForAchievements);

// Public Steam Store metadata. One request per app; configurable because this may take time.
if (!empty($config['include_store_details'])) {
    $maxStore = max(0,(int)($config['max_store_detail_games'] ?? 0));
    $storeGames = $maxStore === 0 ? $games : array_slice($games,0,$maxStore);
    $details = [];
    foreach ($storeGames as $i=>$g) {
        $app = (int)($g['appid'] ?? 0); if ($app < 1) continue;
        $d = attempt(fn()=>getJson('https://store.steampowered.com/api/appdetails?'.http_build_query(['appids'=>$app,'l'=>'english','cc'=>'au'])), 'store_details_'.$app, $errors);
        if ($d !== null && !empty($d[(string)$app]['success'])) $details[(string)$app] = $d[(string)$app]['data'] ?? null;
        if (($i+1) % 25 === 0) echo 'Processed Store details for '.($i+1).' games...'.PHP_EOL;
        pause($delayMs);
    }
    $out['data']['store_details_by_appid'] = $details;
    $out['data']['store_detail_games_attempted'] = count($storeGames);
}

// Keep error details in the export so missing/private/unsupported sections are visible.
$out['errors'] = $errors;
$dir = __DIR__.'/exports';
if (!is_dir($dir) && !mkdir($dir,0775,true) && !is_dir($dir)) { fwrite(STDERR,"Cannot create exports directory.\n"); exit(1); }
$file = $dir.'/steam-export-'.gmdate('Ymd-His').'.json';
$json = json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
if ($json === false || file_put_contents($file,$json.PHP_EOL) === false) { fwrite(STDERR,"Could not write JSON export.\n"); exit(1); }
echo "Export finished: {$file}\n";
echo 'Games returned: '.count($games)."\n";
echo 'Achievements returned for: '.count($achievements)." games\n";
echo 'Store details returned for: '.count($out['data']['store_details_by_appid'] ?? [])." games\n";
echo 'Errors recorded: '.count($errors)."\n";
