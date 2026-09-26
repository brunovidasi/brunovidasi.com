<?php

/**
 * The two "not on the shelf yet" lists: the Discogs wantlist, synced, and the
 * hunting list, typed in by hand for records Discogs doesn't have.
 */

require_once __DIR__ . '/../includes/admin.php';

require_login();

if (is_post()) {
    csrf_verify();

    $title = post('manual_title');
    if ($title === '') {
        flash('Give it a title at least.', 'error');
    } else {
        db()->prepare("
            INSERT INTO items (source, manual_title, manual_artist, media_kind, media_kind_locked, notes, is_visible)
            VALUES ('searching', ?, ?, ?, 1, ?, 1)
        ")->execute([$title, nullable(post('manual_artist')), posted_media_kind('other'), nullable(post('notes'))]);
        flash('Added to the hunting list.');
    }

    redirect('admin_wantlist');
}

$wanted = items_query("WHERE i.source = 'wantlist' AND i.missing_since IS NULL ORDER BY r.primary_artist COLLATE NOCASE, " . RELEASE_DATE_SQL);
$hunting = items_query("WHERE i.source = 'searching' ORDER BY i.created_at DESC");

admin_header('Wantlist', count($wanted) . ' on the Discogs wantlist, ' . count($hunting) . ' being hunted by hand.');
?>

<div class="card">
  <h2>Still hunting</h2>
  <p>Anything not on Discogs — a bootleg, a regional pressing, something you only know by description.</p>

  <form method="post">
    <?= csrf_field() ?>
    <div class="grid-fields">
      <div class="field">
        <label for="manual_title">Title</label>
        <input type="text" id="manual_title" name="manual_title" required>
      </div>
      <div class="field">
        <label for="manual_artist">Artist</label>
        <input type="text" id="manual_artist" name="manual_artist">
      </div>
      <div class="field">
        <label for="media_kind">Format</label>
        <select id="media_kind" name="media_kind"><?= options_html(MEDIA_KINDS) ?></select>
      </div>
      <div class="field">
        <label for="notes">Note</label>
        <input type="text" id="notes" name="notes" placeholder="Japanese press with the obi, ideally">
      </div>
    </div>
    <button type="submit" class="gold small">Add</button>
  </form>

  <?php if ($hunting): ?>
    <table class="table spaced" data-sortable>
      <thead>
        <tr>
          <th data-sort>Album</th>
          <th data-sort>Artist</th>
          <th data-sort>Format</th>
          <th data-sort class="hide-sm">Note</th>
          <th class="right"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($hunting as $row): ?>
          <?php $editUrl = url('admin_item?id=' . (int) $row['id']); ?>
          <tr>
            <td class="title"><b><a href="<?= e($editUrl) ?>"><?= e(item_title($row)) ?></a></b></td>
            <td><?= e(item_artist($row)) ?></td>
            <td><?= kind_badge($row['media_kind']) ?></td>
            <td class="hide-sm dim"><?= e($row['notes']) ?></td>
            <td class="right"><?= edit_button($editUrl) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="card">
  <h2>From the Discogs wantlist</h2>
  <p>Synced with everything else. Add or remove them on Discogs; edit here to add your own notes.</p>

  <?php if (!$wanted): ?>
    <p class="empty">Nothing yet — run a sync, or add something to your wantlist on Discogs.</p>
  <?php else: ?>
    <table class="table" data-sortable>
      <thead>
        <tr>
          <th class="thumb"></th>
          <th data-sort>Album</th>
          <th data-sort>Artist</th>
          <th data-sort>Format</th>
          <th data-sort class="hide-sm">Year</th>
          <th class="right"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($wanted as $row): ?>
          <?php $editUrl = url('admin_item?id=' . (int) $row['id']); ?>
          <tr>
            <?= thumb_cell($row, placeholder: false) ?>
            <td class="title"><b><a href="<?= e($editUrl) ?>"><?= e(item_title($row)) ?></a></b></td>
            <td><?= e(item_artist($row)) ?></td>
            <td><?= kind_badge($row['media_kind']) ?></td>
            <td class="hide-sm" data-sort-value="<?= e(item_sort_date($row)) ?>"><?= year_cell($row) ?></td>
            <td class="right"><?= edit_button($editUrl) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php admin_footer('admin-table'); ?>
