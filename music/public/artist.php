<?php
/**
 * An artist's page: Lady Gaga, Beyoncé, Anitta, RBD — and anything else added
 * in the admin. One file for all of them.
 *
 * It replaces the hand-written /lady-gaga page, and keeps its URL: the eras
 * that page listed in JavaScript are rows in the database now (seeded from it),
 * so the same records land in the same eras, in the same order.
 *
 * The page renders its own shell and hero; the records themselves arrive from
 * api/artist and are drawn by the same code as the main shelf (js/tiles.js), so
 * an era is a pile of the real objects rather than a row of thumbnails.
 */

require_once __DIR__ . '/../includes/bootstrap_api.php';

$artist = artist_by_slug(query('slug'));

if ($artist === null || !$artist['is_published']) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$accent = preg_match('/^#[0-9a-f]{3,8}$/i', (string) $artist['accent']) ? $artist['accent'] : '#C99A2E';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($artist['name']) ?> — Bruno's Collection</title>
<meta name="description" content="<?= e($artist['tagline'] ?: "Every record by {$artist['name']} in Bruno's collection, era by era.") ?>">
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
</head>
<body data-api="<?= e(url('api/')) ?>" data-version="<?= e(data_version()) ?>" data-slug="<?= e($artist['slug']) ?>" style="--accent: <?= e($accent) ?>">

<header class="hero<?= hero_classes() ?>">
  <div class="hero-inner">
    <div class="hero-text">
      <a class="back" href="<?= e(url('')) ?>">← Full Collection</a>
      <div class="display"><?= hero_title($artist['name']) ?></div>
      <?= hero_rule() ?>
      <p><?= e($artist['tagline'] ?: 'Every record in the collection, era by era.') ?></p>
      <?= hero_stats(hero_figures((int) $artist['id'])) ?>
      <?= hero_links((int) $artist['id'], false, 'Other artists') ?>
    </div>
  </div>
  <?= hero_platter('record', $accent) ?>
</header>

<div class="controls">
  <div class="controls-inner">
    <?= hero_search('Search title, barcode…', 'Search ' . $artist['name'] . ' records') ?>
    <div class="seg" id="orderToggle" role="group" aria-label="Order of eras">
      <button type="button" data-order="oldest">Oldest first</button>
      <button type="button" data-order="newest">Newest first</button>
    </div>
    <div class="seg" id="viewToggle" role="group" aria-label="View">
      <button type="button" data-view="grid">Grid</button>
      <button type="button" data-view="list">List</button>
    </div>
  </div>
</div>

<nav class="era-nav" id="eraNav" aria-label="Eras" hidden></nav>

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
<script src="<?= e(asset_url('js/tiles.js')) ?>"></script>
<script src="<?= e(asset_url('js/spotlight.js')) ?>"></script>
<script src="<?= e(asset_url('js/artist.js')) ?>"></script>

</body>
</html>
