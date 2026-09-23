<?php

/**
 * One listing: start it from nothing but a Discogs link, then set what Discogs
 * can't tell you — the price, the eBay listing, any photos of your own.
 *
 * Deliberately smaller than admin_item.php: a listing isn't filed into an era
 * or an artist page, and it has no discs to draw, so none of that machinery is
 * here. What it does share — the cover picker, the read-only Discogs facts,
 * the Sync/Delete header actions — is lifted from admin_item.php as directly
 * as the two forms' different shapes allow.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$id = (int) query('id');
$item = $id > 0 ? item_by_id($id) : null;

if ($id > 0 && ($item === null || $item['source'] !== 'for_sale')) {
    http_response_code(404);
    $pageTitle = 'Not found';
    require __DIR__ . '/../includes/admin_layout_top.php';
    echo '<div class="card"><p class="empty">There is no listing with that id. It may have been deleted.</p></div>';
    require __DIR__ . '/../includes/admin_layout_bottom.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    if (post('action') === 'delete' && $item) {
        db()->prepare('DELETE FROM items WHERE id = ?')->execute([$item['id']]);
        flash('Deleted "' . item_title($item) . '".');
        redirect('admin_selling');
    }

    if (post('action') === 'sync' && $item) {
        set_time_limit(150);
        try {
            flash(sync_one_item($item));
        } catch (DiscogsException $e) {
            flash('Sync failed: ' . $e->getMessage(), 'error');
        } catch (Throwable $e) {
            error_log('Selling item sync failed: ' . $e);
            flash('Sync failed' . (is_debug() ? ': ' . $e->getMessage() : '. Check the log.'), 'error');
        }
        redirect('admin_selling_item?id=' . $item['id']);
    }

    if ($item) {
        $kind = isset(MEDIA_KINDS[post('media_kind')]) ? post('media_kind') : $item['media_kind'];

        $price = post('sale_price');
        $price = $price !== '' && is_numeric($price) ? (float) $price : null;

        $ebay = nullable(post('ebay_url'));
        $ebay = $ebay !== null && safe_http_url($ebay) ? $ebay : null;

        $photos = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', post('extra_photos'))), 'safe_http_url'));
        $condition = in_array(post('sale_condition'), ['new', 'used'], true) ? post('sale_condition') : null;

        // What the drawer's gallery actually shows: whichever of Discogs'
        // images and the photos above were ticked, in the order they were
        // presented. Every checkbox rendered has an entry in the posted
        // array or not; a request with no gallery box at all (JS disabled,
        // or the create step) is not the same as "unticked everything".
        $galleryPosted = array_key_exists('gallery_photos', $_POST) && is_array($_POST['gallery_photos']);
        $gallery = $galleryPosted
            ? array_values(array_filter(array_map('trim', $_POST['gallery_photos']), 'safe_http_url'))
            : null;

        $sets = [
            'media_kind = ?', 'media_kind_locked = ?', 'cover_url = ?',
            'sale_price = ?', 'sale_currency = ?', 'sale_condition = ?', 'ebay_url = ?', 'extra_photos_json = ?', 'notes = ?',
            'is_visible = ?', "sold_at = CASE WHEN ? THEN COALESCE(sold_at, datetime('now')) ELSE NULL END",
            'manual_title = ?', 'manual_artist = ?',
        ];
        $params = [
            $kind,
            (post('media_kind_locked') !== '' || $kind !== $item['media_kind']) ? 1 : 0,
            nullable(post('cover_url')),
            $price,
            strtoupper(post('sale_currency')) ?: 'AUD',
            $condition,
            $ebay,
            $photos ? json_encode($photos, JSON_UNESCAPED_SLASHES) : null,
            nullable(post('notes')),
            post('is_visible') !== '' ? 1 : 0,
            post('sold') !== '' ? 1 : 0,
            nullable(post('manual_title')),
            nullable(post('manual_artist')),
        ];

        if ($galleryPosted) {
            $sets[] = 'gallery_json = ?';
            $params[] = json_encode($gallery, JSON_UNESCAPED_SLASHES);
        }

        // Discogs' facts corrected by hand — same override columns admin_item.php
        // writes. Blank means "use Discogs'"; a sync never touches these.
        foreach (array_keys(OVERRIDE_FIELDS) as $key) {
            $sets[] = "$key = ?";
            $params[] = nullable(post($key));
        }

        $params[] = $item['id'];

        db()->prepare('UPDATE items SET ' . implode(', ', $sets) . ', updated_at = datetime(\'now\') WHERE id = ?')->execute($params);

        flash('Saved "' . item_title(item_by_id((int) $item['id'])) . '".');
        redirect('admin_selling');
    }
}

$pageScript = 'js/admin-selling-item.js';

if ($item === null) {
    $pageTitle = 'Add an item to sell';
    $pageIntro = 'Paste a Discogs release link (or just its id) — the title, artist, cover and tracklist come with it.';
    $pageActions = '<a class="btn ghost" href="' . e(url('admin_selling')) . '">Cancel</a>';

    require __DIR__ . '/../includes/admin_layout_top.php';
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
    require __DIR__ . '/../includes/admin_layout_bottom.php';
    exit;
}

$kind = (string) $item['media_kind'];
$release = $item['discogs_id'] !== null ? $item : null;
$images = json_column($item['images_json'] ?? null);
$extraPhotos = json_column($item['extra_photos_json'] ?? null);
$catalog = field_catalog();

/**
 * The Discogs value showing through an empty box, for the hint under it.
 * Same helper as admin_item.php's — item_field_value() with the field
 * blanked is exactly "what would the site show if I left this empty?".
 */
function fallback_hint(array $item, ?array $release, string $key, bool $always = false): string
{
    if ($release === null || (!$always && trim((string) ($item[$key] ?? '')) !== '')) {
        return '';
    }

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

$selfUrl = e(url('admin_selling_item?id=' . (int) $item['id']));
$actions = [];
if ($item['discogs_id']) {
    $actions[] = '<a class="btn ghost" target="_blank" rel="noopener" href="https://www.discogs.com/release/' . (int) $item['discogs_id'] . '">On Discogs ↗</a>';
    $actions[] = '<form method="post" action="' . $selfUrl . '" id="syncForm">' . csrf_field()
        . '<input type="hidden" name="action" value="sync">'
        . '<button type="submit" class="ghost" title="Refresh this record from Discogs. Price, eBay link and photos are never touched.">Sync with Discogs</button></form>';
}
$actions[] = '<form method="post" action="' . $selfUrl . '" id="deleteForm">' . csrf_field()
    . '<input type="hidden" name="action" value="delete">'
    . '<button type="submit" class="danger">Delete</button></form>';
$actions[] = '<a class="btn ghost" href="' . e(url('admin_selling')) . '">Cancel</a>';
$actions[] = '<button type="submit" form="sellingForm" class="gold">Save</button>';
$pageActions = implode("\n", $actions);

require __DIR__ . '/../includes/admin_layout_top.php';
?>

<?php if ($item['discogs_id'] && $item['detail_fetched_at'] === null): ?>
  <div class="flash error">Discogs' full detail hasn't come back yet — try Sync in a moment.</div>
<?php endif; ?>

<form method="post" id="sellingForm" action="<?= $selfUrl ?>">
  <?= csrf_field() ?>

  <div class="cards">
    <div>
      <div class="card">
        <h2>Cover</h2>
        <?php if (!$images): ?>
          <p class="empty">Discogs hasn't given any images for this release yet — try Sync in a moment.</p>
        <?php else: ?>
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
        <?php endif; ?>
      </div>

      <?php
      $galleryAll = drawer_gallery_all($item);
      $galleryChosenRaw = json_column($item['gallery_json'] ?? null, ['__uncurated__']);
      $galleryChosen = $galleryChosenRaw === ['__uncurated__'] ? array_column($galleryAll, 'full') : $galleryChosenRaw;
      ?>
      <div class="card">
        <h2>Gallery</h2>
        <p>Which of these show in the drawer when a buyer opens this listing. Everything's shown until you pick.</p>
        <?php if (!$galleryAll): ?>
          <p class="empty">Nothing yet — Discogs' gallery or the photos you paste in below will appear here.</p>
        <?php else: ?>
          <div class="picker">
            <?php foreach ($galleryAll as $photo): ?>
              <label>
                <input type="checkbox" name="gallery_photos[]" value="<?= e($photo['full']) ?>"<?= in_array($photo['full'], $galleryChosen, true) ? ' checked' : '' ?>>
                <img src="<?= e($photo['thumb']) ?>" alt="" loading="lazy">
                <small><?= e($photo['type'] === 'yours' ? 'yours' : ($photo['type'] ?: '')) ?></small>
              </label>
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
              <option value="new"<?= $item['sale_condition'] === 'new' ? ' selected' : '' ?>>New</option>
              <option value="used"<?= $item['sale_condition'] === 'used' ? ' selected' : '' ?>>Used</option>
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

        <label class="check">
          <input type="checkbox" name="is_visible" value="1"<?= $item['is_visible'] ? ' checked' : '' ?>>
          Show on the site
        </label>
        <label class="check">
          <input type="checkbox" name="sold" value="1"<?= $item['sold_at'] ? ' checked' : '' ?>>
          Sold
        </label>
        <?php if ($item['sold_at']): ?>
          <div class="hint">Sold <?= e(format_date($item['sold_at'])) ?>. Untick to put it back up.</div>
        <?php endif; ?>
      </div>

      <?php if ($release): ?>
        <?php
        $overrideLabels = ['labels' => 'Label', 'catalog_number' => 'Catalogue no.', 'formats' => 'Format details', 'genres' => 'Genres', 'styles' => 'Styles'];
        $overrideHelp = [
            'labels' => 'One per line.',
            'catalog_number' => 'One per line.',
            'formats' => 'What the list shows under Details, one per line: "LP", "Album", "Pink".',
            'genres' => 'Separated by commas.',
            'styles' => 'Separated by commas.',
        ];
        $discogsTracks = tracklist_to_text(json_column($item['tracklist_json'] ?? null));
        // What each box actually shows: what was typed here, or — pre-filled,
        // not just hinted at — whatever Discogs currently says, so there's
        // something real to edit rather than an empty box to retype from scratch.
        $ownOr = fn (string $key, string $discogsValue) => trim((string) $item[$key]) !== '' ? $item[$key] : $discogsValue;
        ?>
        <div class="card">
          <h2>Correct what Discogs says</h2>
          <p class="hint" style="margin-bottom:0.8rem;">Filled in with what Discogs currently says — edit anything here to correct it. It's kept through every sync; put it back the way Discogs has it to stop correcting it.</p>

          <div class="grid-fields">
            <div class="field">
              <label for="manual_title">Title</label>
              <input type="text" id="manual_title" name="manual_title" value="<?= e($ownOr('manual_title', (string) $item['title'])) ?>">
            </div>
            <div class="field">
              <label for="manual_artist">Artist</label>
              <input type="text" id="manual_artist" name="manual_artist" value="<?= e($ownOr('manual_artist', (string) $item['artists_text'])) ?>">
            </div>

            <?php foreach ($overrideLabels as $key => $label): ?>
              <?php $value = $ownOr($key, fallback_hint($item, $release, $key, true)); ?>
              <div class="field">
                <label for="o_<?= e($key) ?>"><?= e($label) ?></label>
                <?php if (OVERRIDE_FIELDS[$key] === 'list'): ?>
                  <input type="text" id="o_<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($value) ?>">
                <?php else: ?>
                  <textarea id="o_<?= e($key) ?>" name="<?= e($key) ?>" rows="2" style="min-height:0;"><?= e($value) ?></textarea>
                <?php endif; ?>
                <div class="hint"><?= e($overrideHelp[$key]) ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="field" style="margin-top:0.9rem;">
            <label for="o_tracklist">Tracklist</label>
            <textarea id="o_tracklist" name="tracklist" rows="10" data-discogs="<?= e($discogsTracks) ?>"
                      ><?= e($ownOr('tracklist', $discogsTracks)) ?></textarea>
            <div class="hint">
              One track per line, like <b>1. Poker Face 3:58</b>. The number and the length are optional.
              <?php if ($discogsTracks !== '' && trim((string) $item['tracklist']) !== ''): ?>
                <button type="button" class="ghost small" id="copyDiscogsTracks">Reset to Discogs' list</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($release): ?>
        <div class="card">
          <h2>From Discogs</h2>
          <p>Read only — Sync refreshes it. Price, eBay link, photos and notes are never touched by a sync.</p>
          <table class="table facts">
            <tbody>
              <?php
              $readonly = [
                'Release'  => $item['discogs_id'] ? '#' . $item['discogs_id'] : '—',
                'Title'    => $item['title'],
                'Artists'  => $item['artists_text'],
                'Year'     => $item['year'],
                'Country'  => $item['country'],
                'Formats'  => $item['formats_text'],
                'Labels'   => implode(', ', labels_lines(json_column($item['labels_json']))),
                'Barcode'  => $item['release_barcode'],
                'Genres'   => implode(', ', json_column($item['genres_json'])),
                'Tracks'   => count(json_column($item['tracklist_json'])) ?: '—',
                'Images'   => count($images) ?: '—',
                'Detail'   => $item['detail_fetched_at'] ? time_ago($item['detail_fetched_at']) : 'not fetched yet',
              ];
              foreach ($readonly as $label => $value):
                  if ($value === null || $value === '') { continue; }
              ?>
                <tr><td class="label"><?= e($label) ?></td><td class="right"><?= e($value) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</form>

<?php require __DIR__ . '/../includes/admin_layout_bottom.php'; ?>
