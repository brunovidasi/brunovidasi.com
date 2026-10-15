<?php

/** The consoles themselves: every machine, each a link to its edit page. Admin only. */

require_once __DIR__ . '/../includes/admin.php';

require_login();

$all = all_hardware();

admin_header('Consoles', count($all) . ' ' . plural(count($all), 'console') . ', with what is on each one\'s label. Only the admin sees these.', '<a class="button primary" href="' . e(url('admin/console')) . '">+ Add a console</a>');
?>

<div class="card flush">
  <?php if (!$all): ?>
    <p class="empty">No consoles yet. <a href="<?= e(url('admin/console')) ?>">Add one</a>, or run <code>php cron/import_hardware.php</code>.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table consoles" data-sort="consoles">
      <thead>
        <tr><th></th><th>Console</th><th>Model</th><th>Colour</th><th>Region</th><th data-first="desc">Year</th><th>Serial</th></tr>
      </thead>
      <tbody>
      <?php $order = array_flip(array_keys(CONSOLES)); foreach ($all as $h): ?>
        <tr>
          <td class="c-thumb"><a href="<?= e(url('admin/console?id=' . rawurlencode($h['id']))) ?>" tabindex="-1"><?= hardware_photo($h, 'thumb console') ?></a></td>
          <td class="c-title" data-sort="<?= sprintf('%02d', $order[$h['console']] ?? 99) . ' ' . e($h['name']) ?>">
            <a href="<?= e(url('admin/console?id=' . rawurlencode($h['id']))) ?>"><?= e($h['name']) ?></a>
            <div class="muted small"><?= e(implode(' · ', array_filter([$h['edition'], $h['storage']]))) ?: e(console_short($h['console'])) ?></div>
          </td>
          <td><?= e($h['model'] ?? '—') ?></td>
          <td><?= e($h['color'] ?? '—') ?></td>
          <td><?= e(implode(' ', array_filter([$h['region'], $h['video']])) ?: '—') ?><?php if ($h['region_free'] !== null): ?><div class="muted small"><?= $h['region_free'] ? 'Region-free' : 'Region-locked' ?></div><?php endif; ?></td>
          <td><?= e($h['year'] ?? '—') ?></td>
          <td><?= $h['serial'] ? '<code>' . e($h['serial']) . '</code>' : '—' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php admin_footer(); ?>
