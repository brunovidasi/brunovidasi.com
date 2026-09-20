<?php
/**
 * CLI sync, for hosts whose cron can run PHP:
 *
 *   17 4 * * * /usr/local/bin/php /path/to/cron/sync.php >> /path/to/data/sync.log 2>&1
 *
 * If the host's cron cannot run PHP (or cannot run at all), public/cron_sync.php
 * does the same job driven by anything that can fetch a URL.
 *
 * Also useful by hand:  php cron/sync.php --budget=120
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/runtime.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/fields.php';
require_once __DIR__ . '/../includes/items.php';
require_once __DIR__ . '/../includes/DiscogsClient.php';
require_once __DIR__ . '/../includes/sync.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is for the command line. Use cron_sync.php over HTTP instead.\n");
}

date_default_timezone_set(app_timezone());
configure_error_reporting();
set_time_limit(0);

$options = getopt('', ['budget::', 'help']);

if (isset($options['help'])) {
    fwrite(STDOUT, <<<TEXT
    Sync the Discogs collection into the local database.

      --budget=SECONDS   stop after this long (default 900). The run resumes on
                         the next pass, so a short budget is safe.

    TEXT);
    exit(0);
}

$budget = (float) ($options['budget'] ?? 900);

$state = run_full_sync('cli', function (string $message): void {
    fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . "] $message\n");
}, $budget);

fwrite(STDOUT, sprintf(
    "[%s] finished: %s — %s\n",
    date('Y-m-d H:i:s'),
    $state['status'],
    $state['message']
));

// A failed sync should fail the cron job too, so a host that mails about
// non-zero exits actually says something.
exit($state['status'] === 'error' ? 1 : 0);
