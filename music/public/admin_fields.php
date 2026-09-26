<?php

/**
 * Which facts the drawer shows, per format. An unticked fact is left out of the
 * API response, not just hidden.
 */

require_once __DIR__ . '/../includes/admin.php';

require_login();

const FIELD_GROUPS = ['mine' => 'Yours', 'discogs' => 'From Discogs'];

if (is_post()) {
    csrf_verify();

    $config = [];
    foreach (array_keys(MEDIA_KINDS) as $kind) {
        foreach (fields_for_kind($kind) as $key) {
            $config[$kind][$key] = isset($_POST['show'][$kind][$key]);
        }
    }

    set_setting('drawer_fields', $config);
    flash('Saved. The site picks it up on the next page load.');
    redirect('admin_fields');
}

$config = drawer_field_config();

admin_header('Drawer fields', 'What the drawer shows when a record is clicked on the site.');
?>

<form method="post">
  <?= csrf_field() ?>

  <div class="card">
    <div class="matrix-wrap">
      <table class="matrix">
        <thead>
          <tr>
            <th>Fact</th>
            <th class="all-col" title="Tick or untick this fact for every format that has it">All</th>
            <?php foreach (MEDIA_KINDS as $kindLabel): ?>
              <th><?= e($kindLabel) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach (FIELD_GROUPS as $group => $groupLabel): ?>
            <tr class="group-row"><th colspan="<?= 2 + count(MEDIA_KINDS) ?>"><?= e($groupLabel) ?></th></tr>
            <?php foreach (field_catalog() as $key => $def): ?>
              <?php if ($def['group'] !== $group) { continue; } ?>
              <tr>
                <td><?= e($def['label']) ?></td>
                <td class="all-col"><input type="checkbox" class="all-toggle" aria-label="<?= e($def['label']) ?> for every format"></td>
                <?php foreach (MEDIA_KINDS as $kind => $kindLabel): ?>
                  <?php if (isset($config[$kind][$key])): ?>
                    <td><input type="checkbox" name="show[<?= e($kind) ?>][<?= e($key) ?>]" value="1"<?= $config[$kind][$key] ? ' checked' : '' ?> aria-label="<?= e($def['label'] . ' on ' . $kindLabel) ?>"></td>
                  <?php else: ?>
                    <td class="na" title="Doesn't apply to <?= e($kindLabel) ?>">–</td>
                  <?php endif; ?>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="form-actions">
      <button type="submit" class="gold">Save</button>
      <span class="aside">A fact with nothing in it is left out anyway, ticked or not.</span>
    </div>
  </div>
</form>

<?php admin_footer('admin-fields'); ?>
