<?php

/**
 * The shelf: every page of the site (?sims and ?gta are the same page in other
 * clothes, js/app.js). The texts, the views and the "Add me" links come from
 * Settings; the games from the API (api/games and the platforms beside it).
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$site = site_settings();
$shelf = page_settings('shelf');
$pages = page_settings();
$friends = array_filter(friend_settings(), fn ($f) => $f['show']);

// the name in the top bar, its last words in the accent colour
$name = (string) $site['name'];
$accent = (string) $site['accent'];
$brand = $accent !== '' && str_ends_with($name, $accent)
    ? e(rtrim(substr($name, 0, -strlen($accent)))) . ' <span>' . e($accent) . '</span>'
    : e($name);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($shelf['title']) ?></title>
<meta name="description" content="<?= e($shelf['description']) ?>">
<link rel="icon" href="favicon.svg" type="image/svg+xml">
<link rel="preload" href="fonts/orbitron-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="fonts/exo2-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset_url('css/site.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('css/cases.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('css/collection.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('css/sims.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('css/gta.css')) ?>">
</head>
<body>

<div class="topbar">
  <div class="brandbox">
    <a class="brand" href="<?= e(url('')) ?>"><?= $brand ?></a>
    <div class="figures" id="figures" aria-label="The collection in figures"></div>
  </div>
  <nav class="topnav" aria-label="Collections">
    <a href="<?= e(url('')) ?>" data-col="" aria-current="page"><?= e($pages['shelf']['nav']) ?></a>
    <a href="?sims" data-col="sims"><img class="nav-bob" src="assets/plumbob.svg" alt="" width="8" height="16"><?= e($pages['sims']['nav']) ?></a>
    <a href="?gta" data-col="gta"><?= e($pages['gta']['nav']) ?></a>
  </nav>
<?php if ($friends): ?>
  <nav class="addme" aria-label="Add me">
<?php foreach ($friends as $key => $f): ?>
    <a href="<?= e($f['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($f['aria']) ?>"><span class="lbl"><?= e($f['long']) ?></span><?= FRIEND_ICONS[$key] ?></a>
<?php endforeach; ?>
  </nav>
<?php endif; ?>
</div>
<?php if ($friends): ?>
  <nav class="addme addme-strip" aria-label="Add me">
    <span>Add me on</span>
<?php foreach ($friends as $key => $f): ?>
    <a href="<?= e($f['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($f['aria']) ?>"><span class="lbl"><?= e($f['label']) ?></span><?= FRIEND_ICONS[$key] ?></a>
<?php endforeach; ?>
  </nav>
<?php endif; ?>

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

<script>window.SITE = <?= json_encode(site_script_config(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
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
