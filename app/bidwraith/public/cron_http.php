<?php
/**
 * HTTPS trigger for a bid-firing pass, for when the host's cron daemon does not run
 * this account's jobs at all — as is the case here. An external scheduler (any
 * service that can fetch a URL once a minute) calls this instead.
 *
 * Protected by a shared secret in the config's 'cron_token'. Without one set, the
 * endpoint stays closed: it must never be possible to trigger bidding anonymously.
 *
 * The response is sent and closed immediately, before the pass runs, because a pass
 * can sleep up to a minute waiting for an auction's final seconds and no scheduler
 * would wait that long. ignore_user_abort keeps the work going after the caller has
 * hung up.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/EbayClient.php';
require_once __DIR__ . '/../includes/runtime.php';
require_once __DIR__ . '/../includes/snipe_runner.php';

date_default_timezone_set(app_timezone());
configure_error_reporting();

header('Content-Type: text/plain; charset=utf-8');
// Nothing here should ever be cached or indexed by anything.
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

$expected = (string) (app_config()['cron_token'] ?? '');
$given = (string) ($_GET['token'] ?? $_SERVER['HTTP_X_CRON_TOKEN'] ?? '');

if ($expected === '') {
    http_response_code(503);
    exit("No cron_token is configured; this endpoint is disabled.\n");
}

// hash_equals is constant-time, so a wrong token can't be discovered byte by byte
// from response timing. The lengths are compared first because it is not safe on
// differing lengths.
if (strlen($given) !== strlen($expected) || !hash_equals($expected, $given)) {
    http_response_code(403);
    exit("Forbidden.\n");
}

ignore_user_abort(true);
set_time_limit(0);

$body = "Bid pass started at " . date('Y-m-d H:i:s') . ".\n";
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

$logFile = data_dir() . '/cron.log';

run_snipe_pass(function (string $message) use ($logFile): void {
    @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] [http] $message\n", FILE_APPEND);
});
