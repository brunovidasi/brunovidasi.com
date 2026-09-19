<?php
require_once __DIR__ . '/../includes/bootstrap.php';

require_admin();

const SUBSCRIPTIONS_PER_PAGE = 25;

/** The plan groups shown as filter tiles, in display order. Admins are left out of this page. */
const SUBSCRIPTION_VIEWS = ['trialing', 'active', 'past_due', 'free', 'ended', 'none'];

$groupSql = billing_group_sql('u');

$counts = array_fill_keys(SUBSCRIPTION_VIEWS, 0);
foreach (db()->query("SELECT $groupSql AS grp, COUNT(*) AS n FROM users u WHERE u.is_admin = 0 GROUP BY grp") as $row) {
    if (isset($counts[$row['grp']])) {
        $counts[$row['grp']] = (int) $row['n'];
    }
}
$cancelling = (int) db()->query("
    SELECT COUNT(*) FROM users u
    WHERE u.is_admin = 0 AND u.free_access = 0 AND u.cancel_at_period_end = 1
      AND u.subscription_status IN ('trialing', 'active')
")->fetchColumn();

$view = get_param('view');
$view = in_array($view, SUBSCRIPTION_VIEWS, true) ? $view : 'all';
$search = trim(get_param('q'));

$sortColumns = [
    'email'  => 'u.email COLLATE NOCASE',
    'plan'   => $groupSql,
    'trial'  => 'u.trial_ends_at IS NULL, u.trial_ends_at',
    'renews' => 'u.current_period_end IS NULL, u.current_period_end',
    'joined' => 'u.created_at',
];
// Trials sort soonest-ending first and renewals soonest-first, since those are the
// rows someone opens this page to act on; everything else is newest first.
[$defaultKey, $defaultDir] = match ($view) {
    'trialing' => ['trial', 'asc'],
    'active', 'past_due' => ['renews', 'asc'],
    default => ['joined', 'desc'],
};
[$sortKey, $sortDir] = resolve_sort(get_param('sort'), get_param('dir'), array_keys($sortColumns), $defaultKey, $defaultDir);

$where = ['u.is_admin = 0'];
$args = [];
if ($view !== 'all') {
    $where[] = "($groupSql) = ?";
    $args[] = $view;
}
if ($search !== '') {
    $where[] = 'u.email LIKE ?';
    $args[] = '%' . $search . '%';
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM users u $whereSql");
$countStmt->execute($args);
$matches = (int) $countStmt->fetchColumn();

$pages = max(1, (int) ceil($matches / SUBSCRIPTIONS_PER_PAGE));
$page = min(max(1, (int) get_param('p')), $pages);
$offset = ($page - 1) * SUBSCRIPTIONS_PER_PAGE;

$stmt = db()->prepare("
    SELECT u.id, u.email, u.is_admin, u.created_at, u.free_access, u.free_access_note, u.subscription_status,
           u.trial_ends_at, u.current_period_end, u.cancel_at_period_end
    FROM users u
    $whereSql
    ORDER BY {$sortColumns[$sortKey]} " . ($sortDir === 'asc' ? 'ASC' : 'DESC') . ", u.email ASC
    LIMIT " . SUBSCRIPTIONS_PER_PAGE . " OFFSET $offset
");
$stmt->execute($args);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tiles = [
    'trialing' => 'In trial',
    'active'   => 'Subscribed',
    'past_due' => 'Payment failed',
    'free'     => 'Free access',
    'ended'    => 'Ended',
    'none'     => 'No plan',
];

$fmtTime = fn (?string $t) => $t === null ? '<span class="muted">—</span>' : local_time(db_time_epoch($t), substr($t, 0, 10));

$pageTitle = 'Subscriptions';
require __DIR__ . '/../includes/layout_top.php';
?>
<p class="crumb"><a href="admin">&larr; Admin dashboard</a></p>

<h1>Subscriptions</h1>

<?php if (!billing_enabled()): ?>
    <div class="flash flash-error">Billing isn't switched on: set <strong>stripe.secret_key</strong> and <strong>stripe.price_id</strong> in the config. Until then nobody is gated, and no one can start a trial.</div>
<?php endif; ?>

<div class="admin-stats">
    <?php foreach ($tiles as $key => $label): ?>
        <a class="admin-stat<?= $view === $key ? ' is-active' : '' ?>" href="<?= htmlspecialchars(url_with(['view' => $view === $key ? null : $key, 'p' => null])) ?>">
            <span class="admin-stat-value"><?= $counts[$key] ?></span>
            <span class="admin-stat-label"><?= htmlspecialchars($label) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="admin-section" id="people">
    <h2><?= $view === 'all' ? 'Everyone' : htmlspecialchars($tiles[$view]) ?> (<?= $matches ?>)</h2>
    <form class="admin-filters" method="get" action="admin_subscriptions">
        <input type="search" name="q" placeholder="Search email…" value="<?= htmlspecialchars($search) ?>">
        <?php if ($view !== 'all'): ?><input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>"><?php endif; ?>
        <button type="submit">Apply</button>
        <?php if ($search !== '' || $view !== 'all'): ?>
            <a href="admin_subscriptions">Reset</a>
        <?php endif; ?>
    </form>
</div>

<?php if (!$users): ?>
    <p class="admin-empty">No one matches.</p>
<?php else: ?>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <?= sortable_th('Email', 'email', $sortKey, $sortDir, 'sort', 'dir', ['p'], '', 'people') ?>
                <?= sortable_th('Plan', 'plan', $sortKey, $sortDir, 'sort', 'dir', ['p'], '', 'people') ?>
                <?= sortable_th('Trial ends', 'trial', $sortKey, $sortDir, 'sort', 'dir', ['p'], '', 'people') ?>
                <?= sortable_th('Renews / ends', 'renews', $sortKey, $sortDir, 'sort', 'dir', ['p'], '', 'people') ?>
                <?= sortable_th('Joined', 'joined', $sortKey, $sortDir, 'sort', 'dir', ['p'], '', 'people') ?>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u):
                $group = billing_group($u);
                $paying = $group !== 'free' && $u['subscription_status'] !== null;
            ?>
                <tr>
                    <td class="cell-email" title="<?= htmlspecialchars($u['email']) ?>">
                        <a href="admin_user?id=<?= (int) $u['id'] ?>#plan"><?= htmlspecialchars($u['email']) ?></a>
                    </td>
                    <td class="nowrap">
                        <?= billing_group_badge($group) ?>
                        <?php if ($paying && $u['cancel_at_period_end'] && in_array($u['subscription_status'], ['trialing', 'active'], true)): ?>
                            <span class="muted">cancelling</span>
                        <?php endif; ?>
                    </td>
                    <td class="nowrap muted"><?= $paying ? $fmtTime($u['trial_ends_at']) : '—' ?></td>
                    <td class="nowrap muted"><?= $paying ? $fmtTime($u['current_period_end']) : '—' ?></td>
                    <td class="nowrap muted"><?= local_time(db_time_epoch($u['created_at']), substr($u['created_at'], 0, 10)) ?></td>
                    <td class="muted"><?= htmlspecialchars($u['free_access'] ? ($u['free_access_note'] ?? '') : '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$pagerPage = $page;
$pagerPages = $pages;
$pagerParam = 'p';
$pagerAnchor = 'people';
require __DIR__ . '/../includes/admin_pager.php';
?>
<?php endif; ?>

<p class="hint">Admins aren't listed — they never need a plan. "Payment failed" people keep access while Stripe retries their card. Trial and renewal dates are the last ones Stripe reported; open a person and use <em>Refresh from Stripe</em> if they look stale. "Cancelling" means they've turned off renewal and will lose access at the date shown.</p>

<?= app_scripts() ?>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
