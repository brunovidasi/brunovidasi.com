<?php
/**
 * Full history of one of the current user's auctions: every scheduled bid, whether it
 * fired, what eBay said back, and the money involved. Reachable from the auction list
 * (both the live entries and the past-auctions table) — it's the only place a
 * non-admin can see the raw bid_log, which matters most after an auction is lost and
 * the question is "did my bid even go in?".
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
$auctionId = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM watched_auctions WHERE id = ?');
$stmt->execute([$auctionId]);
$auction = $stmt->fetch(PDO::FETCH_ASSOC);

// Someone else's auction is "not found" for a normal user; an admin gets sent to the
// admin view of it rather than a dead end.
if ($auction && (int) $auction['user_id'] !== (int) $user['id']) {
    if ($user['is_admin']) {
        redirect('admin_auction.php?id=' . $auctionId);
    }
    $auction = null;
}

if (!$auction) {
    http_response_code(404);
    $pageTitle = 'Auction not found';
    require __DIR__ . '/../includes/layout_top.php';
    echo '<h1>Auction not found</h1><p class="hint">It may have been removed. <a href="dashboard.php">Back to your auction list</a>.</p>';
    require __DIR__ . '/../includes/layout_bottom.php';
    exit;
}

$stepsStmt = db()->prepare('SELECT * FROM bid_steps WHERE watched_auction_id = ? ORDER BY seconds_before DESC');
$stepsStmt->execute([$auctionId]);
$steps = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);

$logStmt = db()->prepare('
    SELECT bl.*, bs.seconds_before, bs.max_bid
    FROM bid_log bl
    JOIN bid_steps bs ON bs.id = bl.bid_step_id
    WHERE bs.watched_auction_id = ?
    ORDER BY bl.attempted_at ASC, bl.id ASC
');
$logStmt->execute([$auctionId]);
$log = $logStmt->fetchAll(PDO::FETCH_ASSOC);

$currency = ebay_config()['currency'];
$homeCountry = marketplace_country_code(ebay_config()['marketplace_id']);

$topBid = bid_steps_top_amount($steps);
$hasEnded = $auction['end_time'] !== null && strtotime($auction['end_time']) < time();
$settled = in_array($auction['status'], ['won', 'lost'], true);
$editable = !$settled;
$outcome = bid_outcome_summary($steps, $auction['status']);
$estimate = estimate_landed_cost($topBid, $auction['shipping_cost'], $auction['item_country'], $homeCountry);
$events = auction_event_timeline($auction, $steps, $log, $currency);
$ebayUrl = ebay_item_view_url($auction['item_id'], ebay_config()['marketplace_id']);

$pageTitle = $auction['title'] ?? 'Auction';
require __DIR__ . '/../includes/layout_top.php';
?>
<p class="crumb"><a href="dashboard.php<?= $hasEnded ? '#past-auctions' : '' ?>">&larr; Your auction list</a></p>

<div class="detail-head">
    <?php if (!empty($auction['image_url'])): ?>
        <img class="detail-thumb" src="<?= htmlspecialchars($auction['image_url']) ?>" alt="">
    <?php endif; ?>
    <div>
        <h1><?= htmlspecialchars($auction['title'] ?? '(unknown title)') ?></h1>
        <p class="detail-links">
            <a href="<?= htmlspecialchars($ebayUrl) ?>" target="_blank" rel="noopener">View listing on eBay</a>
            <span class="sep">·</span>
            <span class="muted">Item <?= htmlspecialchars($auction['item_id']) ?></span>
            <?php if ($editable): ?>
                <span class="sep">·</span>
                <a href="edit_auction.php?id=<?= (int) $auction['id'] ?>">Edit bids</a>
            <?php endif; ?>
        </p>
    </div>
</div>

<?php if (!$hasEnded && $auction['end_time'] !== null): ?>
    <p class="countdown-row">
        <span class="countdown" data-countdown-end="<?= (int) strtotime($auction['end_time']) ?>"></span>
    </p>
<?php endif; ?>

<div class="detail-grid">
    <div>
        <span class="detail-label"><?= $hasEnded ? 'Ended' : 'Ends' ?></span>
        <?= $auction['end_time'] !== null ? local_time((int) strtotime($auction['end_time']), $auction['end_time']) : 'unknown' ?>
    </div>
    <div>
        <span class="detail-label">Status</span>
        <?php if ($settled || !$hasEnded): ?>
            <span class="status-<?= htmlspecialchars($auction['status']) ?>"><?= htmlspecialchars($auction['status']) ?></span>
        <?php else: ?>
            <span class="outcome-unknown" title="Ended while still marked “<?= htmlspecialchars($auction['status']) ?>” — the app doesn't check the final result on eBay.">unknown</span>
        <?php endif; ?>
    </div>
    <div>
        <span class="detail-label">Bid outcome</span>
        <span class="outcome-<?= htmlspecialchars($outcome['tone']) ?>"><?= htmlspecialchars($outcome['label']) ?></span>
    </div>
    <div>
        <span class="detail-label"><?= $hasEnded ? 'Last known price' : 'Current price' ?></span>
        <?= $auction['current_price'] !== null ? htmlspecialchars($currency . ' ' . number_format($auction['current_price'], 2)) : '—' ?>
    </div>
    <div><span class="detail-label">Highest scheduled bid</span><?= htmlspecialchars($currency . ' ' . number_format($topBid, 2)) ?></div>
    <div>
        <span class="detail-label">Shipping</span>
        <?= $auction['shipping_cost'] !== null ? htmlspecialchars($currency . ' ' . number_format($auction['shipping_cost'], 2)) : '—' ?>
    </div>
    <div><span class="detail-label">Ships from</span><?= htmlspecialchars($auction['item_country'] ?? 'unknown') ?></div>
    <div>
        <span class="detail-label">Est. landed cost</span>
        <?= htmlspecialchars($currency . ' ' . number_format($estimate['total'], 2)) ?>
    </div>
    <div><span class="detail-label">Added</span><?= local_time(db_time_epoch($auction['created_at']), db_time_local($auction['created_at']) ?? 'unknown') ?></div>
    <div><span class="detail-label">Price last checked</span><?= local_time(db_time_epoch($auction['price_checked_at']), db_time_local($auction['price_checked_at']) ?? 'never') ?></div>
</div>

<p class="hint">
    Est. landed cost assumes you pay your highest scheduled bid: <?= htmlspecialchars(number_format($topBid, 2)) ?>
    + shipping <?= htmlspecialchars(number_format($estimate['shipping'], 2)) ?>
    + buyer protection fee (est.) <?= htmlspecialchars(number_format($estimate['buyer_protection_fee'], 2)) ?>
    <?php if ($estimate['gst'] > 0): ?>
        + GST on import (est.) <?= htmlspecialchars(number_format($estimate['gst'], 2)) ?>
    <?php endif; ?>
    — all amounts in <?= htmlspecialchars($currency) ?>.
</p>

<?php if ($outcome['detail'] !== ''): ?>
    <p class="detail-message <?= $outcome['tone'] === 'danger' ? 'detail-message-danger' : '' ?>">
        <strong><?= htmlspecialchars($outcome['label']) ?>.</strong> <?= htmlspecialchars($outcome['detail']) ?>
    </p>
<?php endif; ?>

<?php if ($auction['result_message']): ?>
    <p class="detail-message"><strong>Last message:</strong> <?= htmlspecialchars($auction['result_message']) ?></p>
<?php endif; ?>

<div class="admin-section"><h2>What happened</h2></div>
<?php render_auction_timeline($events); ?>

<div class="admin-section"><h2>Scheduled bids (<?= count($steps) ?>)</h2></div>
<?php if (!$steps): ?>
    <p class="admin-empty">No bids were ever scheduled for this auction.</p>
<?php else: ?>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th class="num">Fires at</th>
                <th class="num">Max bid</th>
                <th>Status</th>
                <th>Fired at</th>
                <th>Result</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($steps as $s): ?>
                <tr>
                    <td class="num nowrap"><?= htmlspecialchars(format_seconds_before((int) $s['seconds_before'])) ?> before end</td>
                    <td class="num"><?= htmlspecialchars(bid_step_amount_text($s, $currency)) ?></td>
                    <td class="nowrap"><span class="status-<?= htmlspecialchars($s['status']) ?>"><?= htmlspecialchars($s['status']) ?></span></td>
                    <td class="nowrap muted"><?= local_time(db_time_epoch($s['fired_at']), db_time_local($s['fired_at']) ?? 'not fired') ?></td>
                    <td><?= $s['result_message'] ? htmlspecialchars($s['result_message']) : '<span class="muted">—</span>' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="admin-section"><h2>eBay bid attempts (<?= count($log) ?>)</h2></div>
<?php if (!$log): ?>
    <p class="admin-empty">
        No bid was ever sent to eBay for this auction.
        <?php if ($hasEnded && $steps): ?>Nothing reached eBay before it closed.<?php endif; ?>
    </p>
<?php else: ?>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Attempted at</th>
                <th class="num">Bid</th>
                <th>Outcome</th>
                <th>eBay response</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($log as $entry): ?>
                <tr>
                    <td class="nowrap muted"><?= local_time(db_time_epoch($entry['attempted_at']), db_time_local($entry['attempted_at']) ?? 'unknown') ?></td>
                    <td class="num"><?= htmlspecialchars(number_format($entry['max_bid'], 2)) ?> <span class="muted">@<?= htmlspecialchars(format_seconds_before((int) $entry['seconds_before'])) ?></span></td>
                    <td class="nowrap">
                        <span class="outcome-<?= $entry['success'] ? 'ok' : 'danger' ?>"><?= $entry['success'] ? 'accepted' : 'rejected' ?></span>
                    </td>
                    <td><?= $entry['response_summary'] ? htmlspecialchars($entry['response_summary']) : '<span class="muted">—</span>' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="detail-actions">
    <?php if ($editable): ?>
        <a class="entry-link" href="edit_auction.php?id=<?= (int) $auction['id'] ?>">Edit bids</a>
    <?php endif; ?>
    <a class="entry-link" href="<?= htmlspecialchars($ebayUrl) ?>" target="_blank" rel="noopener">View on eBay</a>
    <form method="post" action="delete_auction.php" data-confirm="Remove this auction from your auction list?">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $auction['id'] ?>">
        <button type="submit" class="link-btn">Remove</button>
    </form>
</div>

<p class="hint">
    "Scheduled bids" is what the cron job was told to do; "eBay bid attempts" is what was
    actually sent and what eBay said back. A scheduled bid with no matching attempt never
    left this app. Times are shown in your own local time zone.
</p>

<script src="assets/js/app.js"></script>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
