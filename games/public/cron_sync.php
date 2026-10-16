<?php

/**
 * The scheduled sync over HTTPS, for any scheduler that can open an address:
 *
 *   https://brunovida.si/games/cron_sync.php?token=…              every platform ticked in Settings → Sync
 *   https://brunovida.si/games/cron_sync.php?token=…&platform=psn  one
 *
 * Closed until Settings → Sync makes a token, since a sync spends real API
 * calls. It answers at once and keeps working after the caller hangs up.
 * cron/sync.php does the same from the command line.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

$expected = (string) credential('cron', 'token');
$given = $_GET['token'] ?? $_SERVER['HTTP_X_CRON_TOKEN'] ?? '';
$given = is_string($given) ? $given : '';

if ($expected === '') {
    http_response_code(503);
    exit("No cron token is set; this address is off (Settings → Sync).\n");
}

if (strlen($given) !== strlen($expected) || !hash_equals($expected, $given)) {
    http_response_code(403);
    exit("Forbidden.\n");
}

$one = $_GET['platform'] ?? null;
if ($one !== null && !isset(PLATFORMS[$one])) {
    http_response_code(400);
    exit("No such platform.\n");
}
$platforms = $one !== null ? [$one] : (setting('sync', [])['cron_platforms'] ?? array_keys(PLATFORMS));

ignore_user_abort(true);
@set_time_limit(0);

$body = 'Sync of ' . implode(', ', $platforms) . ' started at ' . date('Y-m-d H:i:s') . ".\n";
header('Content-Length: ' . strlen($body));
header('Connection: close');
echo $body;

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    @ob_end_flush();
    @flush();
}

$log = fn (string $message) => @file_put_contents(data_dir() . '/sync.log', '[' . date('Y-m-d H:i:s') . "] [cron] $message\n", FILE_APPEND);

foreach (run_all_syncs('cron', $platforms) as $platform => $result) {
    $log("$platform: {$result['status']} — {$result['message']}");
}
