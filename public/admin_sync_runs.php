<?php

/** Every sync, newest first: when, which platform, how it went and what it changed. */

require_once __DIR__ . '/../includes/admin.php';

require_login();

$platform = query('platform');
$status = query('status');
$page = max(1, (int) query('page', '1'));
$per = 50;

$where = [];
$args = [];
if (isset(PLATFORMS[$platform])) {
    $where[] = 'platform = ?';
    $args[] = $platform;
}
if (in_array($status, ['ok', 'error', 'reverted', 'running'], true)) {
    $where[] = 'status = ?';
    $args[] = $status;
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = db()->prepare("SELECT COUNT(*) FROM sync_runs $sqlWhere");
$stmt->execute($args);
$total = (int) $stmt->fetchColumn();

$stmt = db()->prepare("SELECT * FROM sync_runs $sqlWhere ORDER BY id DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$stmt->execute($args);
$runs = $stmt->fetchAll();

admin_header('Sync History', 'Every sync, what it brought in and what it changed. A platform\'s latest sync can be undone.');
?>

<form class="filters card" method="get" action="<?= e(url('admin/sync_runs')) ?>" data-autosubmit>
  <select name="platform" aria-label="Platform"><?= options_html(array_map(fn ($p) => $p['name'], PLATFORMS), $platform, 'Every platform') ?></select>
  <select name="status" aria-label="Result"><?= options_html(['ok' => 'OK', 'error' => 'Failed', 'reverted' => 'Undone', 'running' => 'Running'], $status, 'Any result') ?></select>
  <noscript><button type="submit">Filter</button></noscript>
</form>

<div class="card flush">
  <?php if (!$runs): ?>
    <p class="empty">No syncs yet<?= $where ? ' like that' : '' ?>. The dashboard has a button for each platform.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table runs" data-sort="runs">
      <thead><tr><th data-first="desc">When</th><th>Platform</th><th>Result</th><th data-first="desc">Added</th><th data-first="desc">Tied</th><th data-first="desc">Changed</th><th data-first="desc">To tie</th><th>Started by</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($runs as $r):
          $secs = $r['finished_at'] ? to_timestamp($r['finished_at']) - to_timestamp($r['started_at']) : null; ?>
        <tr>
          <td data-sort="<?= e($r['started_at']) ?>"><a href="<?= e(url('admin/sync_run?id=' . $r['id'])) ?>"><?= e(format_datetime($r['started_at'])) ?></a><div class="muted small"><?= e(time_ago($r['started_at'])) ?><?= $secs !== null ? ' · ' . $secs . ' s' : '' ?></div></td>
          <td><span class="dot <?= e($r['platform']) ?>"></span> <?= e(PLATFORMS[$r['platform']]['name'] ?? $r['platform']) ?></td>
          <td><?= status_pill($r['status']) ?><?php if ($r['status'] === 'error'): ?><div class="error-text small"><?= e(mb_strimwidth((string) $r['message'], 0, 90, '…')) ?></div><?php endif; ?></td>
          <td><?= $r['games_added'] ?: '' ?></td>
          <td><?= $r['games_linked'] ?: '' ?></td>
          <td><?= ($r['records_changed'] + $r['games_changed']) ?: '' ?></td>
          <td><?= $r['unmatched'] ?: '' ?></td>
          <td><?= e(ucfirst($r['trigger_source'])) ?></td>
          <td><a href="<?= e(url('admin/sync_run?id=' . $r['id'])) ?>">Details</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php if ($total > $per): ?>
  <nav class="pager">
    <?php if ($page > 1): ?><a href="<?= e(url('admin/sync_runs?' . http_build_query(['platform' => $platform, 'status' => $status, 'page' => $page - 1]))) ?>">← Newer</a><?php endif; ?>
    <span>Page <?= $page ?> of <?= (int) ceil($total / $per) ?></span>
    <?php if ($page * $per < $total): ?><a href="<?= e(url('admin/sync_runs?' . http_build_query(['platform' => $platform, 'status' => $status, 'page' => $page + 1]))) ?>">Older →</a><?php endif; ?>
  </nav>
<?php endif; ?>

<?php admin_footer(); ?>
