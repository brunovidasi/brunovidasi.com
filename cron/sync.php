<?php

/**
 * The scheduled sync from the command line, for a host whose cron runs PHP:
 *
 *   php cron/sync.php               every platform ticked in Settings → Sync
 *   php cron/sync.php steam psn     those
 *
 * The same as public/cron_sync.php, without an address or a token.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Command line only.\n");
}

require_once __DIR__ . '/../includes/bootstrap.php';

$platforms = array_slice($argv, 1) ?: (setting('sync', [])['cron_platforms'] ?? array_keys(PLATFORMS));
foreach ($platforms as $platform) {
    if (!isset(PLATFORMS[$platform])) {
        fwrite(STDERR, "No such platform: $platform (" . implode(', ', array_keys(PLATFORMS)) . ")\n");
        exit(1);
    }
}

$failed = false;
foreach ($platforms as $platform) {
    $result = run_sync($platform, 'cli');
    echo '[' . date('Y-m-d H:i:s') . "] $platform: {$result['status']} — {$result['message']}\n";
    $failed = $failed || $result['status'] === 'error';
}

exit($failed ? 1 : 0);
