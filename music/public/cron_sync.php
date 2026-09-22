<?php
/**
 * The daily sync, triggered over HTTPS:
 *
 *   https://brunovida.si/music/cron_sync.php?token=…
 *
 * Give that URL to any scheduler that can fetch a page once a day (the host's
 * cron with curl/wget, or an external pinger if the host's cron can't run PHP —
 * cron/sync.php is the CLI equivalent for hosts that can).
 *
 * Protected by a shared secret in the config's 'cron_token'. Without one set,
 * the endpoint stays closed: a sync burns API calls against a rate limit, so it
 * must never be startable anonymously.
 *
 * The response is sent and closed immediately, before the work runs, because a
 * full sync takes minutes and no scheduler waits that long. ignore_user_abort
 * keeps it going after the caller has hung up.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/runtime.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/fields.php';
require_once __DIR__ . '/../includes/items.php';
require_once __DIR__ . '/../includes/DiscogsClient.php';
require_once __DIR__ . '/../includes/sync.php';

date_default_timezone_set(app_timezone());
configure_error_reporting();

header('Content-Type: text/plain; charset=utf-8');
// Nothing here should ever be cached or indexed by anything.
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

$expected = cron_token();
$given = (string) ($_GET['token'] ?? $_SERVER['HTTP_X_CRON_TOKEN'] ?? '');

if ($expected === '') {
    http_response_code(503);
    exit("No cron_token is configured; this endpoint is disabled.\n");
}

// hash_equals is constant-time, so a wrong token can't be discovered byte by
// byte from response timing. The lengths are compared first because it is not
// safe on differing lengths.
if (strlen($given) !== strlen($expected) || !hash_equals($expected, $given)) {
    http_response_code(403);
    exit("Forbidden.\n");
}

ignore_user_abort(true);
set_time_limit(0);

$body = "Sync started at " . date('Y-m-d H:i:s') . ".\n";
header('Content-Length: ' . strlen($body));
header('Connection: close');
echo $body;

// Close the HTTP connection so the scheduler sees a fast 200, then keep working.
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    @ob_end_flush();
    @flush();
}

$logFile = data_dir() . '/sync.log';

// Fifteen minutes is far more than a nightly top-up needs (only what changed
// gets fetched), and still leaves a first-ever sync of a large collection to be
// finished by the next night's run rather than hammering the API in one go.
$state = run_full_sync('cron', function (string $message) use ($logFile): void {
    @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] [cron] $message\n", FILE_APPEND);
}, 900.0);

@file_put_contents(
    $logFile,
    '[' . date('Y-m-d H:i:s') . '] [cron] finished: ' . $state['status'] . ' — ' . $state['message'] . "\n",
    FILE_APPEND
);
