<?php
/**
 * CLI trigger for a bid-firing pass, for hosts whose cron can run PHP:
 *   * * * * * /usr/local/bin/php /path/to/cron/snipe.php >> /path/to/data/cron.log 2>&1
 *
 * public/preflight.php prints the exact line with this server's real paths.
 * If the host's cron cannot run at all, public/cron_http.php does the same job
 * driven by an external scheduler.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/EbayClient.php';
require_once __DIR__ . '/../includes/runtime.php';
require_once __DIR__ . '/../includes/snipe_runner.php';

date_default_timezone_set(app_timezone());
configure_error_reporting();
set_time_limit(0);

run_snipe_pass(function (string $message): void {
    fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . "] $message\n");
});
