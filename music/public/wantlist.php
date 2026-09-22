<?php
/**
 * The other half of a collection: what isn't in it yet.
 *
 * Two lists — the Discogs wantlist, synced like everything else, and the
 * records being hunted that Discogs has no page for. Drawn exactly like the
 * shelf (js/tiles.js), because they are the same kind of object; the only
 * difference is that Bruno doesn't have them yet.
 */

require_once __DIR__ . '/../includes/bootstrap_api.php';

if (!setting('show_wantlist', true)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$count = (int) db()->query("
    SELECT COUNT(*) FROM items
     WHERE source IN ('wantlist', 'searching') AND is_visible = 1 AND missing_since IS NULL
")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Wantlist — Bruno's Collection</title>
<meta name="description" content="The records Bruno is still looking for.">
<link rel="icon" href="<?= e(url('../assets/favicon.ico')) ?>">
<link rel="preload" href="<?= e(url('fonts/fraunces-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(url('fonts/space-grotesk-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset_url('css/floor.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('css/artist.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('css/spotlight.css')) ?>">
</head>
<body data-api="<?= e(url('api/')) ?>" data-version="<?= e(data_version()) ?>">

<header class="hero<?= hero_classes() ?>">
  <div class="hero-inner">
    <div class="hero-text">
      <a class="back" href="<?= e(url('')) ?>">← The Collection</a>
      <div class="display"><?= hero_title('Still Wanted') ?></div>
      <?= hero_rule() ?>
      <p><?= $count ?> records that aren't on the shelf yet.</p>
      <?= hero_links() ?>
    </div>
  </div>
  <?= hero_platter('sleeve') ?>
</header>

<div class="controls">
  <div class="controls-inner">
    <?= hero_search('Search title, artist, barcode…', 'Search the wantlist') ?>
    <div class="seg" id="viewToggle" role="group" aria-label="View">
      <button type="button" data-view="floor">Floor</button>
      <button type="button" data-view="grid">Grid</button>
    </div>
    <label class="mess" id="messWrap">Messiness <input type="range" id="mess" min="0" max="1.4" step="0.05" value="0.6" aria-label="Messiness of the pile"></label>
  </div>
</div>

<div class="filters">
  <div class="chips" id="formats" role="group" aria-label="Filter by format"></div>
  <span class="meta" id="countMeta"></span>
</div>

<main>
  <div id="content">
    <div class="state"><p>Loading…</p></div>
  </div>
</main>

<footer>
  <span>&copy; 2026 | Data from Discogs API | <a href="https://brunovida.si/contact" target="_blank" rel="noopener">Contact Bruno</a></span>
  <span>Made with &#10084;&#65039; by <a href="https://brunovida.si" target="_blank" rel="noopener">brunovida.si</a></span>
</footer>

<div id="tip"></div>
<div class="overlay" id="overlay"></div>
<aside class="drawer" id="drawer">
  <button class="drawer-close" id="drawerClose" aria-label="Close">✕</button>
  <div class="drawer-cover" id="drawerCover"></div>
  <div class="drawer-body" id="drawerBody"></div>
</aside>

<script src="<?= e(asset_url('js/common.js')) ?>"></script>
<script src="<?= e(asset_url('js/hero.js')) ?>"></script>
<script src="<?= e(asset_url('js/controls.js')) ?>"></script>
<script src="<?= e(asset_url('js/tiles.js')) ?>"></script>
<script src="<?= e(asset_url('js/spotlight.js')) ?>"></script>
<script src="<?= e(asset_url('js/wantlist.js')) ?>"></script>

</body>
</html>
