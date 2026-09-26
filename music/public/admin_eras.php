<?php

/**
 * One artist's eras, and the Discogs ids that file records into them. A rule
 * is usually made from a record's own page; here a pasted link works as well.
 */

require_once __DIR__ . '/../includes/admin.php';

require_login();

$artist = artist_by_id((int) query('artist'));
if ($artist === null) {
    flash('Pick an artist first.', 'error');
    redirect('admin_artists');
}

$artistId = (int) $artist['id'];
$erasUrl = 'admin_eras?artist=' . $artistId;

if (is_post()) {
    csrf_verify();

    $eraId = (int) post('era_id');

    switch (post('action')) {
        case 'save_era':
            $name = post('name');
            $values = [slugify(post('slug') ?: $name), $name, nullable(post('years')), nullable(post('tagline')), (int) post('position')];

            $slugTaken = db()->prepare('SELECT 1 FROM eras WHERE artist_id = ? AND slug = ? AND id <> ?');
            $slugTaken->execute([$artistId, $values[0], $eraId]);

            if ($name === '') {
                flash('An era needs a name.', 'error');
            } elseif ($slugTaken->fetchColumn()) {
                flash("Another era already has the URL fragment \"{$values[0]}\". Pick another one.", 'error');
            } elseif ($eraId) {
                db()->prepare('UPDATE eras SET slug = ?, name = ?, years = ?, tagline = ?, position = ? WHERE id = ? AND artist_id = ?')
                    ->execute([...$values, $eraId, $artistId]);
                flash('Saved ' . $name . '.');
            } else {
                db()->prepare('INSERT INTO eras (slug, name, years, tagline, position, artist_id) VALUES (?, ?, ?, ?, ?, ?)')
                    ->execute([...$values, $artistId]);
                flash('Added ' . $name . '.');
            }
            break;

        case 'delete_era':
            db()->prepare('DELETE FROM eras WHERE id = ? AND artist_id = ?')->execute([$eraId, $artistId]);
            assign_items_to_artists_and_eras();
            flash('Era deleted. Its records are back to being filed automatically.');
            break;

        case 'add_rule':
            [$kind, $discogsId] = discogs_reference_from_input(post('discogs_ref'));
            if ($discogsId === 0) {
                flash("That doesn't look like a Discogs id or link.", 'error');
            } else {
                save_era_rule($eraId, $kind, $discogsId, (int) post('rank'));
                assign_items_to_artists_and_eras();
                flash("Filed $kind $discogsId into that era.");
            }
            break;

        case 'delete_rule':
            db()->prepare('DELETE FROM era_rules WHERE id = ?')->execute([(int) post('rule_id')]);
            assign_items_to_artists_and_eras();
            flash('Rule removed.');
            break;
    }

    redirect($erasUrl);
}

$eras = eras_for_artist($artistId);
$counts = era_counts($artistId);
$editing = (int) query('edit') ? era_by_id((int) query('edit')) : null;

$stmt = db()->prepare("SELECT COUNT(*) FROM items WHERE artist_id = ? AND era_id IS NULL AND source = 'collection' AND missing_since IS NULL");
$stmt->execute([$artistId]);
$unfiled = (int) $stmt->fetchColumn();

/** A small form that posts one action about one era or rule. */
function era_action_form(string $action, array $fields, string $button): string
{
    $hidden = '';
    foreach (['action' => $action, ...$fields] as $name => $value) {
        $hidden .= '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '">';
    }

    return '<form method="post" class="inline">' . csrf_field() . $hidden . $button . '</form>';
}

admin_header(
    $artist['name'] . ' — eras',
    count($eras) . ' eras, ' . array_sum($counts) . ' records filed, ' . $unfiled . ' waiting.',
    '<a class="btn ghost" href="' . e(url('admin_artists')) . '">← All artists</a>'
        . ' <a class="btn ghost" href="' . e(url($artist['slug'])) . '" target="_blank" rel="noopener">View page ↗</a>'
);
?>

<?php if ($unfiled): ?>
  <div class="flash ok">
    <?= $unfiled ?> <?= $unfiled === 1 ? 'record is' : 'records are' ?> on this page but in no era — they show under "More <?= e($artist['name']) ?>" at the end.
    <a href="<?= e(url('admin_items?artist=' . $artistId . '&state=noera')) ?>">Sort them out →</a>
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
      <?php if ($editing): ?><a class="btn ghost" href="<?= e(url($erasUrl)) ?>">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<?php foreach ($eras as $era): ?>
  <?php
  $eraId = (int) $era['id'];
  $rules = era_rules($eraId);
  ?>
  <div class="card">
    <div class="page-head era-heading">
      <div>
        <h2><?= e($era['name']) ?> <span class="years"><?= e($era['years']) ?></span></h2>
        <p><?= e($era['tagline']) ?></p>
      </div>
      <div class="actions">
        <span class="pill"><?= (int) ($counts[$eraId] ?? 0) ?> records</span>
        <a class="btn ghost small" href="<?= e(url('admin_items?artist=' . $artistId . '&era=' . $eraId)) ?>">Show them</a>
        <a class="btn ghost small" href="<?= e(url($erasUrl . '&edit=' . $eraId)) ?>">Edit</a>
        <?= era_action_form('delete_era', ['era_id' => $eraId], '<button type="submit" class="danger small" onclick="return confirm(' . e(json_encode("Delete the {$era['name']} era?", JSON_UNESCAPED_UNICODE)) . ')">Delete</button>') ?>
      </div>
    </div>

    <?php if ($rules): ?>
      <table class="table">
        <tbody>
          <?php foreach ($rules as $rule): ?>
            <tr>
              <td class="rule-kind"><span class="pill"><?= e($rule['kind']) ?></span></td>
              <td>
                <a href="https://www.discogs.com/<?= e($rule['kind']) ?>/<?= (int) $rule['discogs_id'] ?>" target="_blank" rel="noopener">
                  #<?= (int) $rule['discogs_id'] ?> ↗
                </a>
              </td>
              <td class="hide-sm faint">order <?= (int) $rule['rank'] ?></td>
              <td class="right">
                <?= era_action_form('delete_rule', ['rule_id' => (int) $rule['id']], '<button type="submit" class="ghost small">Remove</button>') ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <form method="post" class="filters add-rule">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_rule">
      <input type="hidden" name="era_id" value="<?= $eraId ?>">
      <div class="field grow">
        <label for="ref<?= $eraId ?>">Add a master or release</label>
        <input type="text" id="ref<?= $eraId ?>" name="discogs_ref" placeholder="Paste a Discogs link, or a master id">
      </div>
      <div class="field rank">
        <label for="rank<?= $eraId ?>">Order</label>
        <input type="number" id="rank<?= $eraId ?>" name="rank" value="<?= count($rules) ?>">
      </div>
      <button type="submit" class="ghost">Add</button>
    </form>
  </div>
<?php endforeach; ?>

<?php if (!$eras): ?>
  <div class="card"><p class="empty">No eras yet. Add the first one above — then open any record by this artist and use "put every pressing of this album in that era" to fill it.</p></div>
<?php endif; ?>

<?php admin_footer(); ?>
