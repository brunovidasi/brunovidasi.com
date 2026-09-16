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
        ],
    ];
} elseif ($account) {
    try {
        $client = new EbayClient();
        $items = $client->getWatchList($account['auth_token']);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = 'Watchlist';
require __DIR__ . '/../includes/layout_top.php';
?>
<h1>Your eBay watchlist</h1>
<p class="hint">Items you're watching on eBay itself. Add one to your <a href="dashboard.php">auction list</a> to schedule bids for it.</p>

<?php if (!$account && !$demo): ?>
    <div class="flash flash-error">
        You haven't connected an eBay account yet, so your watchlist can't be loaded.
        <a href="connect_ebay.php">Connect it now</a>.
    </div>
<?php elseif ($error): ?>
    <div class="flash flash-error">Couldn't load your eBay watchlist: <?= htmlspecialchars($error) ?></div>
<?php elseif (empty($items)): ?>
    <p class="hint">You're not watching any items on eBay right now.</p>
<?php else: ?>
<div class="entries">
    <?php foreach ($items as $item): ?>
        <article class="entry">
            <?php if ($item['gallery_url']): ?>
                <img class="entry-thumb" src="<?= htmlspecialchars($item['gallery_url']) ?>" alt="">
            <?php endif; ?>
            <div class="entry-main">
                <h3 class="entry-title"><?= htmlspecialchars($item['title'] !== '' ? $item['title'] : '(unknown title)') ?></h3>
                <p class="entry-meta">
                    Item <?= htmlspecialchars($item['item_id']) ?>
                    <span class="sep">·</span>
                    Ends <?= htmlspecialchars($item['end_time'] !== '' ? $item['end_time'] : 'unknown') ?>
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
<?php endif; ?>

<script src="assets/js/app.js"></script>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
