<?php

/**
 * The daily sync over HTTPS, for any scheduler that can fetch a URL:
 *
 *   https://brunovida.si/music/cron_sync.php?token=…
 *
 * Closed unless the config has a cron_token, since a sync spends real API
 * calls. It answers at once and keeps working after the caller hangs up.
 * cron/sync.php does the same from the command line.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

$expected = cron_token();
$given = $_GET['token'] ?? $_SERVER['HTTP_X_CRON_TOKEN'] ?? '';
$given = is_string($given) ? $given : '';

if ($expected === '') {
    http_response_code(503);
    exit("No cron_token is configured; this endpoint is disabled.\n");
}

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

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    @ob_end_flush();
    @flush();
}

$log = function (string $message): void {
    @file_put_contents(data_dir() . '/sync.log', '[' . date('Y-m-d H:i:s') . "] [cron] $message\n", FILE_APPEND);
};

// Fifteen minutes; a first sync of a big collection finishes over the next nights.
$state = run_full_sync('cron', $log, 900.0);

$log('finished: ' . $state['status'] . ' — ' . $state['message']);
