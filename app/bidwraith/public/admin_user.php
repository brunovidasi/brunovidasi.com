<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$admin = require_admin();

$userId = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT id, email, is_admin, is_active, currency, created_at, free_access, free_access_note, stripe_customer_id, stripe_subscription_id, subscription_status, trial_ends_at, current_period_end, cancel_at_period_end FROM users WHERE id = ?');
$stmt->execute([$userId]);
$viewed = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$viewed) {
    http_response_code(404);
    $pageTitle = 'User not found';
    require __DIR__ . '/../includes/layout_top.php';
    echo '<h1>User not found</h1><p class="hint"><a href="admin">Back to the admin dashboard</a></p>';
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
$currency = user_currency($viewed);
$detailPage = 'admin_auction';
require __DIR__ . '/../includes/layout_top.php';
?>
<p class="crumb"><a href="admin">&larr; Admin dashboard</a></p>

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
    <form method="post" action="admin_user_status" class="detail-action"
          <?= $viewed['is_active'] ? 'data-confirm="Deactivate this account? They won\'t be able to log in."' : '' ?>>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $viewed['id'] ?>">
        <input type="hidden" name="active" value="<?= $viewed['is_active'] ? '0' : '1' ?>">
        <input type="hidden" name="return" value="admin_user?id=<?= (int) $viewed['id'] ?>">
        <button type="submit" class="<?= $viewed['is_active'] ? 'danger' : '' ?>"><?= $viewed['is_active'] ? 'Deactivate account' : 'Activate account' ?></button>
    </form>
<?php endif; ?>

<?php if (billing_enabled()):
    $group = billing_group($viewed);
    $fmtTime = fn (?string $t) => $t === null ? '—' : local_time(db_time_epoch($t), substr($t, 0, 10));
?>
<div class="admin-section" id="plan"><h2>Plan</h2></div>
<div class="detail-grid">
    <div><span class="detail-label">Plan</span><?= billing_group_badge($group) ?></div>
    <div><span class="detail-label">Stripe status</span><?= $viewed['subscription_status'] !== null ? htmlspecialchars($viewed['subscription_status']) . ($viewed['cancel_at_period_end'] ? ' <span class="muted">(cancelling)</span>' : '') : '<span class="muted">Never subscribed</span>' ?></div>
    <?php if ($viewed['trial_ends_at'] !== null): ?>
        <div><span class="detail-label">Trial <?= db_time_epoch($viewed['trial_ends_at']) > time() ? 'ends' : 'ended' ?></span><?= $fmtTime($viewed['trial_ends_at']) ?></div>
    <?php endif; ?>
    <?php if ($viewed['current_period_end'] !== null): ?>
        <div><span class="detail-label"><?= $viewed['cancel_at_period_end'] ? 'Access ends' : 'Renews / period ends' ?></span><?= $fmtTime($viewed['current_period_end']) ?></div>
    <?php endif; ?>
    <?php if ($viewed['stripe_customer_id']): ?>
        <div><span class="detail-label">Stripe</span><a href="<?= htmlspecialchars(stripe_dashboard_url('customers/' . $viewed['stripe_customer_id'])) ?>" target="_blank" rel="noopener">Open customer &nearr;</a></div>
    <?php endif; ?>
</div>

<?php if ($viewed['stripe_subscription_id']): ?>
    <form method="post" action="admin_user_access" class="detail-action">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $viewed['id'] ?>">
        <input type="hidden" name="action" value="refresh">
        <input type="hidden" name="return" value="admin_user?id=<?= (int) $viewed['id'] ?>">
        <button type="submit" class="secondary">Refresh from Stripe</button>
    </form>
<?php endif; ?>

<?php if (!$viewed['is_admin']): ?>
    <h3>Free access</h3>
    <p class="hint">Lets this person use Bidwraith without a plan. It doesn't touch any Stripe subscription they may have.</p>
    <form method="post" action="admin_user_access" class="stacked">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $viewed['id'] ?>">
        <input type="hidden" name="return" value="admin_user?id=<?= (int) $viewed['id'] ?>">

        <label for="free_access">Free access</label>
        <select id="free_access" name="free_access">
            <option value="0" <?= $viewed['free_access'] ? '' : 'selected' ?>>No</option>
            <option value="1" <?= $viewed['free_access'] ? 'selected' : '' ?>>Yes</option>
        </select>

        <label for="free_access_note">Note</label>
        <input type="text" id="free_access_note" name="note" maxlength="200" placeholder="Why — only admins see this" value="<?= htmlspecialchars($viewed['free_access_note'] ?? '') ?>">

        <button type="submit">Save</button>
    </form>
<?php endif; ?>
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
                        <a href="admin_auction?id=<?= (int) $a['id'] ?>"><?= htmlspecialchars($a['title'] ?? '(unknown title)') ?></a>
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

<?= app_scripts() ?>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
