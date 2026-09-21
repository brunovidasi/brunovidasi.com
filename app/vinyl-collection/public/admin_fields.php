<?php

/**
 * Which facts the drawer shows, per format.
 *
 * A vinyl drawer and a CD drawer want different things on them — size and
 * colour against media and type — so the choice is made once per format rather
 * than once for everything. Whatever is unticked here is not merely hidden in
 * CSS: it never reaches the browser.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
$catalog = field_catalog();

$pageTitle = 'Drawer fields';
$pageIntro = 'What the drawer shows when a record is clicked on the site.';
$pageScript = 'js/admin-fields.js';

require __DIR__ . '/../includes/admin_layout_top.php';
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
          <?php
          $groups = ['mine' => 'Yours', 'discogs' => 'From Discogs'];
          foreach ($groups as $group => $groupLabel):
          ?>
            <tr class="group-row"><th colspan="<?= 2 + count(MEDIA_KINDS) ?>"><?= e($groupLabel) ?></th></tr>
            <?php foreach ($catalog as $key => $def): ?>
              <?php if ($def['group'] !== $group) { continue; } ?>
              <tr>
                <td><?= e($def['label']) ?></td>
                <td class="all-col"><input type="checkbox" class="all-toggle" aria-label="<?= e($def['label']) ?> for every format"></td>
                <?php foreach (MEDIA_KINDS as $kind => $kindLabel): ?>
                  <?php if (in_array($key, fields_for_kind($kind), true)): ?>
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
      <span style="font-size:0.82rem;opacity:0.6;">A fact with nothing in it is left out anyway, ticked or not.</span>
    </div>
  </div>
</form>

<?php require __DIR__ . '/../includes/admin_layout_bottom.php'; ?>
