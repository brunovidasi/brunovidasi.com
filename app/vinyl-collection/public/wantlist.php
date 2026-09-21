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

$artists = db()->query('SELECT slug, name FROM artists WHERE is_published = 1 ORDER BY position, name')->fetchAll();
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
</head>
<body data-api="<?= e(url('api/')) ?>" data-version="<?= e(data_version()) ?>">

<header class="hero">
  <div class="hero-inner">
    <div class="hero-text">
      <a class="back" href="<?= e(url('')) ?>">← The Collection</a>
      <div class="display">Still Wanted</div>
      <p><?= $count ?> records that aren't on the shelf yet.</p>
      <?php if ($artists): ?>
        <nav class="hero-links" aria-label="Artist pages">
          <?php foreach ($artists as $artist): ?>
            <a href="<?= e(url($artist['slug'])) ?>"><?= e($artist['name']) ?></a>
          <?php endforeach; ?>
        </nav>
      <?php endif; ?>
    </div>
    <svg id="disc" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <circle cx="50" cy="50" r="48" fill="#0B0A08" stroke="#3a352c" stroke-width="1"/>
      <!-- an empty sleeve rather than a record: nothing here is owned yet -->
      <circle cx="50" cy="50" r="40" fill="none" stroke="#2a2620" stroke-width="0.6" stroke-dasharray="4 3"/>
      <circle cx="50" cy="50" r="28" fill="none" stroke="#2a2620" stroke-width="0.6" stroke-dasharray="4 3"/>
      <circle cx="50" cy="50" r="16" fill="none" stroke="#C99A2E" stroke-width="1.6" stroke-dasharray="3 3"/>
      <circle cx="50" cy="50" r="3" fill="#0B0A08" stroke="#3a352c" stroke-width="0.8"/>
    </svg>
  </div>
</header>

<div class="controls">
  <input type="search" id="search" placeholder="Search title, artist, barcode…" aria-label="Search the wantlist">
  <div class="chips" id="formats" role="group" aria-label="Filter by format"></div>
  <div class="seg" id="viewToggle" role="group" aria-label="View">
    <button type="button" data-view="floor">Floor</button>
    <button type="button" data-view="grid">Grid</button>
  </div>
  <label class="mess" id="messWrap">Messiness <input type="range" id="mess" min="0" max="1.4" step="0.05" value="0.6" aria-label="Messiness of the pile"></label>
  <span class="meta" id="countMeta"></span>
</div>

<main>
  <div id="content">
    <div class="state"><p>Loading…</p></div>
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
<script src="<?= e(asset_url('js/wantlist.js')) ?>"></script>

</body>
</html>
