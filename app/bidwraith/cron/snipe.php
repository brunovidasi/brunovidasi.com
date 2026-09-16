<?php
/**
 * Run this from a host cron job every 1 minute. public/preflight.php prints the
 * exact line to paste, with this server's real PHP binary and absolute paths.
 *
 * Each auction can have up to 5 scheduled bid_steps (e.g. $50 at 10s before end, $60
 * at 3s before, $70 at 1s before). This script looks ahead 65 seconds (a bit more
 * than the cron interval, so nothing between two runs is ever missed), and for each
 * step due in that window it sleeps until its exact configured moment before firing.
 *
 * Multiple steps for the same auction (or different auctions) due in the same run
 * are handled one after another in chronological order — a later one could fire a
 * few seconds late if an earlier one's bid call is slow. Fine for a handful of
 * steps; would need background processes to fully parallelize.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/EbayClient.php';
require_once __DIR__ . '/../includes/runtime.php';

date_default_timezone_set(app_timezone());
configure_error_reporting();
set_time_limit(0);

const LOOKAHEAD_SECONDS = 65;

/**
 * Runs legitimately overlap: a run that sleeps until an auction's final second is
 * still alive when the next minute's run starts. This cap only exists so a run that
 * somehow wedges can't accumulate forever on a shared host.
 */
const MAX_RUNTIME_SECONDS = 180;

const MAX_LOG_BYTES = 2 * 1024 * 1024;

$startedAt = time();

function log_line(string $msg): void
{
    fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . "] $msg\n");
}

/**
 * Proves to the admin dashboard that cron is actually firing. Without this, a cron
 * job that silently stopped (wrong PHP path after a host upgrade, disabled by the
 * panel, quota exceeded) looks identical to a quiet period with no auctions due —
 * and you'd only find out by losing an auction.
 */
function write_heartbeat(): void
{
    $dir = data_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }

    @file_put_contents($dir . '/cron-heartbeat.txt', (string) time());
}

/**
 * The cron line appends stdout to cron.log. Left alone it grows without bound and
 * eventually eats the hosting quota, so keep one previous generation and start over.
 */
function rotate_log_if_large(): void
{
    $log = data_dir() . '/cron.log';
    if (is_file($log) && filesize($log) > MAX_LOG_BYTES) {
        @rename($log, $log . '.1');
    }
}

write_heartbeat();
rotate_log_if_large();

/**
 * A step firing successfully always makes the auction 'bid_placed'. A step failing
 * only downgrades the auction to 'failed' if no earlier step already succeeded —
 * a later failure (e.g. someone outbid your last, highest step) shouldn't erase the
 * fact that you do have a live bid in from an earlier step.
 */
function update_auction_status_after_step(int $auctionId, bool $success, string $message): void
{
    if ($success) {
        db()->prepare("UPDATE watched_auctions SET status = 'bid_placed', result_message = ?, last_checked_at = datetime('now') WHERE id = ?")
            ->execute([$message, $auctionId]);
        return;
    }

    $current = db()->prepare('SELECT status FROM watched_auctions WHERE id = ?');
    $current->execute([$auctionId]);

    if ($current->fetchColumn() !== 'bid_placed') {
        db()->prepare("UPDATE watched_auctions SET status = 'failed', result_message = ?, last_checked_at = datetime('now') WHERE id = ?")
            ->execute([$message, $auctionId]);
    } else {
        db()->prepare("UPDATE watched_auctions SET last_checked_at = datetime('now') WHERE id = ?")->execute([$auctionId]);
    }
}

$horizon = date('Y-m-d H:i:s', time() + LOOKAHEAD_SECONDS);
$now = date('Y-m-d H:i:s');

$stmt = db()->prepare("
    SELECT bid_steps.*, watched_auctions.item_id AS auction_item_id, watched_auctions.user_id AS auction_user_id,
           watched_auctions.end_time AS auction_end_time
    FROM bid_steps
    JOIN watched_auctions ON watched_auctions.id = bid_steps.watched_auction_id
    WHERE bid_steps.status = 'pending'
      AND watched_auctions.end_time IS NOT NULL
      AND watched_auctions.status NOT IN ('won', 'lost')
      AND datetime(watched_auctions.end_time, '-' || bid_steps.seconds_before || ' seconds') <= ?
      AND datetime(watched_auctions.end_time, '-' || bid_steps.seconds_before || ' seconds') > ?
    ORDER BY datetime(watched_auctions.end_time, '-' || bid_steps.seconds_before || ' seconds') ASC
");
$stmt->execute([$horizon, $now]);
$due = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$due) {
    log_line('No bids due in the next ' . LOOKAHEAD_SECONDS . 's.');
    exit;
}

$client = new EbayClient();
$ebayEnvironment = ebay_config()['environment'];

foreach ($due as $step) {
    if (time() - $startedAt > MAX_RUNTIME_SECONDS) {
        log_line('Runtime cap reached; leaving remaining steps to the next run.');
        break;
    }

    $fireAt = strtotime($step['auction_end_time']) - (int) $step['seconds_before'];
    $sleepFor = $fireAt - time();

    if ($sleepFor > 0) {
        log_line("Sleeping {$sleepFor}s before bidding {$step['max_bid']} on item {$step['auction_item_id']} (step #{$step['id']}, {$step['seconds_before']}s before end)");
        sleep($sleepFor);
    }

    // Filtered by environment so a sandbox token can never be used to attempt a real
    // bid (or vice versa) if a database is ever carried between environments.
    $tokenStmt = db()->prepare('SELECT auth_token FROM ebay_accounts WHERE user_id = ? AND environment = ?');
    $tokenStmt->execute([$step['auction_user_id'], $ebayEnvironment]);
    $authToken = $tokenStmt->fetchColumn();

    if (!$authToken) {
        $msg = "No eBay account connected for this user in the $ebayEnvironment environment; cannot bid.";
        log_line("FAILED step #{$step['id']} (item {$step['auction_item_id']}): $msg");
        db()->prepare("UPDATE bid_steps SET status = 'failed', result_message = ?, fired_at = datetime('now') WHERE id = ?")
            ->execute([$msg, $step['id']]);
        update_auction_status_after_step((int) $step['watched_auction_id'], false, $msg);
        continue;
    }

    try {
        $result = $client->placeBid($authToken, $step['auction_item_id'], (float) $step['max_bid']);
    } catch (Throwable $e) {
        $result = ['success' => false, 'message' => $e->getMessage()];
    }

    $status = $result['success'] ? 'bid_placed' : 'failed';
    log_line(($result['success'] ? 'OK' : 'FAILED') . " step #{$step['id']} (item {$step['auction_item_id']}): {$result['message']}");

    db()->prepare("UPDATE bid_steps SET status = ?, result_message = ?, fired_at = datetime('now') WHERE id = ?")
        ->execute([$status, $result['message'], $step['id']]);

    db()->prepare('INSERT INTO bid_log (bid_step_id, success, response_summary) VALUES (?, ?, ?)')
        ->execute([$step['id'], $result['success'] ? 1 : 0, $result['message']]);

    update_auction_status_after_step((int) $step['watched_auction_id'], $result['success'], $result['message']);
}
