<?php
/**
 * The shelf. Static markup, dynamic data: the records arrive from
 * api/collection (see js/script.js), and the only PHP here is the page's own
 * wording and the header's numbers and links to the artist pages (includes/hero.php).
 */

require_once __DIR__ . '/../includes/bootstrap_api.php';

$title = setting('site_title', 'The Collection');
$intro = setting('site_intro', "Every record, CD and disc Bruno owns, straight from the Discogs shelf.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> — Bruno's Vinyl</title>
<link rel="icon" href="<?= e(url('../assets/favicon.ico')) ?>">
<link rel="preload" href="<?= e(url('fonts/fraunces-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(url('fonts/space-grotesk-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset_url('css/floor.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('css/crate.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('css/morph.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('css/spotlight.css')) ?>">
</head>
<body data-api="<?= e(url('api/')) ?>" data-version="<?= e(data_version()) ?>">

<header class="hero<?= hero_classes() ?>">
  <div class="hero-inner">
    <div class="hero-text">
      <?= hero_eyebrow() ?>
      <div class="display"><?= hero_title($title) ?></div>
      <?= hero_rule() ?>
      <p><?= e($intro) ?></p>
      <?= hero_stats(hero_figures()) ?>
      <?= hero_links(null, true) ?>
    </div>
  </div>
  <?= hero_platter() ?>
</header>

<div class="controls">
  <div class="controls-inner">
    <?= hero_search('Search title, artist, barcode…', 'Search collection') ?>
    <select id="sort" aria-label="Sort collection">
      <option value="date-desc" data-short="Newest first">Release date, newest first</option>
      <option value="date-asc" data-short="Oldest first">Release date, oldest first</option>
      <option value="artist" data-short="Artist A–Z">Artist, A–Z</option>
      <option value="added" data-short="Recently added">Recently added</option>
      <option value="custom" disabled hidden></option>
    </select>
    <div class="organise" id="organiseWrap" hidden><span>Organise by</span> <select id="organiseBy" aria-label="Organise the crate by"></select></div>
    <div class="floor-tools">
      <label class="mess" id="messWrap">Messiness <input type="range" id="mess" min="0" max="1.4" step="0.05" value="0.7" aria-label="Messiness of the pile"></label>
      <button type="button" class="crate-btn" id="crateBtn">Put albums in a crate</button>
      <button type="button" class="crate-btn" id="digBtn" hidden>🎲 Dig a random one</button>
      <button type="button" class="ghost" id="messBtn" hidden>Back to the mess</button>
    </div>
    <div class="seg" id="viewToggle" role="group" aria-label="View">
      <button type="button" data-view="floor">Floor</button>
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
    <div class="state"><p>Loading the collection…</p></div>
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
<script src="<?= e(asset_url('js/dropdown.js')) ?>"></script>
<script src="<?= e(asset_url('js/tiles.js')) ?>"></script>
<script src="<?= e(asset_url('js/spotlight.js')) ?>"></script>
<script src="<?= e(asset_url('js/crate.js')) ?>"></script>
<script src="<?= e(asset_url('js/morph.js')) ?>"></script>
<script src="<?= e(asset_url('js/script.js')) ?>"></script>

</body>
</html>
