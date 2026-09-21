<?php
/**
 * The shelf. Static markup, dynamic data: the records arrive from
 * api/collection (see js/script.js), and the only PHP here is the page's own
 * wording and the links to the artist pages, both of which live in the admin.
 */

require_once __DIR__ . '/../includes/bootstrap_api.php';

$artists = db()->query('SELECT slug, name FROM artists WHERE is_published = 1 ORDER BY position, name')->fetchAll();
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

<header class="hero">
  <div class="hero-inner">
    <div class="hero-text">
      <div class="display"><?= e($title) ?></div>
      <p><?= e($intro) ?></p>
      <?php if ($artists): ?>
        <nav class="hero-links" aria-label="Artist pages">
          <?php foreach ($artists as $artist): ?>
            <a href="<?= e(url($artist['slug'])) ?>"><?= e($artist['name']) ?></a>
          <?php endforeach; ?>
          <?php if (setting('show_wantlist', true)): ?>
            <a class="wanted" href="<?= e(url('wantlist')) ?>">Wantlist</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    </div>
    <svg id="disc" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <circle cx="50" cy="50" r="48" fill="#0B0A08" stroke="#3a352c" stroke-width="1"/>
      <!-- the light catching the grooves and the mark on the label turn with the
           record; without them the disc is symmetric and the spin is invisible -->
      <path d="M50 50 L50 2 A48 48 0 0 1 88.8 21.8 Z" fill="#F2EAD8" opacity="0.07"/>
      <path d="M50 50 L50 98 A48 48 0 0 1 11.2 78.2 Z" fill="#F2EAD8" opacity="0.045"/>
      <circle cx="50" cy="50" r="46" fill="none" stroke="#2a2620" stroke-width="0.6"/>
      <circle cx="50" cy="50" r="40" fill="none" stroke="#2a2620" stroke-width="0.6"/>
      <circle cx="50" cy="50" r="34" fill="none" stroke="#2a2620" stroke-width="0.6"/>
      <circle cx="50" cy="50" r="28" fill="none" stroke="#2a2620" stroke-width="0.6"/>
      <circle cx="50" cy="50" r="16" fill="#C99A2E"/>
      <path d="M50 37.5 A12.5 12.5 0 0 1 60.8 43.75" fill="none" stroke="#0B0A08" stroke-width="1.6" stroke-linecap="round" opacity="0.45"/>
      <circle cx="50" cy="50" r="3" fill="#0B0A08"/>
    </svg>
  </div>
</header>

<div class="controls">
  <input type="search" id="search" placeholder="Search title, artist, barcode…" aria-label="Search collection">
  <div class="chips" id="formats" role="group" aria-label="Filter by format"></div>
  <select id="sort" aria-label="Sort collection">
    <option value="date-desc">Release date, newest first</option>
    <option value="date-asc">Release date, oldest first</option>
    <option value="artist">Artist, A–Z</option>
    <option value="added">Recently added</option>
    <option value="custom" disabled hidden></option>
  </select>
  <div class="seg" id="viewToggle" role="group" aria-label="View">
    <button type="button" data-view="floor">Floor</button>
    <button type="button" data-view="grid">Grid</button>
    <button type="button" data-view="list">List</button>
  </div>
  <button type="button" class="crate-btn" id="crateBtn">Put albums in a crate</button>
  <label class="mess" id="messWrap">Messiness <input type="range" id="mess" min="0" max="1.4" step="0.05" value="0.7" aria-label="Messiness of the pile"></label>
  <label class="organise" id="organiseWrap" hidden>Organise by <select id="organiseBy" aria-label="Organise the crate by"></select></label>
  <button type="button" class="crate-btn" id="digBtn" hidden>🎲 Dig a random one</button>
  <button type="button" class="ghost" id="messBtn" hidden>Back to the mess</button>
  <span class="meta" id="countMeta"></span>
</div>

<main>
  <div id="content">
    <div class="state"><p>Loading the collection…</p></div>
  </div>
</main>

<footer>
  <span>&copy; 2026</span>
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
<script src="<?= e(asset_url('js/tiles.js')) ?>"></script>
<script src="<?= e(asset_url('js/spotlight.js')) ?>"></script>
<script src="<?= e(asset_url('js/crate.js')) ?>"></script>
<script src="<?= e(asset_url('js/morph.js')) ?>"></script>
<script src="<?= e(asset_url('js/script.js')) ?>"></script>

</body>
</html>
