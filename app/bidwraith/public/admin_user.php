<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$admin = require_admin();

$userId = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT id, email, is_admin, is_active, created_at FROM users WHERE id = ?');
$stmt->execute([$userId]);
$viewed = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$viewed) {
    http_response_code(404);
    $pageTitle = 'User not found';
    require __DIR__ . '/../includes/layout_top.php';
    echo '<h1>User not found</h1><p class="hint"><a href="admin.php">Back to the admin dashboard</a></p>';
    require __DIR__ . '/../includes/layout_bottom.php';
    exit;
}

$ebayStmt = db()->prepare('SELECT * FROM ebay_accounts WHERE user_id = ?');
$ebayStmt->execute([$userId]);
$ebayAccount = $ebayStmt->fetch(PDO::FETCH_ASSOC);

$now = date('Y-m-d H:i:s');

$auctionAllowedSorts = ['item', 'end', 'status', 'result', 'price', 'topbid'];

[$currentSortKey, $currentSortDir] = resolve_sort(get_param('csort'), get_param('cdir'), $auctionAllowedSorts, 'end', 'asc');
$currentStmt = db()->prepare('
    SELECT wa.* FROM watched_auctions wa
    WHERE wa.user_id = ? AND (wa.end_time IS NULL OR wa.end_time >= ?)
    ORDER BY ' . watched_auction_order_sql($currentSortKey, $currentSortDir) . ', wa.id ASC
');
$currentStmt->execute([$userId, $now]);
$currentRows = $currentStmt->fetchAll(PDO::FETCH_ASSOC);

[$pastSortKey, $pastSortDir] = resolve_sort(get_param('psort'), get_param('pdir'), $auctionAllowedSorts, 'end', 'desc');
$pastStmt = db()->prepare('
    SELECT wa.* FROM watched_auctions wa
    WHERE wa.user_id = ? AND wa.end_time IS NOT NULL AND wa.end_time < ?
    ORDER BY ' . watched_auction_order_sql($pastSortKey, $pastSortDir) . ', wa.id DESC
');
$pastStmt->execute([$userId, $now]);
$pastRows = $pastStmt->fetchAll(PDO::FETCH_ASSOC);

$stepsStmt = db()->prepare('SELECT * FROM bid_steps WHERE watched_auction_id = ? ORDER BY seconds_before DESC');
foreach ($pastRows as &$pastRow) {
    $stepsStmt->execute([$pastRow['id']]);
    $pastRow['steps'] = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($pastRow);

$pageTitle = $viewed['email'];
$currency = ebay_config()['currency'];
$detailPage = 'admin_auction.php';
require __DIR__ . '/../includes/layout_top.php';
?>
<p class="crumb"><a href="admin.php">&larr; Admin dashboard</a></p>

<h1><?= htmlspecialchars($viewed['email']) ?></h1>

<div class="detail-grid">
    <div><span class="detail-label">User ID</span><?= (int) $viewed['id'] ?></div>
    <div><span class="detail-label">Joined</span><?= local_time(db_time_epoch($viewed['created_at']), $viewed['created_at']) ?></div>
    <div>
        <span class="detail-label">Role</span>
        <?= $viewed['is_admin'] ? 'Admin' : 'Standard user' ?>
    </div>
    <div>
        <span class="detail-label">Status</span>
        <span class="status-<?= $viewed['is_active'] ? 'active' : 'inactive' ?>"><?= $viewed['is_active'] ? 'active' : 'inactive' ?></span>
    </div>
    <div>
        <span class="detail-label">eBay account</span>
        <?= $ebayAccount
            ? 'Connected <span class="muted">(' . htmlspecialchars($ebayAccount['environment']) . ')</span>'
            : '<span class="muted">Not connected</span>' ?>
    </div>
    <?php if ($ebayAccount): ?>
        <div><span class="detail-label">Connected at</span><?= local_time(db_time_epoch($ebayAccount['connected_at']), $ebayAccount['connected_at']) ?></div>
        <div>
            <span class="detail-label">Token expires</span>
            <?php $expired = $ebayAccount['token_expires_at'] && strtotime($ebayAccount['token_expires_at']) < time(); ?>
            <span class="<?= $expired ? 'outcome-danger' : '' ?>">
                <?= $ebayAccount['token_expires_at'] !== null ? local_time((int) strtotime($ebayAccount['token_expires_at']), $ebayAccount['token_expires_at']) : 'unknown' ?><?= $expired ? ' (expired)' : '' ?>
            </span>
        </div>
    <?php endif; ?>
    <div><span class="detail-label">Auctions</span><?= count($currentRows) ?> current, <?= count($pastRows) ?> past</div>
</div>

<?php if ((int) $viewed['id'] !== (int) $admin['id']): ?>
    <form method="post" action="admin_user_status.php" class="detail-action"
          <?= $viewed['is_active'] ? 'data-confirm="Deactivate this account? They won\'t be able to log in."' : '' ?>>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $viewed['id'] ?>">
        <input type="hidden" name="active" value="<?= $viewed['is_active'] ? '0' : '1' ?>">
        <input type="hidden" name="return" value="admin_user.php?id=<?= (int) $viewed['id'] ?>">
        <button type="submit" class="<?= $viewed['is_active'] ? 'danger' : '' ?>"><?= $viewed['is_active'] ? 'Deactivate account' : 'Activate account' ?></button>
    </form>
<?php endif; ?>

<div class="admin-section" id="current-auctions"><h2>Current auctions (<?= count($currentRows) ?>)</h2></div>
<?php if (!$currentRows): ?>
    <p class="admin-empty">No auctions still running.</p>
<?php else: ?>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <?= sortable_th('Item', 'item', $currentSortKey, $currentSortDir, 'csort', 'cdir', [], '', 'current-auctions') ?>
                <?= sortable_th('Ends', 'end', $currentSortKey, $currentSortDir, 'csort', 'cdir', [], '', 'current-auctions') ?>
                <?= sortable_th('Status', 'status', $currentSortKey, $currentSortDir, 'csort', 'cdir', [], '', 'current-auctions') ?>
                <?= sortable_th('Price', 'price', $currentSortKey, $currentSortDir, 'csort', 'cdir', [], 'num', 'current-auctions') ?>
                <?= sortable_th('Top bid', 'topbid', $currentSortKey, $currentSortDir, 'csort', 'cdir', [], 'num', 'current-auctions') ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($currentRows as $a):
                $stepsStmt->execute([$a['id']]);
                $steps = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);
                $topBid = bid_steps_top_amount($steps);
            ?>
                <tr>
                    <td class="cell-title" title="<?= htmlspecialchars($a['title'] ?? '') ?>">
                        <a href="admin_auction.php?id=<?= (int) $a['id'] ?>"><?= htmlspecialchars($a['title'] ?? '(unknown title)') ?></a>
                        <span class="muted"><?= htmlspecialchars($a['item_id']) ?></span>
                    </td>
                    <td class="nowrap muted"><?= $a['end_time'] !== null ? local_time((int) strtotime($a['end_time']), $a['end_time']) : 'unknown' ?></td>
                    <td class="nowrap"><span class="status-<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span></td>
                    <td class="num"><?= $a['current_price'] !== null ? htmlspecialchars(number_format($a['current_price'], 2)) : '—' ?></td>
                    <td class="num"><?= htmlspecialchars(number_format($topBid, 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="admin-section" id="past-auctions"><h2>Past auctions (<?= count($pastRows) ?>)</h2></div>
<?php if (!$pastRows): ?>
    <p class="admin-empty">No past auctions yet.</p>
<?php else: ?>
    <?php
    $sortKey = $pastSortKey;
    $sortDir = $pastSortDir;
    $sortParam = 'psort';
    $dirParam = 'pdir';
    $anchor = 'past-auctions';
    require __DIR__ . '/../includes/past_auctions_table.php';
    ?>
<?php endif; ?>

<p class="hint">Prices shown in <?= htmlspecialchars($currency) ?>.</p>

<script src="<?= asset_url('assets/js/app.js') ?>"></script>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
