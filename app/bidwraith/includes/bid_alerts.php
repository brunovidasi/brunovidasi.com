<?php
/**
 * Emails driven by the bid-firing pass: what happened to a user's bids, and a warning
 * when their eBay connection is about to stop working. Both only *queue* mail — the
 * pass sends it when it is idle or finished (see run_snipe_pass), so nothing here can
 * delay a bid.
 */

/**
 * One email per auction summarising the bids that just fired for it. Grouping matters:
 * a three-step ladder fires within three seconds, and three emails for that is noise.
 *
 * @param array<int, array{step_id: int, auction_id: int, seconds_before: int, ok: bool, amount: ?float, message: string}> $outcomes
 */
function queue_bid_alerts(array $outcomes): void
{
    $byAuction = [];
    foreach ($outcomes as $outcome) {
        $byAuction[$outcome['auction_id']][] = $outcome;
    }

    $stmt = db()->prepare('
        SELECT wa.id, wa.title, wa.item_id, wa.end_time, u.id AS user_id, u.email, u.currency, u.email_bid_alerts
        FROM watched_auctions wa JOIN users u ON u.id = wa.user_id
        WHERE wa.id = ?
    ');

    foreach ($byAuction as $auctionId => $group) {
        $stmt->execute([$auctionId]);
        $auction = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$auction || !$auction['email_bid_alerts']) {
            continue;
        }

        $template = email_tpl_bid_alert($auction, $group, user_currency(['currency' => $auction['currency']]));
        $key = 'bids:' . implode(',', array_column($group, 'step_id'));
        queue_email($auction['email'], $template, 'bid_alert', (int) $auction['user_id'], $key);
    }
}

/**
 * Warns people whose eBay authorization is expiring (or already has) *and who have bids
 * waiting on it* — a lapsed connection means every one of those bids silently fails.
 * Someone with nothing scheduled isn't bothered. Runs every pass; the dedupe key (user
 * + this particular expiry) means each person hears once per expiry, and again only
 * after they reconnect and it comes round again.
 */
function queue_ebay_token_warnings(string $environment, int $warnDays = 7): void
{
    $stmt = db()->prepare("
        SELECT ea.user_id, ea.token_expires_at, u.email
        FROM ebay_accounts ea JOIN users u ON u.id = ea.user_id
        WHERE u.is_active = 1 AND ea.environment = ? AND ea.token_expires_at IS NOT NULL
          AND EXISTS (
              SELECT 1 FROM watched_auctions wa JOIN bid_steps bs ON bs.watched_auction_id = wa.id
              WHERE wa.user_id = ea.user_id AND bs.status = 'pending' AND wa.end_time > ?
          )
    ");
    $stmt->execute([$environment, date('Y-m-d H:i:s')]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $expires = strtotime($row['token_expires_at']);
        if ($expires === false) {
            continue;
        }

        $daysLeft = (int) ceil(($expires - time()) / 86400);
        if ($daysLeft > $warnDays) {
            continue;
        }

        $expired = $daysLeft <= 0;
        queue_email(
            $row['email'],
            email_tpl_ebay_expiring($daysLeft, $row['token_expires_at']),
            $expired ? 'ebay_expired' : 'ebay_expiring',
            (int) $row['user_id'],
            ($expired ? 'ebay_expired:' : 'ebay_expiring:') . $row['user_id'] . ':' . $row['token_expires_at']
        );
    }
}
