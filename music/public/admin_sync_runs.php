<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$perPage = 25;

$search = query('q');
$status = in_array(query('status'), ['running', 'ok', 'partial', 'error'], true) ? query('status') : '';
$trigger = in_array(query('trigger'), ['admin', 'cron', 'cli'], true) ? query('trigger') : '';
$pageNo = max(1, (int) query('page', '1'));

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(trigger_source LIKE ? OR status LIKE ? OR phase LIKE ? OR message LIKE ? OR log LIKE ?)';
    $params = array_merge($params, array_fill(0, 5, '%' . $search . '%'));
}
if ($status !== '') {
    $where[] = 'status = ?';
    $params[] = $status;
}
if ($trigger !== '') {
    $where[] = 'trigger_source = ?';
    $params[] = $trigger;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = db()->prepare("SELECT COUNT(*) FROM sync_runs $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$pages = max(1, (int) ceil($total / $perPage));
$pageNo = min($pageNo, $pages);
$offset = ($pageNo - 1) * $perPage;

// $perPage/$offset are our own ints, not user input bound as a param — SQLite's
// LIMIT/OFFSET are happy with that and it sidesteps any doubt about how PDO's
// emulated prepares would type an integer bound alongside the LIKE strings above.
$stmt = db()->prepare("SELECT * FROM sync_runs $whereSql ORDER BY id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$runs = $stmt->fetchAll();

/** A link to this list with some of the state changed; page resets unless given. */
function sync_runs_link(array $overrides): string
{
    global $search, $status, $trigger;

    $current = array_filter(['q' => $search, 'status' => $status, 'trigger' => $trigger], fn ($v) => $v !== '');
    $params = array_filter($current + $overrides, fn ($v) => $v !== '' && $v !== null);

    return url('admin_sync_runs') . '?' . http_build_query($params);
}

/** How long a run took, or how long it's been running, for the table. */
function sync_run_duration(array $run): string
{
    $start = to_timestamp($run['started_at']);
    $end = to_timestamp($run['finished_at']);

    if ($start === null || ($end === null && $run['status'] !== 'running')) {
        return '—';
    }

    $seconds = ($end ?? time()) - $start;
    if ($seconds < 0) {
        return '—';
    }

    $duration = $seconds >= 60 ? sprintf('%dm %ds', intdiv($seconds, 60), $seconds % 60) : "{$seconds}s";

    return $end === null ? "$duration…" : $duration;
}

$statusLabels = ['running' => 'Running', 'ok' => 'OK', 'partial' => 'Partial', 'error' => 'Error'];
$triggerLabels = ['admin' => 'Admin button', 'cron' => 'Cron', 'cli' => 'CLI'];

$chips = [];
if ($search !== '') {
    $chips[] = ['Search', '“' . $search . '”', sync_runs_link(['q' => null, 'page' => null])];
}
if ($status !== '') {
    $chips[] = ['Status', $statusLabels[$status], sync_runs_link(['status' => null, 'page' => null])];
}
if ($trigger !== '') {
    $chips[] = ['Trigger', $triggerLabels[$trigger], sync_runs_link(['trigger' => null, 'page' => null])];
}

$pageTitle = 'Sync history';
$pageIntro = "$total run" . ($total === 1 ? '' : 's') . ' of the Discogs sync, newest first.';
$pageScript = 'js/admin-filters.js';

require __DIR__ . '/../includes/admin_layout_top.php';
?>

<form class="filterbar" id="filters" method="get" action="<?= e(url('admin_sync_runs')) ?>">
  <div class="field search<?= $search !== '' ? ' is-active' : '' ?>">
    <label for="q">Search</label>
    <input type="search" id="q" name="q" value="<?= e($search) ?>" autocomplete="off" enterkeyhint="search"
           placeholder="Status, message, log…">
  </div>

  <div class="field<?= $status !== '' ? ' is-active' : '' ?>">
    <label for="status">Status</label>
    <select id="status" name="status">
      <option value="">Any</option>
      <?php foreach ($statusLabels as $value => $label): ?>
        <option value="<?= e($value) ?>"<?= $status === $value ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="field<?= $trigger !== '' ? ' is-active' : '' ?>">
    <label for="trigger">Trigger</label>
    <select id="trigger" name="trigger">
      <option value="">Any</option>
      <?php foreach ($triggerLabels as $value => $label): ?>
        <option value="<?= e($value) ?>"<?= $trigger === $value ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <noscript><div class="field"><button type="submit" class="ghost">Filter</button></div></noscript>
</form>

<?php if ($chips): ?>
  <div class="filter-chips" aria-label="What is filtering this list">
    <?php foreach ($chips as [$name, $value, $removeUrl]): ?>
      <a class="chip" href="<?= e($removeUrl) ?>" title="Remove this filter">
        <span class="k"><?= e($name) ?></span> <?= e($value) ?> <span class="x" aria-hidden="true">×</span>
      </a>
    <?php endforeach; ?>
    <a class="clear-all" href="<?= e(url('admin_sync_runs')) ?>" title="Clear every filter">Clear all</a>
  </div>
<?php endif; ?>

<div class="card">
  <?php if (!$runs): ?>
    <?php if ($chips): ?>
      <p class="empty">Nothing matches these filters. <a href="<?= e(url('admin_sync_runs')) ?>">Clear them</a></p>
    <?php else: ?>
      <p class="empty">No syncs have run yet.</p>
    <?php endif; ?>
  <?php else: ?>
    <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Started</th>
          <th class="hide-sm">Duration</th>
          <th>Trigger</th>
          <th>Status</th>
          <th class="hide-sm">Collection</th>
          <th class="hide-sm">Wantlist</th>
          <th class="hide-sm">Details</th>
          <th class="right hide-sm">API calls</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($runs as $run): ?>
          <tr>
            <td><?= e(format_date($run['started_at'], 'j M Y, H:i')) ?></td>
            <td class="hide-sm mono"><?= e(sync_run_duration($run)) ?></td>
            <td><?= e($triggerLabels[$run['trigger_source']] ?? $run['trigger_source']) ?></td>
            <td>
              <span class="pill <?= $run['status'] === 'ok' ? 'good' : (in_array($run['status'], ['error', 'partial'], true) ? 'warn' : '') ?>"><?= e($statusLabels[$run['status']] ?? $run['status']) ?></span>
              <?php if ($run['message']): ?>
                <small style="opacity:0.6;display:block;"><?= e($run['message']) ?></small>
              <?php endif; ?>
            </td>
            <td class="hide-sm"><?= (int) $run['collection_seen'] ?></td>
            <td class="hide-sm"><?= (int) $run['wantlist_seen'] ?></td>
            <td class="hide-sm"><?= (int) $run['details_fetched'] ?><?= $run['details_pending'] ? ' (+' . (int) $run['details_pending'] . ' to go)' : '' ?></td>
            <td class="right hide-sm"><?= (int) $run['api_calls'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <?php if ($pages > 1): ?>
      <div class="form-actions">
        <?php if ($pageNo > 1): ?><a class="btn ghost small" href="<?= e(sync_runs_link(['page' => $pageNo - 1])) ?>">← Previous</a><?php endif; ?>
        <span style="font-size:0.85rem;opacity:0.6;">Page <?= $pageNo ?> of <?= $pages ?></span>
        <?php if ($pageNo < $pages): ?><a class="btn ghost small" href="<?= e(sync_runs_link(['page' => $pageNo + 1])) ?>">Next →</a><?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin_layout_bottom.php'; ?>
