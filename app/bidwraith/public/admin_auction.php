<?php
require_once __DIR__ . '/../includes/bootstrap.php';

require_admin();

$auctionId = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('
    SELECT wa.*, u.email AS owner_email, u.id AS owner_id
    FROM watched_auctions wa
    JOIN users u ON u.id = wa.user_id
    WHERE wa.id = ?
');
$stmt->execute([$auctionId]);
$auction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$auction) {
    http_response_code(404);
    $pageTitle = 'Auction not found';
    require __DIR__ . '/../includes/layout_top.php';
    echo '<h1>Auction not found</h1><p class="hint"><a href="admin.php">Back to the admin dashboard</a></p>';
    require __DIR__ . '/../includes/layout_bottom.php';
    exit;
}

$stepsStmt = db()->prepare('SELECT * FROM bid_steps WHERE watched_auction_id = ? ORDER BY seconds_before DESC');
$stepsStmt->execute([$auctionId]);
$steps = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);

// The bid_log is the raw record of what eBay said on each attempt — audit history
// that's never been surfaced anywhere in the UI before.
$logStmt = db()->prepare('
    SELECT bl.*, bs.seconds_before, bs.max_bid
    FROM bid_log bl
    JOIN bid_steps bs ON bs.id = bl.bid_step_id
    WHERE bs.watched_auction_id = ?
    ORDER BY bl.attempted_at DESC
');
$logStmt->execute([$auctionId]);
$log = $logStmt->fetchAll(PDO::FETCH_ASSOC);

$topBid = $steps ? max(array_column($steps, 'max_bid')) : 0.0;
$hasEnded = $auction['end_time'] !== null && $auction['end_time'] < date('Y-m-d H:i:s');
$outcome = $hasEnded ? bid_outcome_summary($steps, $auction['status']) : null;

$currency = ebay_config()['currency'];
$homeCountry = marketplace_country_code(ebay_config()['marketplace_id']);
$estimate = estimate_landed_cost($topBid, $auction['shipping_cost'], $auction['item_country'], $homeCountry);

$pageTitle = $auction['title'] ?? 'Auction';
require __DIR__ . '/../includes/layout_top.php';
?>
<p class="crumb"><a href="admin.php">&larr; Admin dashboard</a></p>

<h1><?= htmlspecialchars($auction['title'] ?? '(unknown title)') ?></h1>

<div class="detail-grid">
    <div><span class="detail-label">Item ID</span><?= htmlspecialchars($auction['item_id']) ?></div>
    <div>
        <span class="detail-label">Owner</span>
        <a href="admin_user.php?id=<?= (int) $auction['owner_id'] ?>"><?= htmlspecialchars($auction['owner_email']) ?></a>
    </div>
    <div>
        <span class="detail-label"><?= $hasEnded ? 'Ended' : 'Ends' ?></span>
        <?= $auction['end_time'] !== null ? local_time((int) strtotime($auction['end_time']), $auction['end_time']) : 'unknown' ?>
    </div>
    <div>
        <span class="detail-label">Status</span>
        <span class="status-<?= htmlspecialchars($auction['status']) ?>"><?= htmlspecialchars($auction['status']) ?></span>
    </div>
    <?php if ($outcome): ?>
        <div>
            <span class="detail-label">Bid outcome</span>
            <span class="outcome-<?= htmlspecialchars($outcome['tone']) ?>"><?= htmlspecialchars($outcome['label']) ?></span>
        </div>
    <?php endif; ?>
    <div>
        <span class="detail-label">Current price</span>
        <?= $auction['current_price'] !== null ? htmlspecialchars($currency . ' ' . number_format($auction['current_price'], 2)) : '—' ?>
    </div>
    <div>
        <span class="detail-label">Shipping</span>
        <?= $auction['shipping_cost'] !== null ? htmlspecialchars($currency . ' ' . number_format($auction['shipping_cost'], 2)) : '—' ?>
    </div>
    <div><span class="detail-label">Ships from</span><?= htmlspecialchars($auction['item_country'] ?? 'unknown') ?></div>
    <div><span class="detail-label">Top scheduled bid</span><?= htmlspecialchars($currency . ' ' . number_format($topBid, 2)) ?></div>
    <div><span class="detail-label">Est. landed cost</span><?= htmlspecialchars($currency . ' ' . number_format($estimate['total'], 2)) ?></div>
    <div><span class="detail-label">Added</span><?= local_time(db_time_epoch($auction['created_at']), db_time_local($auction['created_at']) ?? 'unknown') ?></div>
    <div><span class="detail-label">Price checked</span><?= local_time(db_time_epoch($auction['price_checked_at']), db_time_local($auction['price_checked_at']) ?? 'never') ?></div>
    <div><span class="detail-label">Last cron touch</span><?= local_time(db_time_epoch($auction['last_checked_at']), db_time_local($auction['last_checked_at']) ?? 'never') ?></div>
</div>

<?php if ($auction['result_message']): ?>
    <p class="detail-message"><?= htmlspecialchars($auction['result_message']) ?></p>
<?php endif; ?>

<?php if ($outcome && $outcome['detail'] !== ''): ?>
    <p class="detail-message <?= $outcome['tone'] === 'danger' ? 'detail-message-danger' : '' ?>">
        <strong><?= htmlspecialchars($outcome['label']) ?>.</strong> <?= htmlspecialchars($outcome['detail']) ?>
    </p>
<?php endif; ?>

<div class="admin-section"><h2>What happened</h2></div>
<?php render_auction_timeline(auction_event_timeline($auction, $steps, $log, $currency)); ?>

<div class="admin-section"><h2>Scheduled bids (<?= count($steps) ?>)</h2></div>
<?php if (!$steps): ?>
    <p class="admin-empty">No bids scheduled for this auction.</p>
<?php else: ?>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th class="num">Fires at</th>
                <th class="num">Max bid</th>
                <th>Status</th>
                <th>Fired at</th>
                <th>Result from eBay</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($steps as $s): ?>
                <tr>
                    <td class="num nowrap"><?= (int) $s['seconds_before'] ?>s before end</td>
                    <td class="num"><?= htmlspecialchars(number_format($s['max_bid'], 2)) ?></td>
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
        No bid has been sent to eBay for this auction yet.
        <?php if ($hasEnded): ?>The auction has already ended.<?php endif; ?>
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
                    <td class="num"><?= htmlspecialchars(number_format($entry['max_bid'], 2)) ?> <span class="muted">@<?= (int) $entry['seconds_before'] ?>s</span></td>
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

<p class="hint">
    Prices shown in <?= htmlspecialchars($currency) ?>, times in your own local time zone.
    "Scheduled bids" is what the cron job was told to do; "eBay bid attempts" is what was actually
    sent and what eBay said back.
</p>

<script src="assets/js/app.js"></script>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
