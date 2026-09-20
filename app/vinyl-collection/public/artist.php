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
$others = db()->prepare('SELECT slug, name FROM artists WHERE is_published = 1 AND id != ? ORDER BY position, name');
$others->execute([$artist['id']]);
$others = $others->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($artist['name']) ?> — Bruno's Collection</title>
<meta name="description" content="<?= e($artist['tagline'] ?: "Every record by {$artist['name']} in Bruno's collection, era by era.") ?>">
<link rel="icon" href="<?= e(url('../assets/favicon.ico')) ?>">
<link rel="preload" href="<?= e(url('fonts/fraunces-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(url('fonts/space-grotesk-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(url('css/floor.css')) ?>">
<link rel="stylesheet" href="<?= e(url('css/artist.css')) ?>">
</head>
<body data-api="<?= e(url('api/')) ?>" data-version="<?= e(data_version()) ?>" data-slug="<?= e($artist['slug']) ?>" style="--accent: <?= e($accent) ?>">

<header class="hero">
  <div class="hero-inner">
    <div class="hero-text">
      <a class="back" href="<?= e(url('')) ?>">← The Collection</a>
      <div class="display"><?= e($artist['name']) ?></div>
      <p><?= e($artist['tagline'] ?: 'Every record in the collection, era by era.') ?></p>
      <?php if ($others): ?>
        <nav class="hero-links" aria-label="Other artists">
          <?php foreach ($others as $other): ?>
            <a href="<?= e(url($other['slug'])) ?>"><?= e($other['name']) ?></a>
          <?php endforeach; ?>
        </nav>
      <?php endif; ?>
    </div>
    <svg id="disc" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <circle cx="50" cy="50" r="48" fill="#0B0A08" stroke="#3a352c" stroke-width="1"/>
      <circle cx="50" cy="50" r="46" fill="none" stroke="#2a2620" stroke-width="0.6"/>
      <circle cx="50" cy="50" r="40" fill="none" stroke="#2a2620" stroke-width="0.6"/>
      <circle cx="50" cy="50" r="34" fill="none" stroke="#2a2620" stroke-width="0.6"/>
      <circle cx="50" cy="50" r="28" fill="none" stroke="#2a2620" stroke-width="0.6"/>
      <circle class="accent" cx="50" cy="50" r="16" fill="<?= e($accent) ?>"/>
      <circle cx="50" cy="50" r="3" fill="#0B0A08"/>
    </svg>
  </div>
</header>

<div class="controls">
  <input type="search" id="search" placeholder="Search by title or edition…" aria-label="Search <?= e($artist['name']) ?> records">
  <div class="chips" id="formats" role="group" aria-label="Filter by format"></div>
  <div class="seg" id="viewToggle" role="group" aria-label="View">
    <button type="button" data-view="grid">Grid</button>
    <button type="button" data-view="list">List</button>
  </div>
  <span class="meta" id="countMeta"></span>
</div>

<nav class="era-nav" id="eraNav" aria-label="Eras" hidden></nav>

<main>
  <div id="content">
    <div class="state"><p>Loading the collection…</p></div>
  </div>
</main>

<footer>
  <span>&copy; 2026<?php if (setting('last_successful_sync')): ?> · last synced <?= e(time_ago(setting('last_successful_sync'))) ?><?php endif; ?></span>
  <span>Made with &#10084;&#65039; by <a href="https://brunovida.si" target="_blank" rel="noopener">brunovida.si</a></span>
</footer>

<div id="tip"></div>
<div class="overlay" id="overlay"></div>
<aside class="drawer" id="drawer">
  <button class="drawer-close" id="drawerClose" aria-label="Close">✕</button>
  <div class="drawer-cover" id="drawerCover"></div>
  <div class="drawer-body" id="drawerBody"></div>
</aside>

<script src="<?= e(url('js/common.js')) ?>"></script>
<script src="<?= e(url('js/tiles.js')) ?>"></script>
<script src="<?= e(url('js/artist.js')) ?>"></script>

</body>
</html>
