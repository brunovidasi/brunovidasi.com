<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();

$now = date('Y-m-d H:i:s');
// Auctions stay in the detailed list for a day after they close so the result is still
// visible up top; only after that grace period do they drop into the past-auctions table.
$pastCutoff = date('Y-m-d H:i:s', strtotime('-1 day'));

$stmt = db()->prepare('SELECT * FROM watched_auctions WHERE user_id = ? AND (end_time IS NULL OR end_time >= ?) ORDER BY end_time IS NULL, end_time ASC');
$stmt->execute([$user['id'], $pastCutoff]);
$auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pastAllowedSorts = ['item', 'end', 'status', 'result', 'price', 'topbid'];
[$pastSortKey, $pastSortDir] = resolve_sort(get_param('psort'), get_param('pdir'), $pastAllowedSorts, 'end', 'desc');
$pastStmt = db()->prepare('
    SELECT wa.* FROM watched_auctions wa
    WHERE wa.user_id = ? AND wa.end_time IS NOT NULL AND wa.end_time < ?
    ORDER BY ' . watched_auction_order_sql($pastSortKey, $pastSortDir) . ', wa.id DESC
');
$pastStmt->execute([$user['id'], $pastCutoff]);
$pastRows = $pastStmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = db()->prepare('SELECT 1 FROM ebay_accounts WHERE user_id = ?');
$stmt2->execute([$user['id']]);
$hasEbayAccount = (bool) $stmt2->fetchColumn();

// Refresh live price/shipping data for auctions we haven't finished with yet.
// Best-effort: a lookup failure just leaves the last-known values on screen.
$client = new EbayClient();
foreach ($auctions as &$a) {
    if (!in_array($a['status'], ['pending', 'bid_placed'], true)) {
        continue;
    }
    try {
        $lookup = $client->getItemByLegacyId($a['item_id']);
        if ($lookup) {
            $imageUrl = $lookup['image_url'] ?? $a['image_url'];
            db()->prepare('
                UPDATE watched_auctions
                SET current_price = ?, shipping_cost = ?, item_country = ?, image_url = ?, price_checked_at = datetime(\'now\')
                WHERE id = ?
            ')->execute([$lookup['current_price'], $lookup['shipping_cost'], $lookup['item_country'], $imageUrl, $a['id']]);
            $a['current_price'] = $lookup['current_price'];
            $a['shipping_cost'] = $lookup['shipping_cost'];
            $a['item_country'] = $lookup['item_country'];
            $a['image_url'] = $imageUrl;
        }
    } catch (Throwable $e) {
        // Keep showing the last-known values.
    }
}
unset($a);

$stepsStmt = db()->prepare('SELECT * FROM bid_steps WHERE watched_auction_id = ? ORDER BY seconds_before DESC');

foreach ($pastRows as &$pastRow) {
    $stepsStmt->execute([$pastRow['id']]);
    $pastRow['steps'] = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($pastRow);

$pageTitle = 'Auction list';
$currency = user_currency($user);
$homeCountry = marketplace_country_code(ebay_config()['marketplace_id']);
require __DIR__ . '/../includes/layout_top.php';
?>
<div class="page-header">
    <h1>Your auction list</h1>
    <a class="btn" href="add_auction">+ Add auction</a>
</div>

<?php if (!$hasEbayAccount): ?>
    <div class="flash flash-error">
        You haven't connected an eBay account yet, so bids can't be placed.
        <a href="connect_ebay">Connect it now</a>.
    </div>
<?php endif; ?>

<?php if (empty($auctions)): ?>
    <p class="hint"><?= $pastRows ? 'No auctions still running.' : 'No auctions yet. Add one by eBay item ID and set your max bid.' ?></p>
<?php else: ?>
<div class="entries" data-server-now="<?= time() ?>">
    <?php foreach ($auctions as $a):
        $stepsStmt->execute([$a['id']]);
        $steps = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);
        $effectiveMaxBid = bid_steps_top_amount($steps);
        // An uncapped, still-pending "anyway" step has no ceiling to be outbid past,
        // so the usual "current price already at/above your max" warning doesn't apply.
        $hasUncappedAnyway = (bool) array_filter(
            $steps,
            fn ($s) => ($s['bid_mode'] ?? 'fixed') === 'anyway' && $s['status'] === 'pending' && $s['max_bid'] === null
        );

        $currentPrice = $a['current_price'];
        $outbid = !$hasUncappedAnyway && $currentPrice !== null && in_array($a['status'], ['pending', 'bid_placed'], true) && (float) $currentPrice >= $effectiveMaxBid;
        $estimate = estimate_landed_cost($effectiveMaxBid, $a['shipping_cost'], $a['item_country'], $homeCountry);
        $editable = !in_array($a['status'], ['won', 'lost'], true);
        $editUrl = 'edit_auction?id=' . (int) $a['id'];
        $detailUrl = 'auction?id=' . (int) $a['id'];
        $titleText = htmlspecialchars($a['title'] ?? '(unknown title)');
    ?>
        <article class="entry">
            <?php if (!empty($a['image_url'])): ?>
                <a href="<?= $detailUrl ?>"><img class="entry-thumb" src="<?= htmlspecialchars($a['image_url']) ?>" alt=""></a>
            <?php endif; ?>
            <div class="entry-main">
                <h3 class="entry-title<?= $outbid ? ' is-outbid' : '' ?>"><a href="<?= $editable ? $editUrl : $detailUrl ?>"><?= $titleText ?></a></h3>
                <p class="entry-meta">
                    Item <?= htmlspecialchars($a['item_id']) ?>
                    <span class="sep">·</span>
                    Ends <?= $a['end_time'] !== null ? local_time((int) strtotime($a['end_time']), $a['end_time']) : 'unknown' ?>
                    <span class="sep">·</span>
                    <span class="status-<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span>
                </p>
                <?php if ($a['end_time'] !== null): ?>
                    <p class="countdown-row">
                        <span class="countdown" data-countdown-end="<?= (int) strtotime($a['end_time']) ?>"></span>
                    </p>
                <?php endif; ?>
                <?php if ($outbid): ?>
                    <p class="warning-badge">Outbid — raise your max</p>
                <?php endif; ?>
                <?php if ($a['result_message']): ?>
                    <p class="hint"><?= htmlspecialchars($a['result_message']) ?></p>
                <?php endif; ?>
                <?php if ($steps): ?>
                <ul class="bid-steps-summary">
                <?php foreach ($steps as $s): ?>
                    <li>
                        <?= htmlspecialchars(format_seconds_before((int) $s['seconds_before'])) ?> before end: <?= htmlspecialchars(bid_step_amount_text($s, $currency)) ?>
                        <span class="status-<?= htmlspecialchars($s['status']) ?>">(<?= htmlspecialchars($s['status']) ?>)</span>
                    </li>
                <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <a class="entry-link entry-view-link" href="<?= htmlspecialchars(ebay_item_view_url($a['item_id'], ebay_config()['marketplace_id'])) ?>" target="_blank" rel="noopener">View on eBay</a>
            </div>
            <div class="entry-figures">
                <div class="entry-price<?= $outbid ? ' is-outbid' : '' ?>">
                    <?= $currentPrice !== null ? htmlspecialchars($currency . ' ' . number_format($currentPrice, 2)) : '—' ?>
                    <span class="entry-figure-label">current price</span>
                </div>
                <div class="entry-estimate">
                    <?= htmlspecialchars($currency . ' ' . number_format($estimate['total'], 2)) ?>
                    <span class="entry-figure-label">est. if you win</span>
                </div>
                <p class="hint">
                    highest bid <?= number_format($effectiveMaxBid, 2) ?>
                    + shipping <?= number_format($estimate['shipping'], 2) ?>
                    + buyer protection fee (est.) <?= number_format($estimate['buyer_protection_fee'], 2) ?>
                    <?php if ($estimate['gst'] > 0): ?>
                        + GST on import (est.) <?= number_format($estimate['gst'], 2) ?>
                    <?php endif; ?>
                </p>
                <?php if ($estimate['is_overseas']): ?>
                    <p class="hint">Ships from overseas (<?= htmlspecialchars($a['item_country']) ?>)</p>
                <?php endif; ?>
                <div class="entry-actions">
                    <a class="entry-link" href="<?= $detailUrl ?>">Details &amp; log</a>
                    <?php if ($editable): ?>
                        <a class="entry-link" href="<?= $editUrl ?>">Edit bids</a>
                    <?php endif; ?>
                    <form method="post" action="delete_auction" data-confirm="Remove this auction from your auction list?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <button type="submit" class="icon-btn icon-btn-danger" aria-label="Remove" title="Remove">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                <path d="M10 11v6"></path>
                                <path d="M14 11v6"></path>
                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<p class="hint">
    "Est. total if you win" is a best-effort estimate based on your highest configured bid (worst
    case — proxy bidding may win it for less): highest bid + shipping + eBay's published Buyer
    Protection fee (waived by some business/Pro sellers, which the API doesn't tell us) + GST on
    low-value imports where the item ships from outside <?= htmlspecialchars($homeCountry) ?> and
    eBay hasn't already included it in the price.
</p>
<?php endif; ?>

<?php if ($pastRows): ?>
    <h2 class="past-heading" id="past-auctions">Past auctions (<?= count($pastRows) ?>)</h2>
    <?php
    $sortKey = $pastSortKey;
    $sortDir = $pastSortDir;
    $sortParam = 'psort';
    $dirParam = 'pdir';
    $anchor = 'past-auctions';
    require __DIR__ . '/../includes/past_auctions_table.php';
    ?>
    <p class="hint">
        "Bids" shows what the cron job did as each auction closed. "Never fired" means the
        scheduled bid never ran at all. Open any auction for the full log of what happened.
    </p>
<?php endif; ?>

<?= app_scripts() ?>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
