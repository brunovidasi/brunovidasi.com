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

require __DIR__ . '/../includes/admin_layout_top.php';
?>

<form method="post">
  <?= csrf_field() ?>

  <div class="card">
    <div class="field-grid">
      <?php foreach (MEDIA_KINDS as $kind => $kindLabel): ?>
        <div>
          <h3><?= e($kindLabel) ?></h3>

          <?php
          $groups = ['mine' => 'Yours', 'discogs' => 'From Discogs'];
          foreach ($groups as $group => $groupLabel):
              $keys = array_filter(fields_for_kind($kind), fn ($key) => $catalog[$key]['group'] === $group);
              if (!$keys) { continue; }
          ?>
            <div class="group-label"><?= e($groupLabel) ?></div>
            <?php foreach ($keys as $key): ?>
              <label class="check">
                <input type="checkbox" name="show[<?= e($kind) ?>][<?= e($key) ?>]" value="1"<?= $config[$kind][$key] ? ' checked' : '' ?>>
                <?= e($catalog[$key]['label']) ?>
              </label>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="form-actions">
      <button type="submit" class="gold">Save</button>
      <span style="font-size:0.82rem;opacity:0.6;">A fact with nothing in it is left out anyway, ticked or not.</span>
    </div>
  </div>
</form>

<?php require __DIR__ . '/../includes/admin_layout_bottom.php'; ?>
