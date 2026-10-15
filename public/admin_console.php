<?php

/** Adds a console, or changes one: its model, region, serial and what its label says. */

require_once __DIR__ . '/../includes/admin.php';

require_login();

$id = query('id', post('original_id'));
$hw = $id !== '' ? find_hardware($id) : null;
if ($id !== '' && !$hw) {
    admin_not_found("There is no console with the id \"$id\".");
}
$isNew = $hw === null;
$hw ??= ['id' => '', 'console' => query('console', 'switch')];
$errors = [];

if (is_post()) {
    csrf_verify();

    if (post('action') === 'delete' && !$isNew) {
        delete_hardware($id);
        flash("{$hw['name']} was deleted.");
        redirect('admin/consoles');
    }

    $new = ['id' => $hw['id']];
    foreach (array_keys(HARDWARE_FIELDS) as $key) {
        [$value, $error] = hardware_from_post($key, $_POST['h'][$key] ?? null);
        if ($error) {
            $errors[] = $error;
        }
        $new[$key] = $value;
    }

    if (!$errors) {
        if ($isNew) {
            $new['id'] = new_hardware_id($new['name']);
        }
        save_hardware($new, $isNew);
        flash($isNew ? "{$new['name']} was added." : "{$new['name']} was saved.");
        redirect('admin/console?id=' . rawurlencode($new['id']));
    }

    // show the form again with what was typed
    $hw = $new;
}

admin_header($isNew ? 'Add a console' : $hw['name'], $isNew ? 'A console you own, with what is on its label.' : console_name($hw['console']) . ' · ' . $hw['id']);

foreach ($errors as $error) {
    echo flash_box($error, 'error');
}
?>

<form method="post" class="game-form" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="original_id" value="<?= e($isNew ? '' : $id) ?>">

  <div class="edit-layout">
    <section class="card group">
      <div class="fields">
        <?php foreach (array_keys(HARDWARE_FIELDS) as $key): ?>
          <?= hardware_input($key, $hw[$key] ?? null) ?>
        <?php endforeach; ?>
      </div>
    </section>

    <aside class="edit-side">
      <?php if ($photo = $hw['photo'] ?? null): ?>
        <section class="card console-photo">
          <?= hardware_photo($hw, 'photo') ?>
          <p class="muted small">
            <?php if (!empty($photo['source']) && safe_http_url($photo['source'])): ?><a href="<?= e($photo['source']) ?>" target="_blank" rel="noopener">Photo</a><?php else: ?>Photo<?php endif; ?>
            <?= e(implode(' · ', array_filter([$photo['author'] ?? null, $photo['license'] ?? null]))) ?>
            <?php if (!empty($photo['note'])): ?><br><?= e($photo['note']) ?><?php endif; ?>
          </p>
        </section>
      <?php endif; ?>
      <?php if (!empty($hw['specs'])): ?>
        <section class="card">
          <h2>Specs</h2>
          <dl class="specs">
            <?php foreach ($hw['specs'] as $label => $value): ?>
              <dt><?= e($label) ?></dt><dd><?= e(is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE)) ?></dd>
            <?php endforeach; ?>
          </dl>
        </section>
      <?php endif; ?>
    </aside>
  </div>

  <div class="save-bar">
    <button type="submit" name="action" value="save" class="primary"><?= $isNew ? 'Add the console' : 'Save' ?></button>
    <a class="button ghost" href="<?= e(url('admin/consoles')) ?>">Back to the consoles</a>
    <?php if (!$isNew): ?>
      <button type="submit" name="action" value="delete" class="danger" formnovalidate data-confirm="Delete <?= e($hw['name']) ?>? This cannot be undone.">Delete</button>
    <?php endif; ?>
  </div>
</form>

<?php admin_footer(); ?>
