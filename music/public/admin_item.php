<?php

/**
 * Editing one record, in the order the work happens: its pictures, the fields
 * Bruno fills in, where it is filed, and Discogs' facts at the bottom as a
 * reference for what an empty box falls back to.
 */

require_once __DIR__ . '/../includes/admin.php';

require_login();

$isNew = query('new') === 'searching';
$item = $isNew ? blank_item_row() : item_by_id((int) query('id'));

if ($item === null) {
    admin_not_found('There is no record with that id. It may have been deleted.');
}

if (is_post()) {
    csrf_verify();
    handle_record_action($item, 'admin_items?source=' . $item['source'], 'admin_item');

    $kind = posted_media_kind('other');

    if ($isNew) {
        db()->prepare("
            INSERT INTO items (source, manual_title, manual_artist, media_kind, media_kind_locked, is_visible)
            VALUES ('searching', ?, ?, ?, 1, 1)
        ")->execute([post('manual_title'), post('manual_artist'), $kind]);
        $item = item_by_id((int) db()->lastInsertId());
    }

    // Only the boxes this kind's form showed are saved, so turning a CD into a
    // vinyl doesn't wipe the Media it had as a CD.
    $values = [];
    foreach (own_fields_for_kind($kind) as $key) {
        $values[$key] = nullable(post($key));
    }

    $discs = posted_disc_config($item, $kind);
    if ($kind === 'vinyl') {
        // The record-wide colour from before discs had their own is folded into them.
        $values += ['vinyl_hex' => null, 'vinyl_translucent' => null];
    }

    $eraId = (int) post('era_id') ?: null;

    // "Automatic" leaves the artist page to the sync; anything else is kept.
    $artistChoice = post('artist_id');
    $artistId = match ($artistChoice) {
        ''      => $item['artist_id'],
        'none'  => null,
        default => (int) $artistChoice ?: null,
    };

    update_item((int) $item['id'], $values + posted_overrides() + [
        'media_kind'        => $kind,
        'media_kind_locked' => posted_media_kind_locked($kind, $item['media_kind']),
        // Only a DVD's form has the Case box.
        'case_kind'         => $kind === 'dvd' && post('case_kind') === 'cd' ? 'cd' : null,
        'artist_id'         => $artistId,
        'artist_locked'     => $artistChoice !== '' ? 1 : 0,
        'era_id'            => $eraId,
        // An era picked by hand stays put through a sync; clearing it unlocks it.
        'era_locked'        => $eraId !== null ? 1 : 0,
        'cover_url'         => nullable(post('cover_url')),
        'disc_url'          => $discs['first_art'],
        'disc_config'       => $discs['config'],
        'is_visible'        => posted_flag('is_visible'),
        'is_featured'       => posted_flag('is_featured'),
        'sort_rank'         => (int) post('sort_rank'),
        'manual_title'      => nullable(post('manual_title')),
        'manual_artist'     => nullable(post('manual_artist')),
    ]);

    $refiled = $eraId !== null && post('apply_to_master') !== '' && $item['discogs_id'];
    if ($refiled) {
        file_album_into_era($item, $eraId);
    }

    $name = item_title(item_by_id((int) $item['id']));
    flash(match (true) {
        $isNew   => "Added \"$name\" to the hunting list.",
        $refiled => "Saved \"$name\", and every pressing of it now files into that era.",
        default  => "Saved \"$name\".",
    });

    redirect('admin_items?source=' . $item['source']);
}

$kind = (string) $item['media_kind'];
$release = item_release($item);
$catalog = field_catalog();
$images = json_column($item['images_json'] ?? null);
$isHunting = $item['source'] === 'searching';

// A disc from a box set has no release of its own: its pictures are the box's.
$box = !empty($item['parent_item_id']) ? item_by_id((int) $item['parent_item_id']) : null;
$pictures = $images ?: json_column($box['images_json'] ?? null);
$artists = all_artists();
$eras = $item['artist_id'] ? eras_for_artist((int) $item['artist_id']) : [];

$discConfig = disc_config($item);
$formats = item_formats($item);
$derivedDiscs = count(item_discs([...$item, 'disc_config' => null, 'disc_url' => null], $formats));
$shownDiscs = max(1, $discConfig['count'] ?? $derivedDiscs);
// Which of the three places holds a vinyl: only those get a colour picker.
$discTypes = array_column(item_discs([...$item, 'disc_config' => json_encode(['count' => MAX_DISCS])], $formats), 't');
$discColours = disc_colours($item);

$selfUrl = $isNew ? url('admin_item?new=searching') : url('admin_item?id=' . (int) $item['id']);
$listUrl = url('admin_items?source=' . $item['source']);

$overridden = array_filter(array_map(
    fn ($key) => trim((string) ($item[$key] ?? '')),
    [...array_keys(OVERRIDE_FIELDS), ...($isHunting ? [] : ['manual_title', 'manual_artist'])]
));
$discogsTracks = tracklist_to_text(json_column($item['tracklist_json'] ?? null));

// Every artist's eras, by artist id.
$eraOptions = [];
foreach ($artists as $artist) {
    $eraOptions[$artist['id']] = array_map(
        fn ($era) => ['id' => (int) $era['id'], 'name' => $era['name'] . ($era['years'] ? " ({$era['years']})" : '')],
        eras_for_artist((int) $artist['id'])
    );
}

admin_header(item_title($item), item_byline($item), record_actions(
    $item,
    $selfUrl,
    'itemForm',
    $listUrl,
    'Refresh this record from Discogs. What you typed is never overwritten.',
    'Delete this record and everything you typed about it?',
    !$isNew
));
?>

<?php if ($item['missing_since']): ?>
  <?= flash_box("This copy wasn't in the last sync of your Discogs collection (since " . format_date($item['missing_since']) . "). It's kept here because it holds your own notes — delete it with the button at the top if it really is gone.", 'error') ?>
<?php endif; ?>

<?php if ($item['discogs_id'] && $item['detail_fetched_at'] === null): ?>
  <?= flash_box("Discogs' full detail for this release hasn't been fetched yet, so the boxes below have little to fall back on. It arrives with the next sync.") ?>
<?php endif; ?>

<form method="post" id="itemForm" action="<?= e($selfUrl) ?>">
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
      <?= cover_picker($item, $pictures) ?>
    <?php endif; ?>

    <div class="discs" id="discs" data-derived="<?= (int) $derivedDiscs ?>">
      <h3 class="sub">Discs</h3>
      <div class="field disc-count">
        <label for="disc_count">Discs in the sleeve</label>
        <select id="disc_count" name="disc_count">
          <option value="">As Discogs says (<?= (int) $derivedDiscs ?>)</option>
          <?php for ($n = 1; $n <= MAX_DISCS; $n++): ?>
            <option value="<?= $n ?>"<?= $discConfig['count'] === $n ? ' selected' : '' ?>><?= $n ?> <?= plural($n, 'disc') ?></option>
          <?php endfor; ?>
        </select>
        <div class="hint">A 2-CD set is two discs on the shelf, each with its own picture below. Up to <?= MAX_DISCS ?>.</div>
      </div>

      <?php if ($pictures): ?>
        <?php for ($k = 0; $k < MAX_DISCS; $k++): ?>
          <?php
          // A record from before discs were set one by one has one picture on every disc.
          $current = $discConfig['set'] ? $discConfig['art'][$k] : trim((string) $item['disc_url']);
          // A picture no longer in the gallery is still offered, so saving doesn't drop it.
          $offered = $current !== '' && !in_array($current, array_column($pictures, 'uri'), true)
              ? [['uri' => $current, 'type' => 'current'], ...$pictures]
              : $pictures;
          $hidden = $k >= $shownDiscs;
          ?>
          <div class="disc-art" data-disc="<?= $k ?>"<?= $hidden ? ' hidden' : '' ?>>
            <h4>Disc <?= $k + 1 ?> <span><?= $kind === 'vinyl' ? '— picture on the disc' : '— image printed on the disc' ?></span></h4>
            <?= image_picker("disc_art[$k]", $offered, $current, 'None', $kind === 'vinyl' ? 'plain vinyl' : 'plain disc', $hidden ? ' disabled' : '') ?>
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

        <?php if ($isHunting): ?>
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
            <?php $hint = discogs_fallback_text($item, $release, $key); ?>
            <div class="field">
              <label for="f_<?= e($key) ?>"><?= e($catalog[$key]['label']) ?></label>
              <?php if ($key === 'item_type'): ?>
                <?= item_type_select("f_$key", $item) ?>
              <?php else: ?>
                <input type="text" id="f_<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($item[$key]) ?>"
                       <?= $key === 'vinyl_size' ? 'list="vinylSizes"' : '' ?>
                       placeholder="<?= e($hint) ?>">
              <?php endif; ?>
              <?php if ($hint !== ''): ?>
                <?= discogs_hint($hint) ?>
              <?php endif; ?>
              <?php if ($key === 'vinyl_color'): ?>
                <?php
                // Left on automatic, a disc takes the colour its text matches.
                $autoHex = vinyl_color((string) item_field_value($item, $release, 'vinyl_color'))['c'];
                $palette = [];
                foreach (VINYL_COLORS as [$word, $hex]) {
                    $palette[$hex] ??= $word;
                }
                ?>
                <div class="colour-pick" id="colourPick"
                     data-palette="<?= e(json_encode(VINYL_COLORS)) ?>"
                     data-discogs="<?= e(discogs_fallback_text($item, $release, 'vinyl_color', true)) ?>"
                     data-fallback="<?= e(VINYL_FALLBACK_COLOR) ?>">
                  <?php foreach ($discTypes as $k => $type): ?>
                    <?php
                    if ($type !== 'v') {
                        continue;
                    }
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
            <summary class="more">More fields</summary>
            <div class="grid-fields">
              <?php foreach ($secondary as $key): ?>
                <?php $hint = discogs_fallback_text($item, $release, $key); ?>
                <div class="field">
                  <label for="s_<?= e($key) ?>"><?= e($catalog[$key]['label']) ?></label>
                  <?php if ($key === 'item_type'): ?>
                    <?= item_type_select("s_$key", $item) ?>
                  <?php else: ?>
                    <input type="text" id="s_<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($item[$key]) ?>">
                  <?php endif; ?>
                  <?php if ($hint !== ''): ?><?= discogs_hint($hint) ?><?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </details>
        <?php endif; ?>

        <details<?= $overridden ? ' open' : '' ?>>
          <summary class="more corrections">Correct what Discogs says</summary>
          <p class="hint lead">Anything typed here is shown instead of Discogs' and is kept through every sync. Leave a box empty to use Discogs'. Label, Catalogue no., Genres and Styles appear in the drawer only where they're switched on under <a href="<?= e(url('admin_fields')) ?>">Fields</a>.</p>

          <div class="grid-fields">
            <?php if (!$isHunting): ?>
              <div class="field">
                <label for="manual_title">Title</label>
                <input type="text" id="manual_title" name="manual_title" value="<?= e($item['manual_title']) ?>" placeholder="<?= e((string) $item['title']) ?>">
                <?php if (trim((string) $item['manual_title']) !== '' && $release !== null): ?><?= discogs_hint((string) $item['title']) ?><?php endif; ?>
              </div>
              <div class="field">
                <label for="manual_artist">Artist</label>
                <input type="text" id="manual_artist" name="manual_artist" value="<?= e($item['manual_artist']) ?>" placeholder="<?= e((string) $item['artists_text']) ?>">
                <?php if (trim((string) $item['manual_artist']) !== '' && $release !== null): ?><?= discogs_hint((string) $item['artists_text']) ?><?php endif; ?>
              </div>
            <?php endif; ?>

            <?php foreach (OVERRIDE_BOXES as $key => $box): ?>
              <?php $hint = discogs_fallback_text($item, $release, $key, true); ?>
              <div class="field">
                <label for="o_<?= e($key) ?>"><?= e($box['label']) ?></label>
                <?= override_input($key, (string) $item[$key], $hint) ?>
                <?php if ($hint !== '' && trim((string) $item[$key]) !== ''): ?><?= discogs_hint($hint) ?><?php endif; ?>
                <div class="hint"><?= e($box['help']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="field spaced">
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

        <?= media_kind_field($kind, (bool) $item['media_kind_locked'], 'Keep this even if a sync disagrees (a format you change here is kept anyway)') ?>

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
          <?= check_box('apply_to_master', false, 'Put every pressing of this album in that era') ?>
          <div class="hint under-check">
            <?= $item['master_id']
                ? 'Files the CD, the vinyl and any reissue together from the next sync on.'
                : 'This release has no master on Discogs, so the rule covers this release only.' ?>
          </div>
        <?php endif; ?>

        <?= check_box('is_visible', (bool) $item['is_visible'], 'Show on the site') ?>
        <?= check_box('is_featured', (bool) $item['is_featured'], 'Favourite') ?>

        <div class="field spaced">
          <label for="sort_rank">Sort weight</label>
          <input type="number" id="sort_rank" name="sort_rank" value="<?= (int) $item['sort_rank'] ?>">
          <div class="hint">Higher floats to the front of the shelf. Leave at 0 for the normal order.</div>
        </div>
      </div>

      <div class="card">
        <h2>From Discogs</h2>
        <p>Read only — a sync refreshes all of it. What you've corrected on the left is kept and shown instead.</p>
        <?= facts_table([
            'Release'  => $item['discogs_id'] ? '#' . $item['discogs_id'] : '—',
            'Master'   => $item['master_id'] ? '#' . $item['master_id'] : '—',
            'Title'    => $item['title'],
            'Artists'  => $item['artists_text'],
            'Year'     => $item['year'],
            'Released' => $item['released_formatted'] ?: $item['released'],
            'Country'  => $item['country'],
            'Formats'  => $item['formats_text'],
            'Labels'   => implode(', ', labels_lines(json_column($item['labels_json']))),
            'Cat. no.' => implode(', ', catalog_numbers(json_column($item['labels_json']))),
            'Barcode'  => $item['release_barcode'],
            'Genres'   => implode(', ', json_column($item['genres_json'])),
            'Styles'   => implode(', ', json_column($item['styles_json'])),
            'Added'    => format_date($item['date_added'], 'j M Y'),
            'Tracks'   => count(json_column($item['tracklist_json'])) ?: '—',
            'Images'   => count($images) ?: '—',
            'Detail'   => $item['detail_fetched_at'] ? time_ago($item['detail_fetched_at']) : 'not fetched yet',
        ]) ?>
      </div>
    </div>
  </div>
</form>

<script id="eraOptions" type="application/json"><?= json_encode((object) $eraOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<?php admin_footer('admin-edit', 'admin-item'); ?>
