<?php

/**
 * Editing one record.
 *
 * The form is in the order the work happens: the handful of fields Bruno
 * actually fills in are at the top, where they can be typed and saved without
 * scrolling; filing (format, artist page, era, visibility) is next; the
 * pictures after that; and everything Discogs supplied is at the bottom, read
 * only, as a reference for what an empty box up top will fall back to.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$isNew = query('new') === 'searching';
$item = null;

if ($isNew) {
    // Nothing exists yet, but every box on the form still has to render, so the
    // page is given an empty row of the right shape.
    $item = blank_item_row();
} else {
    $item = item_by_id((int) query('id'));
    if ($item === null) {
        http_response_code(404);
        $pageTitle = 'Not found';
        require __DIR__ . '/../includes/admin_layout_top.php';
        echo '<div class="card"><p class="empty">There is no record with that id. It may have been deleted.</p></div>';
        require __DIR__ . '/../includes/admin_layout_bottom.php';
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (post('action') === 'delete' && $item) {
        db()->prepare('DELETE FROM items WHERE id = ?')->execute([$item['id']]);
        flash('Deleted "' . item_title($item) . '".');
        redirect('admin_items?source=' . $item['source']);
    }

    $kind = isset(MEDIA_KINDS[post('media_kind')]) ? post('media_kind') : 'other';

    if ($isNew) {
        db()->prepare("
            INSERT INTO items (source, manual_title, manual_artist, media_kind, media_kind_locked, is_visible)
            VALUES ('searching', ?, ?, ?, 1, 1)
        ")->execute([post('manual_title'), post('manual_artist'), $kind]);
        $item = item_by_id((int) db()->lastInsertId());
        flash('Added to the hunting list.');
    }

    // Only the boxes this kind's form actually showed are written, so switching
    // a record from CD to vinyl doesn't wipe the Media value it had as a CD.
    $editable = array_values(array_filter(
        fields_for_kind($kind),
        fn ($key) => field_catalog()[$key]['group'] === 'mine'
    ));

    $sets = [];
    $params = [];
    foreach ($editable as $key) {
        $sets[] = "$key = ?";
        $params[] = nullable(post($key));
    }

    $eraId = (int) post('era_id') ?: null;
    $artistId = (int) post('artist_id') ?: null;

    $sets = array_merge($sets, [
        'media_kind = ?', 'media_kind_locked = ?', 'artist_id = ?', 'era_id = ?', 'era_locked = ?',
        'cover_url = ?', 'disc_url = ?', 'is_visible = ?', 'is_featured = ?', 'sort_rank = ?',
        'manual_title = ?', 'manual_artist = ?',
        "updated_at = datetime('now')",
    ]);
    $params = array_merge($params, [
        $kind,
        post('media_kind_locked') !== '' ? 1 : 0,
        $artistId,
        $eraId,
        // An era chosen by hand is locked, so the next sync's automatic filing
        // can't move it back. Clearing the era unlocks it again.
        $eraId !== null ? 1 : 0,
        nullable(post('cover_url')),
        nullable(post('disc_url')),
        post('is_visible') !== '' ? 1 : 0,
        post('is_featured') !== '' ? 1 : 0,
        (int) post('sort_rank'),
        nullable(post('manual_title')),
        nullable(post('manual_artist')),
    ]);
    $params[] = $item['id'];

    db()->prepare('UPDATE items SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);

    // "Every pressing of this album" — writes an era rule keyed on the master,
    // so the CD, the vinyl and next year's reissue all land in the same era on
    // the next sync without anyone opening them.
    if ($eraId !== null && post('apply_to_master') !== '' && $item['discogs_id']) {
        $useMaster = !empty($item['master_id']);
        db()->prepare('
            INSERT INTO era_rules (era_id, kind, discogs_id, rank) VALUES (?, ?, ?, ?)
            ON CONFLICT(kind, discogs_id) DO UPDATE SET era_id = excluded.era_id
        ')->execute([
            $eraId,
            $useMaster ? 'master' : 'release',
            $useMaster ? (int) $item['master_id'] : (int) $item['discogs_id'],
            $useMaster ? 500 : 1000,
        ]);
        assign_items_to_artists_and_eras();
        flash('Saved, and every pressing of this one now files into that era.');
    } else {
        flash('Saved.');
    }

    redirect('admin_item?id=' . $item['id']);
}

$kind = (string) $item['media_kind'];
$release = $item['discogs_id'] !== null ? $item : null;
$catalog = field_catalog();
$images = json_column($item['images_json'] ?? null);
$artists = all_artists();
$eras = $item['artist_id'] ? eras_for_artist((int) $item['artist_id']) : [];

/** The Discogs value showing through an empty box, for the hint under it. */
function fallback_hint(array $item, ?array $release, string $key): string
{
    if (trim((string) ($item[$key] ?? '')) !== '' || $release === null) {
        return '';
    }

    // item_field_value() with the field blanked is exactly "what would the site
    // show if I left this empty?".
    $probe = $item;
    $probe[$key] = null;
    $value = item_field_value($probe, $release, $key);

    if ($value === null || $value === '' || $value === []) {
        return '';
    }

    return is_array($value) ? implode(', ', $value) : (string) $value;
}

$pageTitle = item_title($item);
$pageIntro = item_artist($item) . ($item['year'] ? ' · ' . $item['year'] : '');
$pageActions = '<a class="btn ghost" href="' . e(url('admin_items?source=' . $item['source'])) . '">← Back to the list</a>'
    . ($item['discogs_id'] ? ' <a class="btn ghost" target="_blank" rel="noopener" href="https://www.discogs.com/release/' . (int) $item['discogs_id'] . '">On Discogs ↗</a>' : '');
$pageScript = 'js/admin-item.js';

require __DIR__ . '/../includes/admin_layout_top.php';
?>

<?php if ($item['missing_since']): ?>
  <div class="flash error">This copy wasn't in the last sync of your Discogs collection (since <?= e(format_date($item['missing_since'])) ?>). It's kept here because it holds your own notes — delete it below if it really is gone.</div>
<?php endif; ?>

<?php if ($item['discogs_id'] && $item['detail_fetched_at'] === null): ?>
  <div class="flash ok">Discogs' full detail for this release hasn't been fetched yet, so the boxes below have little to fall back on. It arrives with the next sync.</div>
<?php endif; ?>

<form method="post" action="<?= e($isNew ? url('admin_item?new=searching') : url('admin_item?id=' . (int) $item['id'])) ?>">
  <?= csrf_field() ?>

  <div class="cards">
    <div>
      <div class="card">
        <h2>Yours</h2>
        <p>Anything left empty falls back to what Discogs says, shown underneath it.</p>

        <?php if ($item['source'] === 'searching'): ?>
          <div class="grid-fields">
            <div class="field">
              <label for="manual_title">Title</label>
              <input type="text" id="manual_title" name="manual_title" value="<?= e($item['manual_title']) ?>">
            </div>
            <div class="field">
              <label for="manual_artist">Artist</label>
              <input type="text" id="manual_artist" name="manual_artist" value="<?= e($item['manual_artist']) ?>">
            </div>
          </div>
        <?php else: ?>
          <input type="hidden" name="manual_title" value="<?= e($item['manual_title']) ?>">
          <input type="hidden" name="manual_artist" value="<?= e($item['manual_artist']) ?>">
        <?php endif; ?>

        <div class="grid-fields">
          <?php foreach (primary_fields_for_kind($kind) as $key): ?>
            <?php if ($key === 'notes') { continue; } ?>
            <?php $hint = fallback_hint($item, $release, $key); ?>
            <div class="field">
              <label for="f_<?= e($key) ?>"><?= e($catalog[$key]['label']) ?></label>
              <?php if ($key === 'item_type'): ?>
                <select id="f_<?= e($key) ?>" name="<?= e($key) ?>">
                  <option value="">— from Discogs —</option>
                  <?php foreach (ITEM_TYPES as $type): ?>
                    <option value="<?= e($type) ?>"<?= $item['item_type'] === $type ? ' selected' : '' ?>><?= e(ucfirst($type)) ?></option>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <input type="text" id="f_<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($item[$key]) ?>"
                       <?= $key === 'vinyl_size' ? 'list="vinylSizes"' : '' ?>
                       placeholder="<?= e($hint !== '' ? $hint : '') ?>">
              <?php endif; ?>
              <?php if ($hint !== ''): ?>
                <div class="inherited">Discogs: <b><?= e($hint) ?></b></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <datalist id="vinylSizes">
          <?php foreach (VINYL_SIZES as $size): ?><option value="<?= e($size) ?>"><?php endforeach; ?>
        </datalist>

        <div class="field">
          <label for="f_notes">Notes</label>
          <textarea id="f_notes" name="notes" placeholder="Where you found it, what's odd about this pressing, who signed it…"><?= e($item['notes']) ?></textarea>
        </div>

        <?php $secondary = secondary_fields_for_kind($kind); ?>
        <?php if ($secondary): ?>
          <details>
            <summary style="cursor:pointer;font-size:0.85rem;opacity:0.7;margin-bottom:0.8rem;">More fields</summary>
            <div class="grid-fields">
              <?php foreach ($secondary as $key): ?>
                <?php $hint = fallback_hint($item, $release, $key); ?>
                <div class="field">
                  <label for="s_<?= e($key) ?>"><?= e($catalog[$key]['label']) ?></label>
                  <?php if ($key === 'item_type'): ?>
                    <select id="s_<?= e($key) ?>" name="<?= e($key) ?>">
                      <option value="">— from Discogs —</option>
                      <?php foreach (ITEM_TYPES as $type): ?>
                        <option value="<?= e($type) ?>"<?= $item['item_type'] === $type ? ' selected' : '' ?>><?= e(ucfirst($type)) ?></option>
                      <?php endforeach; ?>
                    </select>
                  <?php else: ?>
                    <input type="text" id="s_<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($item[$key]) ?>">
                  <?php endif; ?>
                  <?php if ($hint !== ''): ?><div class="inherited">Discogs: <b><?= e($hint) ?></b></div><?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </details>
        <?php endif; ?>
      </div>

      <div class="card">
        <h2>Pictures</h2>
        <p>Pick the sleeve the site shows<?= $kind === 'vinyl' ? '' : ', and which image is the disc itself' ?>.</p>

        <?php if (!$images): ?>
          <p class="empty">Discogs hasn't given any images for this release yet — they arrive with the full detail on the next sync.</p>
        <?php else: ?>
          <h3 style="font-size:0.8rem;text-transform:uppercase;letter-spacing:0.08em;opacity:0.6;margin-bottom:0.6rem;">Cover</h3>
          <div class="picker">
            <label>
              <input type="radio" name="cover_url" value=""<?= $item['cover_url'] ? '' : ' checked' ?>>
              <span class="none">Discogs' own</span>
              <small>default</small>
            </label>
            <?php foreach ($images as $image): ?>
              <?php if (empty($image['uri'])) { continue; } ?>
              <label>
                <input type="radio" name="cover_url" value="<?= e($image['uri']) ?>"<?= $item['cover_url'] === $image['uri'] ? ' checked' : '' ?>>
                <img src="<?= e($image['uri150'] ?? $image['uri']) ?>" alt="" loading="lazy">
                <small><?= e($image['type'] ?? '') ?></small>
              </label>
            <?php endforeach; ?>
          </div>

          <?php if ($kind !== 'vinyl'): ?>
            <h3 style="font-size:0.8rem;text-transform:uppercase;letter-spacing:0.08em;opacity:0.6;margin:1.2rem 0 0.6rem;">Disc art</h3>
            <div class="picker">
              <label>
                <input type="radio" name="disc_url" value=""<?= $item['disc_url'] ? '' : ' checked' ?>>
                <span class="none">None</span>
                <small>plain disc</small>
              </label>
              <?php foreach ($images as $image): ?>
                <?php if (empty($image['uri'])) { continue; } ?>
                <label>
                  <input type="radio" name="disc_url" value="<?= e($image['uri']) ?>"<?= $item['disc_url'] === $image['uri'] ? ' checked' : '' ?>>
                  <img src="<?= e($image['uri150'] ?? $image['uri']) ?>" alt="" loading="lazy">
                  <small><?= e($image['type'] ?? '') ?></small>
                </label>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <input type="hidden" name="disc_url" value="<?= e($item['disc_url']) ?>">
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <div>
      <div class="card">
        <h2>Where it lives</h2>

        <div class="field">
          <label for="media_kind">Format</label>
          <select id="media_kind" name="media_kind">
            <?php foreach (MEDIA_KINDS as $value => $label): ?>
              <option value="<?= e($value) ?>"<?= $kind === $value ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <label class="check" style="margin-top:0.5rem;">
            <input type="checkbox" name="media_kind_locked" value="1"<?= $item['media_kind_locked'] ? ' checked' : '' ?>>
            Keep this even if a sync disagrees
          </label>
        </div>

        <div class="field">
          <label for="artist_id">Artist page</label>
          <select id="artist_id" name="artist_id">
            <option value="">Not on an artist page</option>
            <?php foreach ($artists as $artist): ?>
              <option value="<?= (int) $artist['id'] ?>"<?= (int) $item['artist_id'] === (int) $artist['id'] ? ' selected' : '' ?>><?= e($artist['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="hint">Worked out from the credits on each sync; change it only to override that.</div>
        </div>

        <div class="field">
          <label for="era_id">Era</label>
          <select id="era_id" name="era_id">
            <option value="">— filed automatically —</option>
            <?php foreach ($eras as $era): ?>
              <option value="<?= (int) $era['id'] ?>"<?= (int) $item['era_id'] === (int) $era['id'] ? ' selected' : '' ?>>
                <?= e($era['name']) ?><?= $era['years'] ? ' (' . e($era['years']) . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (!$eras && $item['artist_id']): ?>
            <div class="hint">That artist has no eras yet — <a href="<?= e(url('admin_eras?artist=' . (int) $item['artist_id'])) ?>">add some</a>.</div>
          <?php endif; ?>
        </div>

        <?php if ($item['discogs_id']): ?>
          <label class="check">
            <input type="checkbox" name="apply_to_master" value="1">
            Put every pressing of this album in that era
          </label>
          <div class="hint" style="margin:0.3rem 0 1rem;">
            <?= $item['master_id']
                ? 'Files the CD, the vinyl and any reissue together from the next sync on.'
                : 'This release has no master on Discogs, so the rule covers this release only.' ?>
          </div>
        <?php endif; ?>

        <label class="check">
          <input type="checkbox" name="is_visible" value="1"<?= $item['is_visible'] ? ' checked' : '' ?>>
          Show on the site
        </label>
        <label class="check">
          <input type="checkbox" name="is_featured" value="1"<?= $item['is_featured'] ? ' checked' : '' ?>>
          Favourite
        </label>

        <div class="field" style="margin-top:0.9rem;">
          <label for="sort_rank">Sort weight</label>
          <input type="number" id="sort_rank" name="sort_rank" value="<?= (int) $item['sort_rank'] ?>">
          <div class="hint">Higher floats to the front of the shelf. Leave at 0 for the normal order.</div>
        </div>

        <div class="form-actions">
          <button type="submit" class="gold">Save</button>
          <a class="btn ghost" href="<?= e(url('admin_items?source=' . $item['source'])) ?>">Cancel</a>
          <button type="submit" name="action" value="delete" class="danger small" style="margin-left:auto;"
                  onclick="return confirm('Delete this record and everything you typed about it?')">Delete</button>
        </div>
      </div>

      <div class="card">
        <h2>From Discogs</h2>
        <p>Read only — a sync overwrites all of it.</p>
        <table class="table">
          <tbody>
            <?php
            $readonly = [
              'Release'   => $item['discogs_id'] ? '#' . $item['discogs_id'] : '—',
              'Master'    => $item['master_id'] ? '#' . $item['master_id'] : '—',
              'Title'     => $item['title'],
              'Artists'   => $item['artists_text'],
              'Year'      => $item['year'],
              'Released'  => $item['released_formatted'] ?: $item['released'],
              'Country'   => $item['country'],
              'Formats'   => $item['formats_text'],
              'Labels'    => implode(', ', labels_lines(json_column($item['labels_json']))),
              'Cat. no.'  => implode(', ', catalog_numbers(json_column($item['labels_json']))),
              'Barcode'   => $item['release_barcode'],
              'Genres'    => implode(', ', json_column($item['genres_json'])),
              'Styles'    => implode(', ', json_column($item['styles_json'])),
              'Added'     => format_date($item['date_added'], 'j M Y'),
              'Tracks'    => count(json_column($item['tracklist_json'])) ?: '—',
              'Images'    => count($images) ?: '—',
              'Detail'    => $item['detail_fetched_at'] ? time_ago($item['detail_fetched_at']) : 'not fetched yet',
            ];
            foreach ($readonly as $label => $value):
              if ($value === null || $value === '') { continue; }
            ?>
              <tr><td style="opacity:0.6;"><?= e($label) ?></td><td class="right"><?= e($value) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</form>

<?php
// Every artist's eras, keyed by artist id as a string so json_encode always
// writes an object (numeric keys starting at 1 would otherwise be ambiguous).
$eraOptions = [];
foreach ($artists as $artist) {
    $eraOptions[(string) $artist['id']] = array_map(
        fn ($era) => ['id' => (int) $era['id'], 'name' => $era['name'] . ($era['years'] ? " ({$era['years']})" : '')],
        eras_for_artist((int) $artist['id'])
    );
}
?>
<script id="eraOptions" type="application/json"><?= json_encode($eraOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT) ?></script>

<?php require __DIR__ . '/../includes/admin_layout_bottom.php'; ?>
