<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$admin = require_admin();

const ADMIN_PER_PAGE = 20;

function admin_page_number(string $param): int
{
    return max(1, (int) get_param($param));
}

/**
 * Auctions are "current" until their end time passes. Compared against PHP's clock
 * rather than SQLite's datetime('now') because end_time is written in app-local
 * time (see add_auction.php), which is what the sniping cron compares against too.
 */
$now = date('Y-m-d H:i:s');

$userTotals = db()->query('SELECT COUNT(*) AS total, SUM(is_active) AS active FROM users')->fetch(PDO::FETCH_ASSOC);

$auctionTotalsStmt = db()->prepare('
    SELECT
        SUM(CASE WHEN end_time IS NULL OR end_time >= ? THEN 1 ELSE 0 END) AS current,
        SUM(CASE WHEN end_time IS NOT NULL AND end_time <  ? THEN 1 ELSE 0 END) AS past
    FROM watched_auctions
');
$auctionTotalsStmt->execute([$now, $now]);
$auctionTotals = $auctionTotalsStmt->fetch(PDO::FETCH_ASSOC);
$currentTotal = (int) ($auctionTotals['current'] ?? 0);
$pastTotal = (int) ($auctionTotals['past'] ?? 0);

// ---------------------------------------------------------------- users

$search = trim(get_param('q'));
$userSortColumns = [
    'email'    => 'u.email COLLATE NOCASE',
    'id'       => 'u.id',
    'joined'   => 'u.created_at',
    'ebay'     => 'ebay_env',
    'auctions' => 'auction_count',
    'status'   => 'u.is_active',
];
[$userSortKey, $userSortDir] = resolve_sort(get_param('sort'), get_param('dir'), array_keys($userSortColumns), 'joined', 'desc');

$userWhere = '';
$userArgs = [];
if ($search !== '') {
    $userWhere = 'WHERE u.email LIKE ?';
    $userArgs[] = '%' . $search . '%';
}

$countStmt = db()->prepare("SELECT COUNT(*) FROM users u $userWhere");
$countStmt->execute($userArgs);
$userMatches = (int) $countStmt->fetchColumn();

$userPages = max(1, (int) ceil($userMatches / ADMIN_PER_PAGE));
$userPage = min(admin_page_number('up'), $userPages);
$userOffset = ($userPage - 1) * ADMIN_PER_PAGE;

// Admins always sort to the top, whatever ordering is picked below them.
$usersStmt = db()->prepare("
    SELECT u.id, u.email, u.is_admin, u.is_active, u.created_at,
           (SELECT COUNT(*) FROM watched_auctions wa WHERE wa.user_id = u.id) AS auction_count,
           (SELECT ea.environment FROM ebay_accounts ea WHERE ea.user_id = u.id) AS ebay_env
    FROM users u
    $userWhere
    ORDER BY u.is_admin DESC, {$userSortColumns[$userSortKey]} $userSortDir, u.email ASC
    LIMIT " . ADMIN_PER_PAGE . " OFFSET $userOffset
");
$usersStmt->execute($userArgs);
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// ------------------------------------------------------------- auctions

$auctionSortKeys = ['item', 'owner', 'end', 'status', 'result', 'price', 'topbid'];

/** @return array{rows: array, pages: int, page: int} */
function admin_auctions(string $condition, string $order, string $pageParam, string $now): array
{
    $count = db()->prepare("SELECT COUNT(*) FROM watched_auctions wa WHERE $condition");
    $count->execute([$now]);
    $total = (int) $count->fetchColumn();

    $pages = max(1, (int) ceil($total / ADMIN_PER_PAGE));
    $page = min(admin_page_number($pageParam), $pages);
    $offset = ($page - 1) * ADMIN_PER_PAGE;

    $stmt = db()->prepare("
        SELECT wa.*, u.email AS owner_email
        FROM watched_auctions wa
        JOIN users u ON u.id = wa.user_id
        WHERE $condition
        ORDER BY $order
        LIMIT " . ADMIN_PER_PAGE . " OFFSET $offset
    ");
    $stmt->execute([$now]);

    return ['rows' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'pages' => $pages, 'page' => $page];
}

[$currentSortKey, $currentSortDir] = resolve_sort(get_param('csort'), get_param('cdir'), $auctionSortKeys, 'end', 'asc');
[$pastSortKey, $pastSortDir] = resolve_sort(get_param('psort'), get_param('pdir'), $auctionSortKeys, 'end', 'desc');

$current = admin_auctions('wa.end_time IS NULL OR wa.end_time >= ?', watched_auction_order_sql($currentSortKey, $currentSortDir) . ', wa.id ASC', 'cp', $now);
$past = admin_auctions('wa.end_time IS NOT NULL AND wa.end_time < ?', watched_auction_order_sql($pastSortKey, $pastSortDir) . ', wa.id ASC', 'pp', $now);

$stepsStmt = db()->prepare('SELECT * FROM bid_steps WHERE watched_auction_id = ? ORDER BY seconds_before DESC');

function admin_top_bid(PDOStatement $stmt, int $auctionId): float
{
    $stmt->execute([$auctionId]);
    $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $steps ? (float) max(array_column($steps, 'max_bid')) : 0.0;
}

$pageTitle = 'Admin';
$currency = ebay_config()['currency'];
require __DIR__ . '/../includes/layout_top.php';
?>
<h1>Admin dashboard</h1>

<div class="admin-tools">
    <a href="ebay_setup.php" class="btn secondary">eBay setup</a>
    <a href="preflight.php" class="btn secondary">Preflight</a>
</div>

<?php [$cronState, $cronMessage] = cron_health(); ?>
<div class="cron-status cron-<?= $cronState ?>">
    <span class="cron-dot"></span>
    <?= htmlspecialchars($cronMessage) ?>
    <?php if ($cronState !== 'ok'): ?>
        <a href="preflight.php">Check deployment &rarr;</a>
    <?php endif; ?>
</div>

<details class="admin-deletion-log">
    <summary>Recent eBay deletion activity</summary>
    <?php
    $deletionEvents = db()->query('SELECT * FROM ebay_deletion_log ORDER BY id DESC LIMIT 20')->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <?php if (!$deletionEvents): ?>
        <p class="admin-empty">No account-deletion notifications or verification attempts recorded yet.</p>
    <?php else: ?>
        <table class="admin-table">
            <tr><th>When</th><th>Username</th><th>Action</th></tr>
            <?php foreach ($deletionEvents as $event): ?>
                <tr>
                    <td><?= htmlspecialchars($event['received_at']) ?></td>
                    <td><?= htmlspecialchars($event['ebay_username'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($event['action']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</details>

<div class="admin-stats">
    <div class="admin-stat">
        <span class="admin-stat-value"><?= (int) $userTotals['total'] ?></span>
        <span class="admin-stat-label">Users</span>
    </div>
    <div class="admin-stat">
        <span class="admin-stat-value"><?= (int) $userTotals['active'] ?></span>
        <span class="admin-stat-label">Active users</span>
    </div>
    <div class="admin-stat">
        <span class="admin-stat-value"><?= (int) $userTotals['total'] - (int) $userTotals['active'] ?></span>
        <span class="admin-stat-label">Inactive users</span>
    </div>
    <div class="admin-stat">
        <span class="admin-stat-value"><?= $currentTotal ?></span>
        <span class="admin-stat-label">Current auctions</span>
    </div>
    <div class="admin-stat">
        <span class="admin-stat-value"><?= $pastTotal ?></span>
        <span class="admin-stat-label">Past auctions</span>
    </div>
</div>

<div class="admin-section" id="users">
    <h2>Users (<?= $userMatches ?>)<?= $search !== '' ? ' <span class="muted">matching “' . htmlspecialchars($search) . '”</span>' : '' ?></h2>
    <form class="admin-filters" method="get" action="admin.php">
        <input type="search" name="q" placeholder="Search email…" value="<?= htmlspecialchars($search) ?>">
        <input type="hidden" name="sort" value="<?= htmlspecialchars($userSortKey) ?>">
        <input type="hidden" name="dir" value="<?= htmlspecialchars($userSortDir) ?>">
        <button type="submit">Apply</button>
        <?php if ($search !== ''): ?>
            <a href="admin.php">Reset</a>
        <?php endif; ?>
    </form>
</div>

<?php if (!$users): ?>
    <p class="admin-empty">No users match “<?= htmlspecialchars($search) ?>”.</p>
<?php else: ?>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <?= sortable_th('Email', 'email', $userSortKey, $userSortDir, 'sort', 'dir', ['up'], '', 'users') ?>
                <?= sortable_th('ID', 'id', $userSortKey, $userSortDir, 'sort', 'dir', ['up'], 'num', 'users') ?>
                <?= sortable_th('Joined', 'joined', $userSortKey, $userSortDir, 'sort', 'dir', ['up'], '', 'users') ?>
                <?= sortable_th('eBay', 'ebay', $userSortKey, $userSortDir, 'sort', 'dir', ['up'], '', 'users') ?>
                <?= sortable_th('Auctions', 'auctions', $userSortKey, $userSortDir, 'sort', 'dir', ['up'], 'num', 'users') ?>
                <?= sortable_th('Status', 'status', $userSortKey, $userSortDir, 'sort', 'dir', ['up'], '', 'users') ?>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr class="<?= $u['is_active'] ? '' : 'admin-row-inactive' ?>">
                    <td class="cell-email" title="<?= htmlspecialchars($u['email']) ?>">
                        <a href="admin_user.php?id=<?= (int) $u['id'] ?>"><?= htmlspecialchars($u['email']) ?></a>
                        <?php if ($u['is_admin']): ?><span class="admin-badge">admin</span><?php endif; ?>
                    </td>
                    <td class="num muted"><?= (int) $u['id'] ?></td>
                    <td class="nowrap muted"><?= local_time(db_time_epoch($u['created_at']), substr($u['created_at'], 0, 10)) ?></td>
                    <td class="nowrap">
                        <?= $u['ebay_env']
                            ? 'Connected <span class="muted">(' . htmlspecialchars($u['ebay_env']) . ')</span>'
                            : '<span class="muted">Not connected</span>' ?>
                    </td>
                    <td class="num"><?= (int) $u['auction_count'] ?></td>
                    <td class="nowrap">
                        <span class="status-<?= $u['is_active'] ? 'active' : 'inactive' ?>"><?= $u['is_active'] ? 'active' : 'inactive' ?></span>
                    </td>
                    <td class="cell-actions nowrap">
                        <?php if ((int) $u['id'] !== (int) $admin['id']): ?>
                            <form method="post" action="admin_user_status.php"
                                  <?= $u['is_active'] ? 'data-confirm="Deactivate this account? They won\'t be able to log in."' : '' ?>>
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                <input type="hidden" name="active" value="<?= $u['is_active'] ? '0' : '1' ?>">
                                <button type="submit" class="link-btn"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$pagerPage = $userPage;
$pagerPages = $userPages;
$pagerParam = 'up';
$pagerAnchor = 'users';
require __DIR__ . '/../includes/admin_pager.php';
?>
<?php endif; ?>

<?php
$sections = [
    ['title' => 'Current auctions', 'anchor' => 'current-auctions', 'data' => $current, 'sortKey' => $currentSortKey, 'sortDir' => $currentSortDir, 'sortParam' => 'csort', 'dirParam' => 'cdir', 'pageParam' => 'cp', 'dateLabel' => 'Ends', 'empty' => 'No current auctions.', 'isPast' => false, 'total' => $currentTotal],
    ['title' => 'Past auctions', 'anchor' => 'past-auctions', 'data' => $past, 'sortKey' => $pastSortKey, 'sortDir' => $pastSortDir, 'sortParam' => 'psort', 'dirParam' => 'pdir', 'pageParam' => 'pp', 'dateLabel' => 'Ended', 'empty' => 'No past auctions yet.', 'isPast' => true, 'total' => $pastTotal],
];
foreach ($sections as $section):
?>
<div class="admin-section" id="<?= htmlspecialchars($section['anchor']) ?>">
    <h2><?= htmlspecialchars($section['title']) ?> (<?= $section['total'] ?>)</h2>
</div>

<?php if (!$section['data']['rows']): ?>
    <p class="admin-empty"><?= htmlspecialchars($section['empty']) ?></p>
<?php elseif ($section['isPast']): ?>
<?php
$pastRows = $section['data']['rows'];
foreach ($pastRows as &$pastRow) {
    $stepsStmt->execute([$pastRow['id']]);
    $pastRow['steps'] = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($pastRow);
$showOwner = true;
$detailPage = 'admin_auction.php';
$sortKey = $section['sortKey'];
$sortDir = $section['sortDir'];
$sortParam = $section['sortParam'];
$dirParam = $section['dirParam'];
$sortResetParams = [$section['pageParam']];
$anchor = $section['anchor'];
require __DIR__ . '/../includes/past_auctions_table.php';
?>
<?php else: ?>
<div class="admin-table-wrap" data-server-now="<?= time() ?>">
    <table class="admin-table">
        <thead>
            <tr>
                <?= sortable_th('Item', 'item', $section['sortKey'], $section['sortDir'], $section['sortParam'], $section['dirParam'], [$section['pageParam']], '', $section['anchor']) ?>
                <?= sortable_th('Owner', 'owner', $section['sortKey'], $section['sortDir'], $section['sortParam'], $section['dirParam'], [$section['pageParam']], '', $section['anchor']) ?>
                <?= sortable_th($section['dateLabel'], 'end', $section['sortKey'], $section['sortDir'], $section['sortParam'], $section['dirParam'], [$section['pageParam']], '', $section['anchor']) ?>
                <th>Countdown</th>
                <?= sortable_th('Status', 'status', $section['sortKey'], $section['sortDir'], $section['sortParam'], $section['dirParam'], [$section['pageParam']], '', $section['anchor']) ?>
                <?= sortable_th('Price', 'price', $section['sortKey'], $section['sortDir'], $section['sortParam'], $section['dirParam'], [$section['pageParam']], 'num', $section['anchor']) ?>
                <?= sortable_th('Top bid', 'topbid', $section['sortKey'], $section['sortDir'], $section['sortParam'], $section['dirParam'], [$section['pageParam']], 'num', $section['anchor']) ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($section['data']['rows'] as $a): ?>
                <tr>
                    <td class="cell-title" title="<?= htmlspecialchars($a['title'] ?? '') ?>">
                        <a href="admin_auction.php?id=<?= (int) $a['id'] ?>"><?= htmlspecialchars($a['title'] ?? '(unknown title)') ?></a>
                        <span class="muted"><?= htmlspecialchars($a['item_id']) ?></span>
                    </td>
                    <td class="cell-email" title="<?= htmlspecialchars($a['owner_email']) ?>">
                        <a href="admin_user.php?id=<?= (int) $a['user_id'] ?>"><?= htmlspecialchars($a['owner_email']) ?></a>
                    </td>
                    <td class="nowrap muted"><?= $a['end_time'] !== null ? local_time((int) strtotime($a['end_time']), $a['end_time']) : 'unknown' ?></td>
                    <td class="nowrap">
                        <?php if ($a['end_time'] !== null): ?>
                            <span class="countdown countdown-cell" data-countdown-end="<?= (int) strtotime($a['end_time']) ?>"></span>
                        <?php else: ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="nowrap"><span class="status-<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span></td>
                    <td class="num"><?= $a['current_price'] !== null ? htmlspecialchars(number_format($a['current_price'], 2)) : '—' ?></td>
                    <td class="num"><?= htmlspecialchars(number_format(admin_top_bid($stepsStmt, (int) $a['id']), 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if ($section['data']['rows']):
    $pagerPage = $section['data']['page'];
    $pagerPages = $section['data']['pages'];
    $pagerParam = $section['pageParam'];
    $pagerAnchor = $section['anchor'];
    require __DIR__ . '/../includes/admin_pager.php';
endif; ?>
<?php endforeach; ?>

<p class="hint">Prices shown in <?= htmlspecialchars($currency) ?>. "Top bid" is the highest scheduled bid on the auction.</p>

<script src="assets/js/app.js"></script>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
