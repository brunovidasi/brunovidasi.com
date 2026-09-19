<?php
/**
 * The actual bid-firing pass, shared by the two things that can trigger it:
 * cron/snipe.php (host cron, when the host's crond works) and public/cron_http.php
 * (an external scheduler calling in over HTTPS, when it doesn't).
 *
 * Each auction can have up to 5 scheduled bid_steps (e.g. $50 at 10s before end,
 * $60 at 3s, $70 at 1s). A pass looks ahead 65 seconds — slightly more than the
 * one-minute trigger interval, so nothing between two runs is ever missed — and for
 * each step due in that window sleeps until its exact moment before firing.
 *
 * Steps due in the same pass are handled in chronological order; a later one can
 * fire a few seconds late if an earlier bid call is slow. Fine for a handful of
 * steps, would need parallel processes to do better.
 */

// The cron entrypoints don't go through bootstrap.php, so this file has to load what
// it uses itself — user_currency() below lives here.
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/email_templates.php';
require_once __DIR__ . '/bid_alerts.php';

const LOOKAHEAD_SECONDS = 65;

/**
 * Passes legitimately overlap: one that sleeps until an auction's final second is
 * still alive when the next minute's trigger arrives. This cap only stops a wedged
 * pass accumulating forever on a shared host.
 */
const MAX_RUNTIME_SECONDS = 180;

const MAX_LOG_BYTES = 2 * 1024 * 1024;

/**
 * Proves to the admin dashboard that the trigger is alive. Without it, a scheduler
 * that silently stopped looks exactly like a quiet period with no auctions due —
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

/** Keeps one previous generation so the log can't grow into the hosting quota. */
function rotate_log_if_large(): void
{
    $log = data_dir() . '/cron.log';
    if (is_file($log) && filesize($log) > MAX_LOG_BYTES) {
        @rename($log, $log . '.1');
    }
}

/**
 * A step firing successfully always makes the auction 'bid_placed'. A step failing
 * only downgrades the auction to 'failed' if no earlier step already succeeded — a
 * later failure (someone outbidding your highest step) shouldn't erase the fact
 * that you do have a live bid in from an earlier one.
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

/**
 * A full pass: fire what's due, then send the mail that produced.
 *
 * Email is never sent from inside the firing loop. A step fires the instant its second
 * arrives and the next may be one or two seconds behind it, so even a fast SMTP
 * handshake would push a bid late. Instead results are collected as they happen and
 * mailed when there is slack — while waiting on a step more than a few seconds away —
 * and once the pass is over.
 *
 * @param callable(string):void $log
 */
function run_snipe_pass(callable $log): void
{
    $outcomes = [];

    $sendMail = function () use (&$outcomes, $log): void {
        try {
            queue_bid_alerts($outcomes);
            $outcomes = [];
            flush_outbox();
        } catch (Throwable $e) {
            // Mail trouble must never be the reason a pass fails.
            $log('Email error: ' . $e->getMessage());
        }
    };

    try {
        fire_due_bids($log, $outcomes, $sendMail);
    } finally {
        $sendMail();
        try {
            queue_ebay_token_warnings(ebay_config()['environment']);
            flush_outbox();
        } catch (Throwable $e) {
            $log('Email error: ' . $e->getMessage());
        }
    }
}

/**
 * @param callable(string):void $log
 * @param array $outcomes collects one entry per step that finished, for bid_alerts.php
 * @param callable():void $sendMail queues and sends the mail collected so far
 */
function fire_due_bids(callable $log, array &$outcomes, callable $sendMail): void
{
    $startedAt = time();

    write_heartbeat();
    rotate_log_if_large();

    $horizon = date('Y-m-d H:i:s', time() + LOOKAHEAD_SECONDS);
    $now = date('Y-m-d H:i:s');

    $stmt = db()->prepare("
        SELECT bid_steps.*, watched_auctions.item_id AS auction_item_id, watched_auctions.user_id AS auction_user_id,
               watched_auctions.end_time AS auction_end_time, users.currency AS auction_user_currency
        FROM bid_steps
        JOIN watched_auctions ON watched_auctions.id = bid_steps.watched_auction_id
        JOIN users ON users.id = watched_auctions.user_id
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
        $log('No bids due in the next ' . LOOKAHEAD_SECONDS . 's.');
        return;
    }

    $client = new EbayClient();
    $ebayEnvironment = ebay_config()['environment'];

    $record = function (array $step, bool $ok, ?float $amount, string $message) use (&$outcomes): void {
        // A fixed bid that never got as far as eBay still has a known amount to report.
        if ($amount === null && ($step['bid_mode'] ?? 'fixed') === 'fixed' && $step['max_bid'] !== null) {
            $amount = (float) $step['max_bid'];
        }
        $outcomes[] = [
            'step_id' => (int) $step['id'],
            'auction_id' => (int) $step['watched_auction_id'],
            'seconds_before' => (int) $step['seconds_before'],
            'ok' => $ok,
            'amount' => $amount,
            'message' => $message,
        ];
    };

    foreach ($due as $step) {
        // Reset each time round: a value left from the previous step must never be
        // reported as this one's.
        $bidAmount = null;

        if (time() - $startedAt > MAX_RUNTIME_SECONDS) {
            $log('Runtime cap reached; leaving remaining steps to the next pass.');
            break;
        }

        $fireAt = strtotime($step['auction_end_time']) - (int) $step['seconds_before'];
        $sleepFor = $fireAt - time();

        if ($sleepFor > 0) {
            $plannedAmount = ($step['bid_mode'] ?? 'fixed') === 'anyway'
                ? 'current price + ' . $step['increment_amount'] . ($step['increment_type'] === 'percent' ? '%' : '')
                : (string) $step['max_bid'];
            // Idle time: the best moment to send mail, as nothing is about to fire.
            if ($sleepFor >= 8) {
                $sendMail();
            }
            $log("Sleeping {$sleepFor}s before bidding {$plannedAmount} on item {$step['auction_item_id']} (step #{$step['id']}, {$step['seconds_before']}s before end)");
            sleep($sleepFor);
        }

        // Filtered by environment so a sandbox token can never be used to attempt a
        // real bid (or vice versa) if a database is carried between environments.
        $tokenStmt = db()->prepare('SELECT auth_token FROM ebay_accounts WHERE user_id = ? AND environment = ?');
        $tokenStmt->execute([$step['auction_user_id'], $ebayEnvironment]);
        $authToken = $tokenStmt->fetchColumn();

        if (!$authToken) {
            $msg = "No eBay account connected for this user in the $ebayEnvironment environment; cannot bid.";
            $log("FAILED step #{$step['id']} (item {$step['auction_item_id']}): $msg");
            db()->prepare("UPDATE bid_steps SET status = 'failed', result_message = ?, fired_at = datetime('now') WHERE id = ?")
                ->execute([$msg, $step['id']]);
            update_auction_status_after_step((int) $step['watched_auction_id'], false, $msg);
            $record($step, false, $bidAmount, $msg);
            continue;
        }

        try {
            $lookup = $client->getItemByLegacyId($step['auction_item_id']);
        } catch (Throwable $e) {
            $lookup = null;
        }
        $currentPrice = $lookup['current_price'] ?? null;
        $bidMode = $step['bid_mode'] ?? 'fixed';

        if ($bidMode === 'anyway') {
            // Unlike a fixed step, there's nothing to bid without a live price to
            // add the increment to — this can't fall back to a preset amount.
            if ($currentPrice === null) {
                $msg = "Could not determine the item's current price; bid not placed.";
                $log("FAILED step #{$step['id']} (item {$step['auction_item_id']}): $msg");
                db()->prepare("UPDATE bid_steps SET status = 'failed', result_message = ?, fired_at = datetime('now') WHERE id = ?")
                    ->execute([$msg, $step['id']]);
                update_auction_status_after_step((int) $step['watched_auction_id'], false, $msg);
                $record($step, false, $bidAmount, $msg);
                continue;
            }

            $cap = $step['max_bid'] !== null ? (float) $step['max_bid'] : null;
            if ($cap !== null && $currentPrice >= $cap) {
                $msg = "Current price ($currentPrice) is already at or above the max value ($cap); bid not placed.";
                $log("SKIPPED step #{$step['id']} (item {$step['auction_item_id']}): $msg");
                db()->prepare("UPDATE bid_steps SET status = 'failed', result_message = ?, fired_at = datetime('now') WHERE id = ?")
                    ->execute([$msg, $step['id']]);
                db()->prepare('INSERT INTO bid_log (bid_step_id, success, response_summary) VALUES (?, 0, ?)')
                    ->execute([$step['id'], $msg]);
                update_auction_status_after_step((int) $step['watched_auction_id'], false, $msg);
                $record($step, false, $bidAmount, $msg);
                continue;
            }

            $bidAmount = $step['increment_type'] === 'percent'
                ? $currentPrice * (1 + ((float) $step['increment_amount'] / 100))
                : $currentPrice + (float) $step['increment_amount'];
            $bidAmount = round($bidAmount, 2);
            if ($cap !== null) {
                $bidAmount = min($bidAmount, $cap);
            }
        } else {
            $bidAmount = (float) $step['max_bid'];

            if ($currentPrice !== null && $currentPrice >= $bidAmount) {
                $msg = "Current price ({$currentPrice}) is already at or above the max bid ({$bidAmount}); bid not placed.";
                $log("SKIPPED step #{$step['id']} (item {$step['auction_item_id']}): $msg");
                db()->prepare("UPDATE bid_steps SET status = 'failed', result_message = ?, fired_at = datetime('now') WHERE id = ?")
                    ->execute([$msg, $step['id']]);
                db()->prepare('INSERT INTO bid_log (bid_step_id, success, response_summary) VALUES (?, 0, ?)')
                    ->execute([$step['id'], $msg]);
                update_auction_status_after_step((int) $step['watched_auction_id'], false, $msg);
                $record($step, false, $bidAmount, $msg);
                continue;
            }
        }

        try {
            $result = $client->placeBid($authToken, $step['auction_item_id'], $bidAmount, user_currency(['currency' => $step['auction_user_currency']]), $step['end_user_ip']);
        } catch (Throwable $e) {
            $result = ['success' => false, 'message' => $e->getMessage()];
        }

        $status = $result['success'] ? 'bid_placed' : 'failed';
        $log(($result['success'] ? 'OK' : 'FAILED') . " step #{$step['id']} (item {$step['auction_item_id']}): {$result['message']}");

        // max_bid is overwritten here with the amount actually attempted — for a
        // fixed step this just rewrites the same value, but for an 'anyway' step
        // this is the only record of what the dynamic formula above resolved to.
        db()->prepare("UPDATE bid_steps SET status = ?, max_bid = ?, result_message = ?, fired_at = datetime('now') WHERE id = ?")
            ->execute([$status, $bidAmount, $result['message'], $step['id']]);

        db()->prepare('INSERT INTO bid_log (bid_step_id, success, response_summary) VALUES (?, ?, ?)')
            ->execute([$step['id'], $result['success'] ? 1 : 0, $result['message']]);

        update_auction_status_after_step((int) $step['watched_auction_id'], $result['success'], $result['message']);
        $record($step, (bool) $result['success'], $bidAmount, (string) $result['message']);
    }
}
