<?php

/**
 * One listing: started from a Discogs link, then given what Discogs can't know
 * — the price, the eBay listing, photos of the actual copy.
 */

require_once __DIR__ . '/../includes/admin.php';

require_login();

$id = (int) query('id');
$item = $id > 0 ? item_by_id($id) : null;

if ($id > 0 && ($item === null || $item['source'] !== 'for_sale')) {
    admin_not_found('There is no listing with that id. It may have been deleted.');
}

if (is_post()) {
    csrf_verify();

    if (post('action') === 'create') {
        $releaseId = discogs_release_id_from_input(post('discogs_input'));
        if ($releaseId === null) {
            flash("That doesn't look like a Discogs release link or id.", 'error');
            redirect('admin_selling_item');
        }
        try {
            $item = create_sale_item_from_discogs($releaseId);
        } catch (DiscogsException $e) {
            flash($e->getMessage(), 'error');
            redirect('admin_selling_item');
        }
        flash('Pulled in "' . item_title($item) . '" from Discogs — set a price and it\'s ready to list.');
        redirect('admin_selling_item?id=' . $item['id']);
    }

    if ($item) {
        handle_record_action($item, 'admin_selling', 'admin_selling_item');

        $kind = posted_media_kind($item['media_kind']);
        $price = post('sale_price');
        $ebay = nullable(post('ebay_url'));
        $photos = array_values(array_filter(array_map('trim', preg_split(LINE_BREAK, post('extra_photos'))), 'safe_http_url'));
        $condition = post('sale_condition');

        $values = [
            'media_kind'        => $kind,
            'media_kind_locked' => posted_media_kind_locked($kind, $item['media_kind']),
            'cover_url'         => nullable(post('cover_url')),
            'sale_price'        => $price !== '' && is_numeric($price) ? (float) $price : null,
            'sale_currency'     => strtoupper(post('sale_currency')) ?: 'AUD',
            'sale_condition'    => isset(SALE_CONDITIONS[$condition]) ? $condition : null,
            'ebay_url'          => $ebay !== null && safe_http_url($ebay) ? $ebay : null,
            'extra_photos_json' => $photos ? json_encode($photos, JSON_UNESCAPED_SLASHES) : null,
            'notes'             => nullable(post('notes')),
            'is_visible'        => posted_flag('is_visible'),
            'manual_title'      => nullable(post('manual_title')),
            'manual_artist'     => nullable(post('manual_artist')),
        ];

        // The ticked pictures, in the order shown. A form without the gallery
        // at all is not the same as one with every box unticked.
        if (is_array($_POST['gallery_photos'] ?? null)) {
            $values['gallery_json'] = json_encode(
                array_values(array_filter(array_map('trim', $_POST['gallery_photos']), 'safe_http_url')),
                JSON_UNESCAPED_SLASHES
            );
        }

        update_item((int) $item['id'], $values + posted_overrides(), [
            "sold_at = CASE WHEN ? THEN COALESCE(sold_at, datetime('now')) ELSE NULL END" => [posted_flag('sold')],
        ]);

        flash('Saved "' . item_title(item_by_id((int) $item['id'])) . '".');
        redirect('admin_selling');
    }
}

if ($item === null) {
    admin_header(
        'Add an item to sell',
        'Paste a Discogs release link (or just its id) — the title, artist, cover and tracklist come with it.',
        '<a class="btn ghost" href="' . e(url('admin_selling')) . '">Cancel</a>'
    );
    ?>
    <div class="card">
      <h2>From Discogs</h2>
      <form method="post" action="<?= e(url('admin_selling_item')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <div class="field">
          <label for="discogs_input">Discogs release link or id</label>
          <input type="text" id="discogs_input" name="discogs_input" required autofocus
                 placeholder="https://www.discogs.com/release/249504-... or just 249504">
        </div>
        <div class="form-actions">
          <button type="submit" class="gold">Fetch from Discogs</button>
        </div>
      </form>
    </div>
    <?php
    admin_footer('admin-edit');
    exit;
}

$kind = (string) $item['media_kind'];
$release = item_release($item);
$images = json_column($item['images_json'] ?? null);
$extraPhotos = json_column($item['extra_photos_json'] ?? null);
$gallery = drawer_gallery_all($item);
$galleryChosen = gallery_choice($item) ?? array_column($gallery, 'full');
$discogsTracks = tracklist_to_text(json_column($item['tracklist_json'] ?? null));
$selfUrl = url('admin_selling_item?id=' . (int) $item['id']);

// Each correction box starts from what Discogs says, so there is something to edit.
$ownOr = fn (string $key, string $discogs) => trim((string) $item[$key]) !== '' ? (string) $item[$key] : $discogs;

admin_header(item_title($item), item_byline($item), record_actions(
    $item,
    $selfUrl,
    'sellingForm',
    url('admin_selling'),
    'Refresh this record from Discogs. Price, eBay link and photos are never touched.',
    'Delete this listing and everything typed about it?'
));
?>

<?php if ($item['discogs_id'] && $item['detail_fetched_at'] === null): ?>
  <?= flash_box("Discogs' full detail hasn't come back yet — try Sync in a moment.", 'error') ?>
<?php endif; ?>

<form method="post" id="sellingForm" action="<?= e($selfUrl) ?>">
  <?= csrf_field() ?>

  <div class="cards">
    <div>
      <div class="card">
        <h2>Cover</h2>
        <?php if (!$images): ?>
          <p class="empty">Discogs hasn't given any images for this release yet — try Sync in a moment.</p>
        <?php else: ?>
          <?= cover_picker($item, $images) ?>
        <?php endif; ?>
      </div>

      <div class="card">
        <h2>Gallery</h2>
        <p>Which of these show in the drawer when a buyer opens this listing. Everything's shown until you pick.</p>
        <?php if (!$gallery): ?>
          <p class="empty">Nothing yet — Discogs' gallery or the photos you paste in below will appear here.</p>
        <?php else: ?>
          <div class="picker">
            <?php foreach ($gallery as $photo): ?>
              <?= picker_choice('checkbox', 'gallery_photos[]', $photo['full'], in_array($photo['full'], $galleryChosen, true), $photo['thumb'], $photo['type']) ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="card">
        <h2>Selling it</h2>

        <div class="grid-fields">
          <div class="field">
            <label for="sale_price">Price</label>
            <input type="number" id="sale_price" name="sale_price" step="0.01" min="0" value="<?= $item['sale_price'] !== null ? e($item['sale_price']) : '' ?>">
          </div>
          <div class="field">
            <label for="sale_currency">Currency</label>
            <input type="text" id="sale_currency" name="sale_currency" maxlength="3" value="<?= e($item['sale_currency'] ?: 'AUD') ?>" placeholder="AUD">
          </div>
          <div class="field">
            <label for="sale_condition">Condition</label>
            <select id="sale_condition" name="sale_condition">
              <option value="">— not set —</option>
              <?= options_html(SALE_CONDITIONS, $item['sale_condition']) ?>
            </select>
          </div>
        </div>

        <div class="field">
          <label for="ebay_url">eBay listing</label>
          <input type="url" id="ebay_url" name="ebay_url" value="<?= e($item['ebay_url']) ?>" placeholder="https://www.ebay.com/itm/…">
        </div>

        <div class="field">
          <label for="extra_photos">Your own photos</label>
          <textarea id="extra_photos" name="extra_photos" rows="4" placeholder="One image URL per line — condition shots, the actual sleeve…"><?= e(implode("\n", $extraPhotos)) ?></textarea>
          <div class="hint">Shown after Discogs' own pictures in the drawer. No eBay integration — paste the URLs by hand.</div>
        </div>

        <div class="field">
          <label for="notes">Notes</label>
          <textarea id="notes" name="notes" placeholder="Condition, what's in the box, anything a buyer should know…"><?= e($item['notes']) ?></textarea>
        </div>
      </div>
    </div>

    <div>
      <div class="card">
        <h2>Where it lives</h2>

        <?= media_kind_field($kind, (bool) $item['media_kind_locked'], 'Keep this even if a sync disagrees') ?>

        <?= check_box('is_visible', (bool) $item['is_visible'], 'Show on the site') ?>
        <?= check_box('sold', (bool) $item['sold_at'], 'Sold') ?>
        <?php if ($item['sold_at']): ?>
          <div class="hint">Sold <?= e(format_date($item['sold_at'])) ?>. Untick to put it back up.</div>
        <?php endif; ?>
      </div>

      <?php if ($release): ?>
        <div class="card">
          <h2>Correct what Discogs says</h2>
          <p class="hint lead">Filled in with what Discogs currently says — edit anything here to correct it. It's kept through every sync; put it back the way Discogs has it to stop correcting it.</p>

          <div class="grid-fields">
            <div class="field">
              <label for="manual_title">Title</label>
              <input type="text" id="manual_title" name="manual_title" value="<?= e($ownOr('manual_title', (string) $item['title'])) ?>">
            </div>
            <div class="field">
              <label for="manual_artist">Artist</label>
              <input type="text" id="manual_artist" name="manual_artist" value="<?= e($ownOr('manual_artist', (string) $item['artists_text'])) ?>">
            </div>

            <?php foreach (OVERRIDE_BOXES as $key => $box): ?>
              <div class="field">
                <label for="o_<?= e($key) ?>"><?= e($box['label']) ?></label>
                <?= override_input($key, $ownOr($key, discogs_fallback_text($item, $release, $key, true))) ?>
                <div class="hint"><?= e($box['help']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="field spaced">
            <label for="o_tracklist">Tracklist</label>
            <textarea id="o_tracklist" name="tracklist" rows="10" data-discogs="<?= e($discogsTracks) ?>"><?= e($ownOr('tracklist', $discogsTracks)) ?></textarea>
            <div class="hint">
              One track per line, like <b>1. Poker Face 3:58</b>. The number and the length are optional.
              <?php if ($discogsTracks !== '' && trim((string) $item['tracklist']) !== ''): ?>
                <button type="button" class="ghost small" id="copyDiscogsTracks">Reset to Discogs' list</button>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="card">
          <h2>From Discogs</h2>
          <p>Read only — Sync refreshes it. Price, eBay link, photos and notes are never touched by a sync.</p>
          <?= facts_table([
              'Release' => $item['discogs_id'] ? '#' . $item['discogs_id'] : '—',
              'Title'   => $item['title'],
              'Artists' => $item['artists_text'],
              'Year'    => $item['year'],
              'Country' => $item['country'],
              'Formats' => $item['formats_text'],
              'Labels'  => implode(', ', labels_lines(json_column($item['labels_json']))),
              'Barcode' => $item['release_barcode'],
              'Genres'  => implode(', ', json_column($item['genres_json'])),
              'Tracks'  => count(json_column($item['tracklist_json'])) ?: '—',
              'Images'  => count($images) ?: '—',
              'Detail'  => $item['detail_fetched_at'] ? time_ago($item['detail_fetched_at']) : 'not fetched yet',
          ]) ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</form>

<?php admin_footer('admin-edit'); ?>
