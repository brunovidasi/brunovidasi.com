<?php

require_once __DIR__ . '/../includes/admin.php';

require_login();

const RUNS_PER_PAGE = 25;
const RUN_STATUSES = ['running' => 'Running', 'ok' => 'OK', 'partial' => 'Partial', 'error' => 'Error'];
const RUN_TRIGGERS = ['admin' => 'Admin button', 'cron' => 'Cron', 'cli' => 'CLI'];

/** How long a run took, or has been running. */
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

$search = query('q');
$status = isset(RUN_STATUSES[query('status')]) ? query('status') : '';
$trigger = isset(RUN_TRIGGERS[query('trigger')]) ? query('trigger') : '';
$pageNo = max(1, (int) query('page', '1'));

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(trigger_source LIKE ? OR status LIKE ? OR phase LIKE ? OR message LIKE ? OR log LIKE ?)';
    array_push($params, ...array_fill(0, 5, '%' . $search . '%'));
}
if ($status !== '') {
    $where[] = 'status = ?';
    $params[] = $status;
}
if ($trigger !== '') {
    $where[] = 'trigger_source = ?';
    $params[] = $trigger;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare("SELECT COUNT(*) FROM sync_runs $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$pages = max(1, (int) ceil($total / RUNS_PER_PAGE));
$pageNo = min($pageNo, $pages);
$offset = ($pageNo - 1) * RUNS_PER_PAGE;

$stmt = db()->prepare("SELECT * FROM sync_runs $whereSql ORDER BY id DESC LIMIT " . RUNS_PER_PAGE . " OFFSET $offset");
$stmt->execute($params);
$runs = $stmt->fetchAll();

$filters = array_filter(['q' => $search, 'status' => $status, 'trigger' => $trigger], fn ($value) => $value !== '');
$listUrl = fn (array $changes) => list_url('admin_sync_runs', $filters, $changes);

$chips = [];
if ($search !== '') {
    $chips[] = ['Search', '“' . $search . '”', $listUrl(['q' => null, 'page' => null])];
}
if ($status !== '') {
    $chips[] = ['Status', RUN_STATUSES[$status], $listUrl(['status' => null, 'page' => null])];
}
if ($trigger !== '') {
    $chips[] = ['Trigger', RUN_TRIGGERS[$trigger], $listUrl(['trigger' => null, 'page' => null])];
}

admin_header('Sync history', "$total " . plural($total, 'run') . ' of the Discogs sync, newest first.');
?>

<form class="filterbar" id="filters" method="get" action="<?= e(url('admin_sync_runs')) ?>">
  <?= filter_search($search, 'Status, message, log…') ?>
  <?= filter_select('status', 'Status', RUN_STATUSES, $status) ?>
  <?= filter_select('trigger', 'Trigger', RUN_TRIGGERS, $trigger) ?>

  <noscript><div class="field"><button type="submit" class="ghost">Filter</button></div></noscript>
</form>

<?= filter_chips($chips, url('admin_sync_runs'), 'Clear every filter') ?>

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
            <td><?= e(RUN_TRIGGERS[$run['trigger_source']] ?? $run['trigger_source']) ?></td>
            <td>
              <?= run_status_pill($run, RUN_STATUSES[$run['status']] ?? $run['status'], ['error', 'partial']) ?>
              <?php if ($run['message']): ?>
                <small class="run-message"><?= e($run['message']) ?></small>
              <?php endif; ?>
            </td>
            <td class="hide-sm"><?= (int) $run['collection_seen'] ?></td>
            <td class="hide-sm"><?= (int) $run['wantlist_seen'] ?></td>
            <td class="hide-sm"><?= run_details_cell($run) ?></td>
            <td class="right hide-sm"><?= (int) $run['api_calls'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <?= pagination($pageNo, $pages, fn (int $page) => $listUrl(['page' => $page])) ?>
  <?php endif; ?>
</div>

<?php admin_footer('admin-filters'); ?>
