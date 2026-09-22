<?php

/**
 * One artist's eras, and the Discogs ids that file records into them.
 *
 * A rule is normally added from an item's own page ("put every pressing of this
 * album in that era"), which is why the box here accepts a pasted Discogs URL
 * as readily as a bare id — it's the same job done from the other end.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$artist = artist_by_id((int) query('artist'));
if ($artist === null) {
    flash('Pick an artist first.', 'error');
    redirect('admin_artists');
}

/** "https://www.discogs.com/master/11126-The-Fame" or "11126" — both work. */
function parse_discogs_id(string $input): array
{
    $input = trim($input);

    if (preg_match('#discogs\.com/(?:[a-z]{2}/)?(master|release)/(\d+)#i', $input, $m)) {
        return [strtolower($m[1]), (int) $m[2]];
    }
    if (preg_match('/^\[?m(\d+)\]?$/i', $input, $m)) {
        return ['master', (int) $m[1]];
    }
    if (ctype_digit($input)) {
        // A bare number is a master id: that is what an era is usually built
        // from, and it catches every pressing rather than one.
        return ['master', (int) $input];
    }

    return ['', 0];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = post('action');

    if ($action === 'save_era') {
        $id = (int) post('era_id');
        $name = post('name');

        if ($name === '') {
            flash('An era needs a name.', 'error');
        } elseif ($id) {
            db()->prepare('UPDATE eras SET slug = ?, name = ?, years = ?, tagline = ?, position = ? WHERE id = ? AND artist_id = ?')
                ->execute([slugify(post('slug') ?: $name), $name, nullable(post('years')), nullable(post('tagline')), (int) post('position'), $id, $artist['id']]);
            flash('Saved ' . $name . '.');
        } else {
            db()->prepare('INSERT INTO eras (artist_id, slug, name, years, tagline, position) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$artist['id'], slugify(post('slug') ?: $name), $name, nullable(post('years')), nullable(post('tagline')), (int) post('position')]);
            flash('Added ' . $name . '.');
        }
    } elseif ($action === 'delete_era') {
        db()->prepare('DELETE FROM eras WHERE id = ? AND artist_id = ?')->execute([(int) post('era_id'), $artist['id']]);
        assign_items_to_artists_and_eras();
        flash('Era deleted. Its records are back to being filed automatically.');
    } elseif ($action === 'add_rule') {
        [$kind, $discogsId] = parse_discogs_id(post('discogs_ref'));

        if ($discogsId === 0) {
            flash("That doesn't look like a Discogs id or link.", 'error');
        } else {
            db()->prepare('
                INSERT INTO era_rules (era_id, kind, discogs_id, rank) VALUES (?, ?, ?, ?)
                ON CONFLICT(kind, discogs_id) DO UPDATE SET era_id = excluded.era_id
            ')->execute([(int) post('era_id'), $kind, $discogsId, (int) post('rank')]);
            assign_items_to_artists_and_eras();
            flash("Filed $kind $discogsId into that era.");
        }
    } elseif ($action === 'delete_rule') {
        db()->prepare('DELETE FROM era_rules WHERE id = ?')->execute([(int) post('rule_id')]);
        assign_items_to_artists_and_eras();
        flash('Rule removed.');
    }

    redirect('admin_eras?artist=' . $artist['id']);
}

$eras = eras_for_artist((int) $artist['id']);
$counts = era_counts((int) $artist['id']);
$editing = (int) query('edit') ? era_by_id((int) query('edit')) : null;

$unfiled = (int) (function () use ($artist) {
    $stmt = db()->prepare("SELECT COUNT(*) FROM items WHERE artist_id = ? AND era_id IS NULL AND source = 'collection' AND missing_since IS NULL");
    $stmt->execute([$artist['id']]);
    return $stmt->fetchColumn();
})();

$pageTitle = $artist['name'] . ' — eras';
$pageIntro = count($eras) . ' eras, ' . array_sum($counts) . ' records filed, ' . $unfiled . ' waiting.';
$pageActions = '<a class="btn ghost" href="' . e(url('admin_artists')) . '">← All artists</a>'
    . ' <a class="btn ghost" href="' . e(url($artist['slug'])) . '" target="_blank" rel="noopener">View page ↗</a>';

require __DIR__ . '/../includes/admin_layout_top.php';
?>

<?php if ($unfiled): ?>
  <div class="flash ok">
    <?= $unfiled ?> <?= $unfiled === 1 ? 'record is' : 'records are' ?> on this page but in no era — they show under "More <?= e($artist['name']) ?>" at the end.
    <a href="<?= e(url('admin_items?artist=' . (int) $artist['id'] . '&state=noera')) ?>">Sort them out →</a>
  </div>
<?php endif; ?>

<div class="card">
  <h2><?= $editing ? 'Edit era' : 'Add an era' ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_era">
    <input type="hidden" name="era_id" value="<?= $editing ? (int) $editing['id'] : '' ?>">

    <div class="grid-fields">
      <div class="field">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="<?= e($editing['name'] ?? '') ?>" placeholder="Born This Way" required>
      </div>
      <div class="field">
        <label for="years">Years</label>
        <input type="text" id="years" name="years" value="<?= e($editing['years'] ?? '') ?>" placeholder="2011–2012">
      </div>
      <div class="field">
        <label for="position">Order</label>
        <input type="number" id="position" name="position" value="<?= (int) ($editing['position'] ?? count($eras)) ?>">
      </div>
      <div class="field">
        <label for="slug">URL fragment</label>
        <input type="text" id="slug" name="slug" value="<?= e($editing['slug'] ?? '') ?>" placeholder="born-this-way">
      </div>
    </div>

    <div class="field">
      <label for="tagline">Tagline</label>
      <input type="text" id="tagline" name="tagline" value="<?= e($editing['tagline'] ?? '') ?>" placeholder="Born This Way · Judas · The Edge of Glory">
    </div>

    <div class="form-actions">
      <button type="submit" class="gold"><?= $editing ? 'Save era' : 'Add era' ?></button>
      <?php if ($editing): ?><a class="btn ghost" href="<?= e(url('admin_eras?artist=' . (int) $artist['id'])) ?>">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<?php foreach ($eras as $era): ?>
  <?php
  $stmt = db()->prepare('SELECT * FROM era_rules WHERE era_id = ? ORDER BY rank, id');
  $stmt->execute([$era['id']]);
  $rules = $stmt->fetchAll();
  ?>
  <div class="card">
    <div class="page-head" style="margin-bottom:0.8rem;">
      <div>
        <h2 style="font-size:1.1rem;"><?= e($era['name']) ?> <span style="opacity:0.5;font-size:0.85rem;"><?= e($era['years']) ?></span></h2>
        <p style="margin-top:0.2rem;"><?= e($era['tagline']) ?></p>
      </div>
      <div class="actions">
        <span class="pill"><?= (int) ($counts[$era['id']] ?? 0) ?> records</span>
        <a class="btn ghost small" href="<?= e(url('admin_items?artist=' . (int) $artist['id'] . '&era=' . (int) $era['id'])) ?>">Show them</a>
        <a class="btn ghost small" href="<?= e(url('admin_eras?artist=' . (int) $artist['id'] . '&edit=' . (int) $era['id'])) ?>">Edit</a>
        <form method="post" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete_era">
          <input type="hidden" name="era_id" value="<?= (int) $era['id'] ?>">
          <button type="submit" class="danger small" onclick="return confirm('Delete the <?= e($era['name']) ?> era?')">Delete</button>
        </form>
      </div>
    </div>

    <?php if ($rules): ?>
      <table class="table">
        <tbody>
          <?php foreach ($rules as $rule): ?>
            <tr>
              <td style="width:5.5rem;"><span class="pill"><?= e($rule['kind']) ?></span></td>
              <td>
                <a href="https://www.discogs.com/<?= e($rule['kind']) ?>/<?= (int) $rule['discogs_id'] ?>" target="_blank" rel="noopener">
                  #<?= (int) $rule['discogs_id'] ?> ↗
                </a>
              </td>
              <td class="hide-sm" style="opacity:0.55;">order <?= (int) $rule['rank'] ?></td>
              <td class="right">
                <form method="post" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete_rule">
                  <input type="hidden" name="rule_id" value="<?= (int) $rule['id'] ?>">
                  <button type="submit" class="ghost small">Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <form method="post" class="filters" style="margin-top:0.9rem;margin-bottom:0;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_rule">
      <input type="hidden" name="era_id" value="<?= (int) $era['id'] ?>">
      <div class="field grow">
        <label for="ref<?= (int) $era['id'] ?>">Add a master or release</label>
        <input type="text" id="ref<?= (int) $era['id'] ?>" name="discogs_ref" placeholder="Paste a Discogs link, or a master id">
      </div>
      <div class="field" style="min-width:90px;">
        <label for="rank<?= (int) $era['id'] ?>">Order</label>
        <input type="number" id="rank<?= (int) $era['id'] ?>" name="rank" value="<?= count($rules) ?>">
      </div>
      <button type="submit" class="ghost">Add</button>
    </form>
  </div>
<?php endforeach; ?>

<?php if (!$eras): ?>
  <div class="card"><p class="empty">No eras yet. Add the first one above — then open any record by this artist and use "put every pressing of this album in that era" to fill it.</p></div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/admin_layout_bottom.php'; ?>
