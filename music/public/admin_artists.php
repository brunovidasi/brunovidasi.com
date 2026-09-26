<?php

require_once __DIR__ . '/../includes/admin.php';

require_login();

if (is_post()) {
    csrf_verify();

    $id = (int) post('id');

    if (post('action') === 'delete' && $id) {
        // Its eras and rules go with it; the records stay and are simply not filed on a page.
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

    $matchNames = array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', post('match_names'))))) ?: [$name];

    // A typed address wins over one ticked in the picker. Blank is "automatic".
    $heroCover = trim(post('hero_cover_url')) ?: trim(post('hero_cover'));
    $heroCover = preg_match('#^https?://\S+$#i', $heroCover) ? $heroCover : null;

    $values = [
        'slug'         => slugify(post('slug') ?: $name),
        'name'         => $name,
        'match_names'  => json_encode($matchNames, JSON_UNESCAPED_UNICODE),
        'tagline'      => nullable(post('tagline')),
        'intro'        => nullable(post('intro')),
        'accent'       => nullable(post('accent')),
        'position'     => (int) post('position'),
        'is_published' => posted_flag('is_published'),
    ];

    $slugTaken = db()->prepare('SELECT 1 FROM artists WHERE slug = ? AND id <> ?');
    $slugTaken->execute([$values['slug'], $id]);
    if ($slugTaken->fetchColumn() || is_site_path($values['slug'])) {
        flash("The URL \"{$values['slug']}\" is already taken. Pick another one.", 'error');
        redirect('admin_artists?edit=' . ($id ?: 'new'));
    }

    if ($id) {
        $values['hero_cover'] = $heroCover;
        $sets = implode(', ', array_map(fn ($column) => "$column = ?", array_keys($values)));
        db()->prepare("UPDATE artists SET $sets WHERE id = ?")->execute([...array_values($values), $id]);
        flash('Saved ' . $name . '.');
    } else {
        $columns = implode(', ', array_keys($values));
        $marks = implode(', ', array_fill(0, count($values), '?'));
        db()->prepare("INSERT INTO artists ($columns) VALUES ($marks)")->execute(array_values($values));
        flash('Added ' . $name . '. Give it some eras next.');
    }

    // New names mean new filing.
    assign_items_to_artists_and_eras();
    redirect('admin_artists');
}

$artists = all_artists();
$editing = (int) query('edit') ? artist_by_id((int) query('edit')) : null;
$counts = artist_record_counts();

// The covers of this artist's records, one of each, to pick the header picture from.
$covers = [];
if ($editing) {
    foreach (public_items('collection', ['i.artist_id = ?'], [$editing['id']]) as $row) {
        $covers[item_thumb($row)] ??= item_title($row);
    }
    unset($covers['']);
}

admin_header(
    'Artists & eras',
    'Each of these gets its own page, split into eras.',
    $editing ? '' : '<a class="btn gold" href="' . e(url('admin_artists?edit=new')) . '">Add an artist</a>'
);
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
          <div class="hint">The page lives at <?= e(base_url()) ?>/<em>this</em>.</div>
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

      <?php if ($editing): ?>
        <?php $picked = (string) ($editing['hero_cover'] ?? ''); ?>
        <h3 class="sub">Picture on the header</h3>
        <p class="hint">The small round cover on <?= e($editing['name']) ?>'s link at the top of the site. Left on automatic it is the first record on their page with a cover.</p>
        <div class="picker artist-covers">
          <?= picker_none('hero_cover', $picked === '', 'Automatic', 'default') ?>
          <?php foreach ($covers as $thumb => $title): ?>
            <?= picker_choice('radio', 'hero_cover', $thumb, $picked === $thumb, $thumb, $title, '', $title) ?>
          <?php endforeach; ?>
        </div>
        <div class="field">
          <label for="hero_cover_url">Or any picture, by address</label>
          <input type="text" id="hero_cover_url" name="hero_cover_url" placeholder="https://…"
                 value="<?= e($picked !== '' && !isset($covers[$picked]) ? $picked : '') ?>">
          <div class="hint">Filled in, this wins over the picker. Square works best; it is cropped round.</div>
        </div>
      <?php else: ?>
        <p class="hint">Once the page is saved you can choose the picture on its header link here.</p>
      <?php endif; ?>

      <div class="field">
        <label for="match_names">Discogs names</label>
        <textarea id="match_names" name="match_names" placeholder="One per line"><?= e(implode("\n", json_column($editing['match_names'] ?? null, [$editing['name'] ?? '']))) ?></textarea>
        <div class="hint">Every spelling that belongs on this page — a record credited to any of them is filed here. Discogs' "(2)" suffixes are stripped automatically.</div>
      </div>

      <?= check_box('is_published', (bool) ($editing['is_published'] ?? 1), 'Live on the site') ?>

      <div class="form-actions">
        <button type="submit" class="gold">Save</button>
        <a class="btn ghost" href="<?= e(url('admin_artists')) ?>">Cancel</a>
        <?php if ($editing): ?>
          <button type="submit" name="action" value="delete" class="danger small push-right"
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
        <?php $picture = hero_artist_cover($artist); ?>
        <tr>
          <td class="title">
            <?php if ($picture !== ''): ?>
              <img class="pill-pic" src="<?= e($picture) ?>" alt="" width="34" height="34" loading="lazy">
            <?php endif; ?>
            <b><?= e($artist['name']) ?></b>
            <small><?= e($artist['tagline']) ?></small>
          </td>
          <td><a href="<?= e(url($artist['slug'])) ?>" target="_blank" rel="noopener">/<?= e($artist['slug']) ?> ↗</a></td>
          <td class="hide-sm"><?= (int) ($counts[$artist['id']] ?? 0) ?></td>
          <td class="hide-sm"><?= count(eras_for_artist((int) $artist['id'])) ?></td>
          <td class="right">
            <?php if (!$artist['is_published']): ?><span class="pill">hidden</span><?php endif; ?>
            <a class="btn ghost small" href="<?= e(url('admin_eras?artist=' . (int) $artist['id'])) ?>">Eras</a>
            <?= edit_button(url('admin_artists?edit=' . (int) $artist['id'])) ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php admin_footer(); ?>
