<?php

/**
 * The shelf: every page of the site (/sims and /gta are the same page in other
 * clothes, and /wiiu, /ps2-final-fantasy-x, /sims/wiiu are it with a console
 * picked or a game open, js/app.js). The texts, the views and the "Add me" links
 * come from Settings; the games from the API (api/games and the platforms beside it).
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// the address under the shelf: what isn't one of its pages is a 404
$route = trim(substr(rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'), strlen(rtrim(app_path(), '/'))), '/');
if ($route === 'index' || $route === 'index.php') {
    $route = '';
}
if (!shelf_route_exists($route)) {
    require __DIR__ . '/404.php';
    exit;
}

$site = site_settings();
$shelf = page_settings('shelf');
$pages = page_settings();
$friends = array_filter(friend_settings(), fn ($f) => $f['show']);
$config = site_script_config(); // before any output: it may open the session

// the collection in the address (/gta, /sims/ps2), dressed as it from the first paint, not as the shelf
// until js/app.js has the games
$col = explode('/', $route)[0];
$col = isset(PAGE_NAMES[$col]) && $col !== 'shelf' ? $col : '';
$title = $col !== '' ? $pages[$col]['title'] . ' · ' . $shelf['title'] : $shelf['title'];
$about = $col !== '' ? $pages[$col]['description'] : $shelf['description'];
$icon = $col !== '' ? "favicon-$col.svg" : 'favicon.svg';
$current = fn (string $c) => $c === $col ? ' aria-current="page"' : '';

// the name in the top bar, its last words in the accent colour
$name = (string) $site['name'];
$accent = (string) $site['accent'];
$brand = $accent !== '' && str_ends_with($name, $accent)
    ? e(rtrim(substr($name, 0, -strlen($accent)))) . ' <span>' . e($accent) . '</span>'
    : e($name);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en"<?= $col !== '' ? ' class="collection ' . e($col) . '"' : '' ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($about) ?>">
<link rel="icon" href="<?= e(url($icon)) ?>" type="image/svg+xml">
<link rel="preload" href="<?= e(url('fonts/orbitron-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(url('fonts/exo2-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset_url('css/site.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('css/cases.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('css/collection.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('css/sims.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('css/gta.css')) ?>">
</head>
<body>

<div class="topbar">
  <button type="button" class="menu-toggle" aria-label="Menu" aria-expanded="false" aria-controls="topmenu"><span></span></button>
  <div class="brandbox">
    <a class="brand" href="<?= e(url('')) ?>"><?= $brand ?></a>
    <div class="figures" id="figures" aria-label="The collection in figures"></div>
  </div>
  <div class="topmenu" id="topmenu">
  <nav class="topnav" aria-label="Collections">
    <a href="<?= e(url('')) ?>" data-col=""<?= $current('') ?>><?= e($pages['shelf']['nav']) ?></a>
    <a href="<?= e(url('sims')) ?>" data-col="sims"<?= $current('sims') ?>><img class="nav-bob" src="<?= e(url('assets/plumbob.svg')) ?>" alt="" width="8" height="16"><?= e($pages['sims']['nav']) ?></a>
    <a href="<?= e(url('gta')) ?>" data-col="gta"<?= $current('gta') ?>><?= e($pages['gta']['nav']) ?></a>
  </nav>
<?php if ($friends): ?>
  <nav class="addme" aria-label="Add me">
<?php foreach ($friends as $key => $f): ?>
<?php if ($f['copy']): ?>
    <button type="button" class="<?= $key ?>" data-copy="<?= e($f['url']) ?>" aria-label="<?= e($f['aria']) ?>"><span class="lbl"><?= e($f['long']) ?></span><?= FRIEND_ICONS[$key] ?></button>
<?php else: ?>
    <a class="<?= $key ?>" href="<?= e($f['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($f['aria']) ?>"><span class="lbl"><?= e($f['long']) ?></span><?= FRIEND_ICONS[$key] ?></a>
<?php endif; ?>
<?php endforeach; ?>
  </nav>
  <nav class="addme addme-strip" aria-label="Add me">
    <span>Add me on</span>
<?php foreach ($friends as $key => $f): ?>
<?php if ($f['copy']): ?>
    <button type="button" class="<?= $key ?>" data-copy="<?= e($f['url']) ?>" aria-label="<?= e($f['aria']) ?>"><span class="lbl"><?= e($f['label']) ?></span><?= FRIEND_ICONS[$key] ?></button>
<?php else: ?>
    <a class="<?= $key ?>" href="<?= e($f['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($f['aria']) ?>"><span class="lbl"><?= e($f['label']) ?></span><?= FRIEND_ICONS[$key] ?></a>
<?php endif; ?>
<?php endforeach; ?>
  </nav>
<?php endif; ?>
  </div>
</div>

<div id="shell"></div>

<div class="tools">
  <div class="grp" id="libWrap" hidden>
    <div class="pills" id="lib" role="group" aria-label="Filter PC games by library"></div>
  </div>
</div>

<main class="case-wrap"><div class="bookcase" id="case"></div></main>
<section id="checklist" aria-label="Checklist"></section>

<footer>
  <span>© <?= date('Y') ?></span>
  <span>Made with <span class="heart" aria-label="love">♥</span> by <a href="https://brunovida.si" target="_blank" rel="noopener">brunovida.si</a></span>
</footer>

<script>window.SITE = <?= json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script>
// on a phone the pages and the add-me buttons fold into a menu behind the burger at the left of the top bar
(() => {
  const bar = document.querySelector('.topbar'), toggle = bar.querySelector('.menu-toggle');
  const setMenu = on => {
    bar.classList.toggle('menu-open', on);
    toggle.setAttribute('aria-expanded', String(on));
    toggle.setAttribute('aria-label', on ? 'Close menu' : 'Menu');
  };
  toggle.addEventListener('click', () => setMenu(!bar.classList.contains('menu-open')));
  bar.querySelectorAll('.topnav a').forEach(a => a.addEventListener('click', () => setMenu(false)));
  document.addEventListener('click', e => { if (!bar.contains(e.target)) setMenu(false); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && bar.classList.contains('menu-open')) { setMenu(false); toggle.focus(); } });
  matchMedia('(max-width: 820px)').addEventListener('change', () => setMenu(false));
})();
// an "Add me" with no link (the EA app) copies the ID to search for, and says so for a moment
document.querySelectorAll('.addme [data-copy]').forEach(b => b.addEventListener('click', async () => {
  const lbl = b.querySelector('.lbl'), was = lbl.textContent;
  try { await navigator.clipboard.writeText(b.dataset.copy); lbl.textContent = 'Copied: ' + b.dataset.copy; }
  catch (e) { lbl.textContent = 'EA ID: ' + b.dataset.copy; }
  b.classList.add('copied');
  setTimeout(() => { lbl.textContent = was; b.classList.remove('copied'); }, 2500);
}));
</script>
<script src="<?= e(asset_url('js/consoles.js')) ?>"></script>
<script src="<?= e(asset_url('js/console-art.js')) ?>"></script>
<script src="<?= e(asset_url('js/cases.js')) ?>"></script>
<script src="<?= e(asset_url('js/collection.js')) ?>"></script>
<script src="<?= e(asset_url('js/sims.js')) ?>"></script>
<script src="<?= e(asset_url('js/gta.js')) ?>"></script>
<script src="<?= e(asset_url('js/dropdown.js')) ?>"></script>
<script src="<?= e(asset_url('js/app.js')) ?>"></script>
</body>
</html>
