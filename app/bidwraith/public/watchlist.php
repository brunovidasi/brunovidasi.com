<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();

$stmt = db()->prepare('SELECT * FROM ebay_accounts WHERE user_id = ?');
$stmt->execute([$user['id']]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

$items = [];
$error = null;
$demo = isset($_GET['demo']);

if ($demo) {
    // Sample data for previewing the layout only — not real eBay items. Visit with ?demo=1.
    // Includes a couple of fixed-price (non-auction) items so the auction-only filter below
    // has something to demonstrate, plus enough auctions to preview pagination.
    $items = [
        [
            'item_id' => '203945671201',
            'title' => 'Sony WH-1000XM5 Wireless Noise Cancelling Headphones - Black',
            'end_time' => date('Y-m-d H:i:s', strtotime('+1 day 3 hours')),
            'view_url' => 'https://www.ebay.com.au/itm/203945671201',
            'gallery_url' => 'https://placehold.co/120x120?text=Headphones',
            'current_price' => 214.50,
            'currency' => 'AUD',
            'bid_count' => 8,
            'listing_type' => 'Chinese',
        ],
        [
            'item_id' => '186372940458',
            'title' => 'Vintage Omega Seamaster Automatic Watch, 1970s',
            'end_time' => date('Y-m-d H:i:s', strtotime('+5 hours 20 minutes')),
            'view_url' => 'https://www.ebay.com.au/itm/186372940458',
            'gallery_url' => 'https://placehold.co/120x120?text=Watch',
            'current_price' => 1325.00,
            'currency' => 'AUD',
            'bid_count' => 23,
            'listing_type' => 'Chinese',
        ],
        [
            'item_id' => '297581103366',
            'title' => 'LEGO Icons 10294 Titanic Building Set (New, Sealed)',
            'end_time' => date('Y-m-d H:i:s', strtotime('+2 days 11 hours')),
            'view_url' => 'https://www.ebay.com.au/itm/297581103366',
            'gallery_url' => 'https://placehold.co/120x120?text=LEGO',
            'current_price' => 489.00,
            'currency' => 'AUD',
            'bid_count' => 3,
            'listing_type' => 'FixedPriceItem',
        ],
        [
            'item_id' => '154029887712',
            'title' => 'Canon EF 50mm f/1.8 STM Lens',
            'end_time' => date('Y-m-d H:i:s', strtotime('+40 minutes')),
            'view_url' => 'https://www.ebay.com.au/itm/154029887712',
            'gallery_url' => '',
            'current_price' => 96.00,
            'currency' => 'AUD',
            'bid_count' => null,
            'listing_type' => 'FixedPriceItem',
        ],
    ];
    // Pad out with extra auction entries so the demo also previews pagination.
    for ($i = 1; $i <= 10; $i++) {
        $items[] = [
            'item_id' => '900000000' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
            'title' => "Demo auction item #$i",
            'end_time' => date('Y-m-d H:i:s', strtotime("+$i hours")),
            'view_url' => 'https://www.ebay.com.au/itm/900000000' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
            'gallery_url' => '',
            'current_price' => 20.00 + $i,
            'currency' => 'AUD',
            'bid_count' => $i,
            'listing_type' => 'Chinese',
        ];
    }
} elseif ($account) {
    try {
        $client = new EbayClient();
        $items = $client->getWatchList($account['auth_token']);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// The watchlist can include fixed-price (Buy It Now) listings too, but this app only
// places auction bids, so only auction-format listings are shown here. eBay's Trading
// API returns "Chinese" (its historical internal name for this format) from some
// calls and "Auction" from others — GetMyeBayBuying uses "Auction" — so match both.
$items = array_values(array_filter($items, fn ($item) => in_array($item['listing_type'] ?? '', ['Chinese', 'Auction'], true)));

$perPageOptions = [10, 20, 50, 100];
$perPage = (int) get_param('per_page');
if (!in_array($perPage, $perPageOptions, true)) {
    $perPage = 10;
}

$totalItems = count($items);
$pagerPages = max(1, (int) ceil($totalItems / $perPage));
$pagerPage = (int) get_param('page');
if ($pagerPage < 1) {
    $pagerPage = 1;
} elseif ($pagerPage > $pagerPages) {
    $pagerPage = $pagerPages;
}
$pagerParam = 'page';

$pagedItems = array_slice($items, ($pagerPage - 1) * $perPage, $perPage);

$pageTitle = 'Watchlist';
require __DIR__ . '/../includes/layout_top.php';
?>
<h1>Your eBay watchlist</h1>
<p class="hint">Auctions you're watching on eBay itself. Add one to your <a href="dashboard.php">auction list</a> to schedule bids for it.</p>

<?php if (!$account && !$demo): ?>
    <div class="flash flash-error">
        You haven't connected an eBay account yet, so your watchlist can't be loaded.
        <a href="connect_ebay.php">Connect it now</a>.
    </div>
<?php elseif ($error): ?>
    <div class="flash flash-error">Couldn't load your eBay watchlist: <?= htmlspecialchars($error) ?></div>
<?php elseif ($totalItems === 0): ?>
    <p class="hint">You're not watching any auctions on eBay right now.</p>
<?php else: ?>
<form class="admin-filters" method="get">
    <?php if ($demo): ?><input type="hidden" name="demo" value="1"><?php endif; ?>
    <label for="per_page">Per page</label>
    <select id="per_page" name="per_page" onchange="this.form.submit()">
        <?php foreach ($perPageOptions as $option): ?>
            <option value="<?= $option ?>" <?= $option === $perPage ? 'selected' : '' ?>><?= $option ?></option>
        <?php endforeach; ?>
    </select>
</form>
<div class="entries">
    <?php foreach ($pagedItems as $item): ?>
        <article class="entry">
            <?php if ($item['gallery_url']): ?>
                <img class="entry-thumb" src="<?= htmlspecialchars($item['gallery_url']) ?>" alt="">
            <?php endif; ?>
            <div class="entry-main">
                <h3 class="entry-title"><?= htmlspecialchars($item['title'] !== '' ? $item['title'] : '(unknown title)') ?></h3>
                <p class="entry-meta">
                    Item <?= htmlspecialchars($item['item_id']) ?>
                    <span class="sep">·</span>
                    Ends <?= $item['end_time'] !== '' ? local_time((int) strtotime($item['end_time']), $item['end_time']) : 'unknown' ?>
                    <?php if ($item['bid_count'] !== null): ?>
                        <span class="sep">·</span>
                        <?= (int) $item['bid_count'] ?> bid<?= $item['bid_count'] === 1 ? '' : 's' ?>
                    <?php endif; ?>
                </p>
                <?php if ($item['view_url']): ?>
                    <a class="entry-link" href="<?= htmlspecialchars($item['view_url']) ?>" target="_blank" rel="noopener">View on eBay</a>
                <?php endif; ?>
            </div>
            <div class="entry-figures">
                <div class="entry-price">
                    <?= $item['current_price'] !== null ? htmlspecialchars(trim($item['currency'] . ' ' . number_format($item['current_price'], 2))) : '—' ?>
                    <span class="entry-figure-label">current price</span>
                </div>
                <a class="btn" href="add_auction.php?item_id=<?= urlencode($item['item_id']) ?>">+ Add to auction list</a>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/admin_pager.php'; ?>
<?php endif; ?>

<script src="<?= asset_url('assets/js/app.js') ?>"></script>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
