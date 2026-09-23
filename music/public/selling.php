<?php
/**
 * The shop: everything up for sale, drawn as a plain grid of cards rather than
 * the collection's messy pile — a buyer wants price and a link, not a record
 * to dig through. Data arrives from api/selling (see js/selling.js).
 */

require_once __DIR__ . '/../includes/bootstrap_api.php';

if (!setting('show_selling', true)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$count = (int) db()->query("
    SELECT COUNT(*) FROM items
     WHERE source = 'for_sale' AND is_visible = 1 AND sold_at IS NULL
")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Selling — Bruno's Collection</title>
<meta name="description" content="Records and CDs Bruno is selling.">
<link rel="icon" type="image/svg+xml" href="<?= e(url('favicon/favicon.svg')) ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?= e(url('favicon/favicon-32x32.png')) ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= e(url('favicon/favicon-16x16.png')) ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?= e(url('favicon/apple-touch-icon.png')) ?>">
<link rel="manifest" href="<?= e(url('favicon/site.webmanifest')) ?>">
<link rel="preload" href="<?= e(url('fonts/fraunces-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(url('fonts/space-grotesk-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset_url('css/floor.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('css/artist.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('css/spotlight.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('css/selling.css')) ?>">
</head>
<body data-api="<?= e(url('api/')) ?>" data-version="<?= e(data_version()) ?>">

<header class="hero<?= hero_classes() ?>">
  <div class="hero-inner">
    <div class="hero-text">
      <a class="back" href="<?= e(url('')) ?>">← Full Collection</a>
      <div class="display"><?= hero_title('Selling') ?></div>
      <?= hero_rule() ?>
      <p><?= $count ?> record<?= $count === 1 ? '' : 's' ?> up for sale, sold through eBay.</p>
      <?= hero_links() ?>
    </div>
  </div>
  <?= hero_platter('sleeve') ?>
</header>

<div class="controls">
  <div class="controls-inner">
    <?= hero_search('Search title, artist, notes…', 'Search what\'s for sale') ?>
    <select id="sort" aria-label="Sort">
      <option value="listed-desc">Newest listed</option>
      <option value="price-asc">Price, low to high</option>
      <option value="price-desc">Price, high to low</option>
    </select>
    <div class="seg" id="viewToggle" role="group" aria-label="View">
      <button type="button" data-view="grid">Grid</button>
      <button type="button" data-view="list">List</button>
    </div>
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

<div class="overlay" id="overlay"></div>
<aside class="drawer" id="drawer">
  <button class="drawer-close" id="drawerClose" aria-label="Close">✕</button>
  <div class="drawer-cover" id="drawerCover"></div>
  <div class="drawer-body" id="drawerBody"></div>
</aside>

<script src="<?= e(asset_url('js/common.js')) ?>"></script>
<script src="<?= e(asset_url('js/hero.js')) ?>"></script>
<script src="<?= e(asset_url('js/controls.js')) ?>"></script>
<script src="<?= e(asset_url('js/dropdown.js')) ?>"></script>
<script src="<?= e(asset_url('js/tiles.js')) ?>"></script>
<script src="<?= e(asset_url('js/sale-spotlight.js')) ?>"></script>
<script src="<?= e(asset_url('js/selling.js')) ?>"></script>

</body>
</html>
