<?php

/**
 * One sync: what it changed, copy by copy and record by record, with the value
 * before and after; its log; what the platform answered (to download); and, for
 * a platform's latest sync, a way to undo it.
 */

require_once __DIR__ . '/../includes/admin.php';

require_login();

$stmt = db()->prepare('SELECT * FROM sync_runs WHERE id = ?');
$stmt->execute([(int) query('id', post('id'))]);
$run = $stmt->fetch();
if (!$run) {
    admin_not_found('There is no such sync.');
}

if (is_post() && post('action') === 'revert') {
    csrf_verify();
    try {
        flash(revert_sync_run((int) $run['id']));
    } catch (Throwable $e) {
        flash($e->getMessage(), 'error');
    }
    redirect('admin_sync_run?id=' . $run['id']);
}

if (query('snapshot') === '1') {
    $stmt = db()->prepare('SELECT raw_gz FROM sync_snapshots WHERE run_id = ?');
    $stmt->execute([$run['id']]);
    $gz = $stmt->fetchColumn();
    if ($gz === false) {
        admin_not_found('What the platform answered in this sync is no longer kept (Settings → Sync says how many are).');
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $run['platform'] . '-sync-' . $run['id'] . '.json"');
    echo gzdecode(is_resource($gz) ? stream_get_contents($gz) : $gz);
    exit;
}

$stmt = db()->prepare('SELECT * FROM sync_changes WHERE run_id = ? ORDER BY id');
$stmt->execute([$run['id']]);
$changes = $stmt->fetchAll();

$stmt = db()->prepare('SELECT bytes FROM sync_snapshots WHERE run_id = ?');
$stmt->execute([$run['id']]);
$snapshot = $stmt->fetchColumn();

// grouped as the page shows them
$groups = ['added' => [], 'linked' => [], 'filled' => [], 'kept' => [], 'record' => [], 'new_record' => [], 'ambiguous' => [], 'unmatched' => [], 'missing' => []];
foreach ($changes as $c) {
    $key = $c['game_id'] ?? ($c['collection'] . ':' . $c['ext_id']);
    $groups[$c['kind']][$key][] = $c;
}
$titles = [
    'added'      => 'Copies added',
    'linked'     => 'Copies tied to ' . platform_name($run['platform']),
    'filled'     => 'Fields filled in',
    'kept'       => 'Your values kept (the platform says otherwise)',
    'record'     => 'Numbers that changed',
    'new_record' => 'New in ' . platform_name($run['platform']),
    'ambiguous'  => 'Could be several copies: to tie by hand',
    'unmatched'  => 'No copy found',
    'missing'    => 'No longer in ' . platform_name($run['platform']),
];
$fieldLabels = array_map(fn ($f) => $f['label'], FIELDS);
$types = [];
foreach (PLATFORM_FIELDS[$run['platform']] ?? [] as $collection => $paths) {
    foreach ($paths as $path => [$label, $type]) {
        $fieldLabels[$path] = $label;
        $types[$path] = $type;
    }
}

$show = function (?string $json, ?string $field) use (&$types): string {
    $v = from_json($json);
    if ($v === null) {
        return '<span class="muted">—</span>';
    }
    if (isset($types[$field])) {
        return e(format_platform_value($v, $types[$field]));
    }
    if (is_bool($v)) {
        return $v ? 'yes' : 'no';
    }

    return is_scalar($v) ? e($v) : '<code>' . e(mb_strimwidth(json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 0, 140, '…')) . '</code>';
};

$secs = $run['finished_at'] ? to_timestamp($run['finished_at']) - to_timestamp($run['started_at']) : null;
$canRevert = can_revert_run($run);

admin_header(
    platform_name($run['platform']) . ' sync, ' . format_datetime($run['started_at']),
    ucfirst($run['trigger_source']) . ($secs !== null ? " · took $secs s" : '') . ' · ' . $run['api_calls'] . ' ' . plural((int) $run['api_calls'], 'request'),
    '<a class="button ghost" href="' . e(url('admin_sync_runs')) . '">All syncs</a>'
);
?>

<section class="card run-summary">
  <div class="run-status">
    <?= status_pill($run['status']) ?>
    <p><?= e($run['message']) ?></p>
  </div>
  <div class="stats small-stats">
    <div class="stat"><b><?= (int) $run['records_seen'] ?></b><span>Seen</span></div>
    <div class="stat"><b><?= (int) $run['games_added'] ?></b><span>Added</span></div>
    <div class="stat"><b><?= (int) $run['games_linked'] ?></b><span>Tied</span></div>
    <div class="stat"><b><?= (int) $run['records_changed'] ?></b><span>Changed</span></div>
    <div class="stat"><b><?= (int) $run['unmatched'] ?></b><span>To tie</span></div>
  </div>
  <div class="run-actions">
    <?php if ($snapshot !== false): ?>
      <a class="button ghost" href="<?= e(url('admin_sync_run?id=' . $run['id'] . '&snapshot=1')) ?>">Download what <?= e(PLATFORMS[$run['platform']]['short']) ?> answered <span class="muted">(<?= e(format_bytes((int) $snapshot)) ?>)</span></a>
    <?php endif; ?>
    <?php if ($canRevert): ?>
      <form method="post" class="inline">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $run['id'] ?>">
        <button type="submit" name="action" value="revert" class="danger" data-confirm="Undo this sync? The copies it added are removed, the fields it filled and tied go back to what they were (unless changed since), and every number it changed is put back.">Undo this sync</button>
      </form>
    <?php elseif ($run['status'] === 'reverted'): ?>
      <span class="muted">Undone <?= e(time_ago($run['reverted_at'])) ?>.</span>
    <?php elseif ($run['status'] === 'ok'): ?>
      <span class="muted small">Only <?= e(PLATFORMS[$run['platform']]['short']) ?>'s latest sync can be undone.</span>
    <?php endif; ?>
  </div>
</section>

<?php foreach ($groups as $kind => $items): if (!$items) continue; ?>
  <section class="card">
    <h2><?= e($titles[$kind]) ?> <span class="count"><?= count($items) ?></span></h2>
    <div class="table-wrap">
      <table class="table compact changes">
        <tbody>
        <?php foreach ($items as $key => $rows): $first = $rows[0]; ?>
          <tr>
            <th scope="row">
              <?php if ($first['game_id'] && game_exists($first['game_id'])): ?>
                <a href="<?= e(url('admin_game?id=' . rawurlencode($first['game_id']))) ?>"><?= e($first['label'] ?? $first['game_id']) ?></a>
              <?php else: ?>
                <?= e($first['label'] ?? $first['ext_id']) ?>
              <?php endif; ?>
              <div class="muted small"><code><?= e($first['game_id'] ?? $first['ext_id']) ?></code></div>
            </th>
            <td>
              <?php foreach ($rows as $c): ?>
                <div class="change">
                  <?php if ($kind === 'added'): ?>
                    A new <?= e(FORMATS[from_json($c['new_json'])['format'] ?? ''] ?? 'copy') ?> on <?= e(console_name(from_json($c['new_json'])['console'] ?? '')) ?>.
                  <?php elseif (in_array($kind, ['ambiguous'], true)): ?>
                    Could be: <?= e(implode(', ', (array) from_json($c['new_json']))) ?>
                  <?php elseif ($c['field']): ?>
                    <span class="what"><?= e($fieldLabels[$c['field']] ?? $c['field']) ?></span>
                    <span class="from"><?= $show($c['old_json'], $c['field']) ?></span> → <span class="to"><?= $show($c['new_json'], $c['field']) ?></span>
                  <?php endif; ?>
                  <?php if ($c['note']): ?><span class="muted small"><?= e($c['note']) ?></span><?php endif; ?>
                </div>
              <?php endforeach; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endforeach; ?>

<?php if (!$changes && $run['status'] === 'ok'): ?>
  <section class="card"><p class="empty">Nothing changed: everything was as the last sync left it.</p></section>
<?php endif; ?>

<?php if ($run['log']): ?>
  <section class="card">
    <h2>Log</h2>
    <pre class="log"><?= e($run['log']) ?></pre>
  </section>
<?php endif; ?>

<?php admin_footer(); ?>
