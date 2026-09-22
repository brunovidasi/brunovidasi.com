<?php

/**
 * Editing one record.
 *
 * The form is in the order the work happens: the pictures come first, being
 * what identifies the record; then the handful of fields Bruno actually fills
 * in; filing (format, artist page, era, visibility) beside them; and everything
 * Discogs supplied is at the bottom, read only, as a reference for what an
 * empty box will fall back to. Save, Cancel, Delete and Sync are in the page
 * header rather than in any one card.
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

    if (post('action') === 'sync' && $item && !$isNew) {
        // A 429 makes the client wait out a whole rate-limit window.
        set_time_limit(150);
        try {
            flash(sync_one_item($item));
        } catch (DiscogsException $e) {
            flash('Sync failed: ' . $e->getMessage(), 'error');
        } catch (Throwable $e) {
            error_log('Single-item sync failed: ' . $e);
            flash('Sync failed' . (is_debug() ? ': ' . $e->getMessage() : '. Check the log.'), 'error');
        }
        redirect('admin_item?id=' . $item['id']);
    }

    $kind = isset(MEDIA_KINDS[post('media_kind')]) ? post('media_kind') : 'other';

    if ($isNew) {
        db()->prepare("
            INSERT INTO items (source, manual_title, manual_artist, media_kind, media_kind_locked, is_visible)
            VALUES ('searching', ?, ?, ?, 1, 1)
        ")->execute([post('manual_title'), post('manual_artist'), $kind]);
        $item = item_by_id((int) db()->lastInsertId());
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

    // Discogs' facts corrected on this record. The boxes are always on the form,
    // so an empty one means "use Discogs'" and clears an earlier correction.
    foreach (array_keys(OVERRIDE_FIELDS) as $key) {
        $sets[] = "$key = ?";
        $params[] = nullable(post($key));
    }

    // The discs: how many (blank = as Discogs says) and the picture on each. The
    // first picture is also kept in disc_url, which the list's "With a picture
    // disc" filter reads.
    $countChoice = post('disc_count');
    $discCount = ctype_digit($countChoice) ? min(MAX_DISCS, max(1, (int) $countChoice)) : null;
    $posted = is_array($_POST['disc_art'] ?? null) ? $_POST['disc_art'] : [];
    $discArt = [];
    for ($k = 0; $k < MAX_DISCS; $k++) {
        $url = is_string($posted[$k] ?? null) ? trim($posted[$k]) : '';
        $discArt[] = safe_http_url($url) ? $url : '';
    }
    if ($discCount !== null) {
        // A disc taken away takes its picture with it.
        $discArt = array_pad(array_slice($discArt, 0, $discCount), MAX_DISCS, '');
    }

    // The colour picked on each vinyl's swatch. Only a vinyl's form has swatches
    // (a CD has none, and switching one to vinyl shouldn't wipe a colour it
    // had), so any other kind keeps what is stored. The record-wide colour of
    // before is folded into the discs (the form showed it on each) and cleared,
    // or "Use automatic" on one disc would keep coming back.
    $discHex = array_fill(0, MAX_DISCS, null);
    $discTr = array_fill(0, MAX_DISCS, null);
    if ($kind === 'vinyl') {
        $postedHex = is_array($_POST['vinyl_hex'] ?? null) ? $_POST['vinyl_hex'] : [];
        $postedTr = is_array($_POST['vinyl_translucent'] ?? null) ? $_POST['vinyl_translucent'] : [];
        for ($k = 0; $k < MAX_DISCS; $k++) {
            $discHex[$k] = vinyl_hex(is_string($postedHex[$k] ?? null) ? $postedHex[$k] : null);
            $discTr[$k] = ['1' => true, '0' => false][is_string($postedTr[$k] ?? null) ? $postedTr[$k] : ''] ?? null;
        }
        $sets[] = 'vinyl_hex = NULL';
        $sets[] = 'vinyl_translucent = NULL';
    } else {
        ['hex' => $discHex, 'tr' => $discTr] = disc_colours($item);
    }
    if ($discCount !== null) {
        // A disc taken away takes its colour with it.
        $discHex = array_pad(array_slice($discHex, 0, $discCount), MAX_DISCS, null);
        $discTr = array_pad(array_slice($discTr, 0, $discCount), MAX_DISCS, null);
    }

    $discConfig = $discCount === null && !array_filter($discArt) && !array_filter($discHex) && !array_filter($discTr, 'is_bool')
        ? null
        : json_encode(['count' => $discCount, 'art' => $discArt, 'hex' => $discHex, 'tr' => $discTr], JSON_UNESCAPED_SLASHES);
    $firstDiscArt = array_values(array_filter($discArt))[0] ?? null;

    // Only a DVD's form offers the Case box; any other kind keeps it clear.
    $caseKind = $kind === 'dvd' && post('case_kind') === 'cd' ? 'cd' : null;

    $eraId = (int) post('era_id') ?: null;

    // "Automatic" leaves the artist page to the sync; anything else was chosen
    // by hand and is kept, "not on an artist page" included.
    $artistChoice = post('artist_id');
    $artistLocked = $artistChoice !== '' ? 1 : 0;
    $artistId = match ($artistChoice) {
        '' => $item['artist_id'],
        'none' => null,
        default => (int) $artistChoice ?: null,
    };

    $sets = array_merge($sets, [
        'media_kind = ?', 'media_kind_locked = ?', 'case_kind = ?', 'artist_id = ?', 'artist_locked = ?', 'era_id = ?', 'era_locked = ?',
        'cover_url = ?', 'disc_url = ?', 'disc_config = ?', 'is_visible = ?', 'is_featured = ?', 'sort_rank = ?',
        'manual_title = ?', 'manual_artist = ?',
        "updated_at = datetime('now')",
    ]);
    $params = array_merge($params, [
        $kind,
        // A format changed here is kept without having to say so: the sync
        // would otherwise put back what Discogs' formats suggest.
        (post('media_kind_locked') !== '' || $kind !== $item['media_kind']) ? 1 : 0,
        $caseKind,
        $artistId,
        $artistLocked,
        $eraId,
        // An era chosen by hand is locked, so the next sync's automatic filing
        // can't move it back. Clearing the era unlocks it again.
        $eraId !== null ? 1 : 0,
        nullable(post('cover_url')),
        $firstDiscArt,
        $discConfig,
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
    $refiled = false;
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
        $refiled = true;
    }

    // Back to the list it was opened from: it opens on the filters, sort and page
    // it was left on, so the next record is where it was. The message names the
    // record, since the page it appears on no longer shows it.
    $name = item_title(item_by_id((int) $item['id']));
    flash($isNew ? "Added \"$name\" to the hunting list."
        : ($refiled ? "Saved \"$name\", and every pressing of it now files into that era." : "Saved \"$name\"."));

    redirect('admin_items?source=' . $item['source']);
}

$kind = (string) $item['media_kind'];
$release = $item['discogs_id'] !== null ? $item : null;
$catalog = field_catalog();
$images = json_column($item['images_json'] ?? null);

// A disc from a box set has no release of its own, so its pictures are the
// box's: the cover and the disc art are picked from the box's gallery.
$box = !empty($item['parent_item_id']) ? item_by_id((int) $item['parent_item_id']) : null;
$pictures = $images ?: json_column($box['images_json'] ?? null);
$artists = all_artists();
$eras = $item['artist_id'] ? eras_for_artist((int) $item['artist_id']) : [];

// Discs: what Discogs' formats give (the "automatic" count), what has been set,
// and what each disc's picture is now. A record from before discs were set one
// by one has a single picture, shown on every disc until it is changed.
$discCfg = disc_config($item);
$derivedDiscs = count(item_discs(array_merge($item, ['disc_config' => null, 'disc_url' => null]), json_column($item['formats_json'] ?? null)));
$shownDiscs = max(1, $discCfg['count'] ?? $derivedDiscs);
$discArtNow = fn (int $k): string => $discCfg['set'] ? $discCfg['art'][$k] : trim((string) $item['disc_url']);

// What kind of disc sits at each of the three places, so only a vinyl gets a
// colour picker (a CD in the same sleeve has none), and the colour each shows.
$discTypes = array_column(item_discs(array_merge($item, ['disc_config' => json_encode(['count' => MAX_DISCS])]), json_column($item['formats_json'] ?? null)), 't');
$discColours = disc_colours($item);

/**
 * The Discogs value showing through an empty box, for the hint under it. With
 * $always, the value is given even where the box has been filled in, so a
 * correction can be compared with what it corrects.
 */
function fallback_hint(array $item, ?array $release, string $key, bool $always = false): string
{
    if ($release === null || (!$always && trim((string) ($item[$key] ?? '')) !== '')) {
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

// Save, Cancel and Delete live in the page header with Sync, out of the way of
// the fields. Save belongs to the big form by its id; Sync and Delete are forms
// of their own (a form can't nest), so neither can be triggered by pressing
// Enter in a box.
$selfUrl = e($isNew ? url('admin_item?new=searching') : url('admin_item?id=' . (int) $item['id']));
$actions = [];
if ($item['discogs_id']) {
    $actions[] = '<a class="btn ghost" target="_blank" rel="noopener" href="https://www.discogs.com/release/' . (int) $item['discogs_id'] . '">On Discogs ↗</a>';
    $actions[] = '<form method="post" action="' . $selfUrl . '" id="syncForm">' . csrf_field()
        . '<input type="hidden" name="action" value="sync">'
        . '<button type="submit" class="ghost" title="Refresh this record from Discogs. What you typed is never overwritten.">Sync with Discogs</button></form>';
}
if (!$isNew) {
    $actions[] = '<form method="post" action="' . $selfUrl . '" id="deleteForm">' . csrf_field()
        . '<input type="hidden" name="action" value="delete">'
        . '<button type="submit" class="danger">Delete</button></form>';
}
$actions[] = '<a class="btn ghost" href="' . e(url('admin_items?source=' . $item['source'])) . '">Cancel</a>';
$actions[] = '<button type="submit" form="itemForm" class="gold">Save</button>';
$pageActions = implode("\n", $actions);
$pageScript = 'js/admin-item.js';

require __DIR__ . '/../includes/admin_layout_top.php';
?>

<?php if ($item['missing_since']): ?>
  <div class="flash error">This copy wasn't in the last sync of your Discogs collection (since <?= e(format_date($item['missing_since'])) ?>). It's kept here because it holds your own notes — delete it with the button at the top if it really is gone.</div>
<?php endif; ?>

<?php if ($item['discogs_id'] && $item['detail_fetched_at'] === null): ?>
  <div class="flash ok">Discogs' full detail for this release hasn't been fetched yet, so the boxes below have little to fall back on. It arrives with the next sync.</div>
<?php endif; ?>

<form method="post" id="itemForm" action="<?= e($isNew ? url('admin_item?new=searching') : url('admin_item?id=' . (int) $item['id'])) ?>">
  <?= csrf_field() ?>

  <div class="card">
    <h2>Pictures</h2>
    <p>Pick the sleeve the site shows, then how many discs are inside and the picture on each<?= $kind === 'vinyl' ? ' (for a picture disc)' : '' ?>.</p>
    <?php if ($box && $pictures): ?>
      <p class="hint">This disc comes from <b><?= e(item_title($box)) ?></b>, so these are the box's pictures.</p>
    <?php endif; ?>

    <?php if (!$pictures): ?>
      <p class="empty">Discogs hasn't given any images for this release yet — they arrive with the full detail on the next sync.</p>
    <?php else: ?>
      <h3 class="sub">Cover</h3>
      <div class="picker">
        <label>
          <input type="radio" name="cover_url" value=""<?= $item['cover_url'] ? '' : ' checked' ?>>
          <span class="none">Discogs' own</span>
          <small>default</small>
        </label>
        <?php foreach ($pictures as $image): ?>
          <?php if (empty($image['uri'])) { continue; } ?>
          <label>
            <input type="radio" name="cover_url" value="<?= e($image['uri']) ?>"<?= $item['cover_url'] === $image['uri'] ? ' checked' : '' ?>>
            <img src="<?= e($image['uri150'] ?? $image['uri']) ?>" alt="" loading="lazy">
            <small><?= e($image['type'] ?? '') ?></small>
          </label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="discs" id="discs" data-derived="<?= (int) $derivedDiscs ?>">
      <h3 class="sub">Discs</h3>
      <div class="field disc-count">
        <label for="disc_count">Discs in the sleeve</label>
        <select id="disc_count" name="disc_count">
          <option value="">As Discogs says (<?= (int) $derivedDiscs ?>)</option>
          <?php for ($n = 1; $n <= MAX_DISCS; $n++): ?>
            <option value="<?= $n ?>"<?= $discCfg['count'] === $n ? ' selected' : '' ?>><?= $n ?> disc<?= $n > 1 ? 's' : '' ?></option>
          <?php endfor; ?>
        </select>
        <div class="hint">A 2-CD set is two discs on the shelf, each with its own picture below. Up to <?= MAX_DISCS ?>.</div>
      </div>

      <?php if ($pictures): ?>
        <?php for ($k = 0; $k < MAX_DISCS; $k++): ?>
          <?php
          $current = $discArtNow($k);
          $uris = array_column($pictures, 'uri');
          // A picture that is no longer in the gallery is still offered, so saving
          // the form doesn't quietly drop it.
          $extra = $current !== '' && !in_array($current, $uris, true) ? [['uri' => $current, 'type' => 'current']] : [];
          ?>
          <div class="disc-art" data-disc="<?= $k ?>"<?= $k >= $shownDiscs ? ' hidden' : '' ?>>
            <h4>Disc <?= $k + 1 ?> <span><?= $kind === 'vinyl' ? '— picture on the disc' : '— image printed on the disc' ?></span></h4>
            <div class="picker">
              <label>
                <input type="radio" name="disc_art[<?= $k ?>]" value=""<?= $current === '' ? ' checked' : '' ?><?= $k >= $shownDiscs ? ' disabled' : '' ?>>
                <span class="none">None</span>
                <small><?= $kind === 'vinyl' ? 'plain vinyl' : 'plain disc' ?></small>
              </label>
              <?php foreach (array_merge($extra, $pictures) as $image): ?>
                <?php if (empty($image['uri'])) { continue; } ?>
                <label>
                  <input type="radio" name="disc_art[<?= $k ?>]" value="<?= e($image['uri']) ?>"<?= $current === $image['uri'] ? ' checked' : '' ?><?= $k >= $shownDiscs ? ' disabled' : '' ?>>
                  <img src="<?= e($image['uri150'] ?? $image['uri']) ?>" alt="" loading="lazy">
                  <small><?= e($image['type'] ?? '') ?></small>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endfor; ?>
      <?php endif; ?>
    </div>
  </div>

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
                  <?php foreach (item_type_options($item['item_type']) as $type): ?>
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
              <?php if ($key === 'vinyl_color'): ?>
                <?php
                // What each disc is drawn in when nothing is picked: the keyword
                // match on the text above, which is what the site has been using.
                $colourText = (string) item_field_value($item, $release, 'vinyl_color');
                $autoHex = vinyl_color($colourText)['c'];
                $palette = [];
                foreach (VINYL_COLORS as [$word, $hex]) {
                    $palette[$hex] ??= $word;
                }
                ?>
                <div class="colour-pick" id="colourPick"
                     data-palette="<?= e(json_encode(VINYL_COLORS)) ?>"
                     data-discogs="<?= e(fallback_hint($item, $release, 'vinyl_color', true)) ?>"
                     data-fallback="<?= e(VINYL_FALLBACK_COLOR) ?>">
                  <?php foreach ($discTypes as $k => $type): ?>
                    <?php
                    if ($type !== 'v') { continue; }
                    $pickedHex = $discColours['hex'][$k];
                    $pickedTr = $discColours['tr'][$k];
                    $off = $k >= $shownDiscs;
                    ?>
                    <div class="colour-disc" data-disc="<?= $k ?>"<?= $off ? ' hidden' : '' ?>>
                      <h4 class="colour-disc-title">Disc <?= $k + 1 ?></h4>
                      <div class="colour-row">
                        <input type="color" class="colour-input" value="<?= e($pickedHex ?? $autoHex ?? VINYL_FALLBACK_COLOR) ?>" aria-label="Disc <?= $k + 1 ?> colour">
                        <input type="hidden" class="colour-hex" name="vinyl_hex[<?= $k ?>]" value="<?= e($pickedHex ?? '') ?>"<?= $off ? ' disabled' : '' ?>>
                        <button type="button" class="ghost small colour-reset">Use automatic</button>
                      </div>
                      <div class="colour-state" aria-live="polite"></div>
                      <div class="swatches">
                        <?php foreach ($palette as $hex => $word): ?>
                          <button type="button" class="swatch" data-hex="<?= e($hex) ?>" title="<?= e(ucfirst($word)) ?>" aria-label="<?= e(ucfirst($word)) ?>" style="background:<?= e($hex) ?>"></button>
                        <?php endforeach; ?>
                      </div>
                      <label for="vinylTranslucent<?= $k ?>" class="sub-label">Transparency</label>
                      <select id="vinylTranslucent<?= $k ?>" class="colour-clarity" name="vinyl_translucent[<?= $k ?>]"<?= $off ? ' disabled' : '' ?>>
                        <option value="">Automatic</option>
                        <option value="1"<?= $pickedTr === true ? ' selected' : '' ?>>Translucent</option>
                        <option value="0"<?= $pickedTr === false ? ' selected' : '' ?>>Opaque</option>
                      </select>
                    </div>
                  <?php endforeach; ?>
                  <div class="hint">Each disc on the shelf is drawn in its own colour, so a split or two-tone pressing can be set disc by disc (the number of discs is under Discs, above). Left on automatic, colour and transparency follow the text above ("Clear", "Transparent" and so on); set them here where that gets it wrong.</div>
                </div>
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
                      <?php foreach (item_type_options($item['item_type']) as $type): ?>
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

        <?php
        $isHunting = $item['source'] === 'searching';
        $corrected = array_filter(array_map(
            fn ($key) => trim((string) ($item[$key] ?? '')),
            array_merge(array_keys(OVERRIDE_FIELDS), $isHunting ? [] : ['manual_title', 'manual_artist'])
        ));
        $overrideLabels = ['labels' => 'Label', 'catalog_number' => 'Catalogue no.', 'formats' => 'Format details', 'genres' => 'Genres', 'styles' => 'Styles'];
        $overrideHelp = [
            'labels' => 'One per line.',
            'catalog_number' => 'One per line.',
            'formats' => 'What the list shows under Details, one per line: "LP", "Album", "Pink".',
            'genres' => 'Separated by commas.',
            'styles' => 'Separated by commas.',
        ];
        $discogsTracks = tracklist_to_text(json_column($item['tracklist_json'] ?? null));
        ?>
        <details<?= $corrected ? ' open' : '' ?>>
          <summary style="cursor:pointer;font-size:0.85rem;opacity:0.7;margin:0.4rem 0 0.8rem;">Correct what Discogs says</summary>
          <p class="hint" style="margin-bottom:0.8rem;">Anything typed here is shown instead of Discogs' and is kept through every sync. Leave a box empty to use Discogs'. Label, Catalogue no., Genres and Styles appear in the drawer only where they're switched on under <a href="<?= e(url('admin_fields')) ?>">Fields</a>.</p>

          <div class="grid-fields">
            <?php if (!$isHunting): ?>
              <div class="field">
                <label for="manual_title">Title</label>
                <input type="text" id="manual_title" name="manual_title" value="<?= e($item['manual_title']) ?>" placeholder="<?= e((string) $item['title']) ?>">
                <?php if (trim((string) $item['manual_title']) !== '' && $release !== null): ?><div class="inherited">Discogs: <b><?= e($item['title']) ?></b></div><?php endif; ?>
              </div>
              <div class="field">
                <label for="manual_artist">Artist</label>
                <input type="text" id="manual_artist" name="manual_artist" value="<?= e($item['manual_artist']) ?>" placeholder="<?= e((string) $item['artists_text']) ?>">
                <?php if (trim((string) $item['manual_artist']) !== '' && $release !== null): ?><div class="inherited">Discogs: <b><?= e($item['artists_text']) ?></b></div><?php endif; ?>
              </div>
            <?php endif; ?>

            <?php foreach ($overrideLabels as $key => $label): ?>
              <?php $hint = fallback_hint($item, $release, $key, true); ?>
              <div class="field">
                <label for="o_<?= e($key) ?>"><?= e($label) ?></label>
                <?php if (OVERRIDE_FIELDS[$key] === 'list'): ?>
                  <input type="text" id="o_<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($item[$key]) ?>" placeholder="<?= e($hint) ?>">
                <?php else: ?>
                  <textarea id="o_<?= e($key) ?>" name="<?= e($key) ?>" rows="2" style="min-height:0;" placeholder="<?= e($hint) ?>"><?= e($item[$key]) ?></textarea>
                <?php endif; ?>
                <?php if ($hint !== '' && trim((string) $item[$key]) !== ''): ?><div class="inherited">Discogs: <b><?= e($hint) ?></b></div><?php endif; ?>
                <div class="hint"><?= e($overrideHelp[$key]) ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="field" style="margin-top:0.9rem;">
            <label for="o_tracklist">Tracklist</label>
            <textarea id="o_tracklist" name="tracklist" rows="10" data-discogs="<?= e($discogsTracks) ?>"
                      placeholder="1. Track title 3:45"><?= e($item['tracklist']) ?></textarea>
            <div class="hint">
              One track per line, like <b>1. Poker Face 3:58</b>. The number and the length are optional.
              <?php if ($discogsTracks !== ''): ?>
                Discogs has <?= substr_count($discogsTracks, "\n") + 1 ?> tracks.
                <button type="button" class="ghost small" id="copyDiscogsTracks">Start from Discogs' list</button>
              <?php endif; ?>
            </div>
          </div>
        </details>
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
            Keep this even if a sync disagrees (a format you change here is kept anyway)
          </label>
        </div>

        <?php if ($kind === 'dvd'): ?>
          <div class="field">
            <label for="case_kind">Case</label>
            <select id="case_kind" name="case_kind">
              <option value=""<?= $item['case_kind'] !== 'cd' ? ' selected' : '' ?>>DVD case</option>
              <option value="cd"<?= $item['case_kind'] === 'cd' ? ' selected' : '' ?>>CD case</option>
            </select>
            <div class="hint">Some DVDs came in a CD-sized jewel case rather than the tall DVD one — pick which one the shelf draws.</div>
          </div>
        <?php endif; ?>

        <div class="field">
          <label for="artist_id">Artist page</label>
          <?php $automatic = array_column($artists, 'name', 'id')[(int) $item['artist_id']] ?? null; ?>
          <select id="artist_id" name="artist_id">
            <option value=""<?= !$item['artist_locked'] ? ' selected' : '' ?>>Automatic — <?= $automatic !== null ? e($automatic) : 'not on an artist page' ?></option>
            <option value="none"<?= $item['artist_locked'] && $item['artist_id'] === null ? ' selected' : '' ?>>Not on an artist page</option>
            <?php foreach ($artists as $artist): ?>
              <option value="<?= (int) $artist['id'] ?>"<?= $item['artist_locked'] && (int) $item['artist_id'] === (int) $artist['id'] ? ' selected' : '' ?>><?= e($artist['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="hint">Automatic works it out from the credits on each sync. Pick one here to keep it whatever a sync says.</div>
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
      </div>

      <div class="card">
        <h2>From Discogs</h2>
        <p>Read only — a sync refreshes all of it. What you've corrected on the left is kept and shown instead.</p>
        <table class="table facts">
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
              <tr><td class="label"><?= e($label) ?></td><td class="right"><?= e($value) ?></td></tr>
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
