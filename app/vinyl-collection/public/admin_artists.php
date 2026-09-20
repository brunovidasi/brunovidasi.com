<?php

/**
 * The artists that get their own page. Four to begin with; nothing stops there
 * being more.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int) post('id');
    $action = post('action');

    if ($action === 'delete' && $id) {
        // Eras and their rules go with it (ON DELETE CASCADE); the items stay,
        // and the next sync simply finds no page to file them on.
        db()->prepare('DELETE FROM artists WHERE id = ?')->execute([$id]);
        assign_items_to_artists_and_eras();
        flash('Artist page deleted.');
        redirect('admin_artists');
    }

    $name = post('name');
    if ($name === '') {
        flash('An artist needs a name.', 'error');
        redirect('admin_artists');
    }

    // One name per line in the box; stored as JSON so the matcher can use them.
    $matchNames = array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', post('match_names')))));
    if (!$matchNames) {
        $matchNames = [$name];
    }

    $slug = slugify(post('slug') ?: $name);

    if ($id) {
        db()->prepare('UPDATE artists SET slug = ?, name = ?, match_names = ?, tagline = ?, intro = ?, accent = ?, position = ?, is_published = ? WHERE id = ?')
            ->execute([$slug, $name, json_encode($matchNames, JSON_UNESCAPED_UNICODE), nullable(post('tagline')), nullable(post('intro')), nullable(post('accent')), (int) post('position'), post('is_published') !== '' ? 1 : 0, $id]);
        flash('Saved ' . $name . '.');
    } else {
        db()->prepare('INSERT INTO artists (slug, name, match_names, tagline, intro, accent, position, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$slug, $name, json_encode($matchNames, JSON_UNESCAPED_UNICODE), nullable(post('tagline')), nullable(post('intro')), nullable(post('accent')), (int) post('position'), post('is_published') !== '' ? 1 : 0]);
        flash('Added ' . $name . '. Give it some eras next.');
    }

    // Names changed means the filing changed.
    assign_items_to_artists_and_eras();
    redirect('admin_artists');
}

$artists = all_artists();
$editing = (int) query('edit') ? artist_by_id((int) query('edit')) : null;

$counts = array_column(db()->query("
    SELECT artist_id, COUNT(*) AS n FROM items
     WHERE source = 'collection' AND missing_since IS NULL AND artist_id IS NOT NULL
     GROUP BY artist_id
")->fetchAll(), 'n', 'artist_id');

$pageTitle = 'Artists & eras';
$pageIntro = 'Each of these gets its own page, split into eras.';
$pageActions = $editing ? '' : '<a class="btn gold" href="' . e(url('admin_artists?edit=new')) . '">Add an artist</a>';

require __DIR__ . '/../includes/admin_layout_top.php';
?>

<?php if ($editing || query('edit') === 'new'): ?>
  <div class="card">
    <h2><?= $editing ? 'Edit ' . e($editing['name']) : 'New artist page' ?></h2>

    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $editing ? (int) $editing['id'] : '' ?>">

      <div class="grid-fields">
        <div class="field">
          <label for="name">Name</label>
          <input type="text" id="name" name="name" value="<?= e($editing['name'] ?? '') ?>" required>
        </div>
        <div class="field">
          <label for="slug">URL</label>
          <input type="text" id="slug" name="slug" value="<?= e($editing['slug'] ?? '') ?>" placeholder="lady-gaga">
          <div class="hint">The page lives at <?= e(rtrim(base_url(), '/')) ?>/<em>this</em>.</div>
        </div>
        <div class="field">
          <label for="accent">Accent colour</label>
          <input type="text" id="accent" name="accent" value="<?= e($editing['accent'] ?? '#C99A2E') ?>" placeholder="#C99A2E">
        </div>
        <div class="field">
          <label for="position">Order</label>
          <input type="number" id="position" name="position" value="<?= (int) ($editing['position'] ?? count($artists)) ?>">
        </div>
      </div>

      <div class="field">
        <label for="tagline">Tagline</label>
        <input type="text" id="tagline" name="tagline" value="<?= e($editing['tagline'] ?? '') ?>" placeholder="Every CD and vinyl in the collection, era by era.">
      </div>

      <div class="field">
        <label for="match_names">Discogs names</label>
        <textarea id="match_names" name="match_names" placeholder="One per line"><?= e(implode("\n", json_column($editing['match_names'] ?? null, [$editing['name'] ?? '']))) ?></textarea>
        <div class="hint">Every spelling that belongs on this page — a record credited to any of them is filed here. Discogs' "(2)" suffixes are stripped automatically.</div>
      </div>

      <label class="check">
        <input type="checkbox" name="is_published" value="1"<?= ($editing['is_published'] ?? 1) ? ' checked' : '' ?>>
        Live on the site
      </label>

      <div class="form-actions">
        <button type="submit" class="gold">Save</button>
        <a class="btn ghost" href="<?= e(url('admin_artists')) ?>">Cancel</a>
        <?php if ($editing): ?>
          <button type="submit" name="action" value="delete" class="danger small" style="margin-left:auto;"
                  onclick="return confirm('Delete this artist page and all its eras? The records stay in the collection.')">Delete page</button>
        <?php endif; ?>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="card">
  <table class="table">
    <thead>
      <tr><th>Artist</th><th>URL</th><th class="hide-sm">Records</th><th class="hide-sm">Eras</th><th class="right"></th></tr>
    </thead>
    <tbody>
      <?php foreach ($artists as $artist): ?>
        <?php $eras = eras_for_artist((int) $artist['id']); ?>
        <tr>
          <td class="title">
            <b><?= e($artist['name']) ?></b>
            <small><?= e($artist['tagline']) ?></small>
          </td>
          <td><a href="<?= e(url($artist['slug'])) ?>" target="_blank" rel="noopener">/<?= e($artist['slug']) ?> ↗</a></td>
          <td class="hide-sm"><?= (int) ($counts[$artist['id']] ?? 0) ?></td>
          <td class="hide-sm"><?= count($eras) ?></td>
          <td class="right">
            <?php if (!$artist['is_published']): ?><span class="pill">hidden</span><?php endif; ?>
            <a class="btn ghost small" href="<?= e(url('admin_eras?artist=' . (int) $artist['id'])) ?>">Eras</a>
            <a class="btn ghost small" href="<?= e(url('admin_artists?edit=' . (int) $artist['id'])) ?>">Edit</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/admin_layout_bottom.php'; ?>
