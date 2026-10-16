<?php
/**
 * @var string $title
 * @var string $intro
 * @var string $actions HTML for the right of the page heading
 */

$navActive = ADMIN_NAV_PARENTS[current_page()] ?? current_page();
$siteName = site_settings()['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> · <?= e($siteName) ?> admin</title>
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<link rel="icon" href="<?= e(url('favicon.svg')) ?>" type="image/svg+xml">
<link rel="preload" href="<?= e(url('fonts/exo2-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset_url('css/admin.css')) ?>">
</head>
<body>

<header class="admin-bar">
  <div class="admin-bar-inner">
    <a class="mark" href="<?= e(url('admin')) ?>"><?= e($siteName) ?> <span>Admin</span></a>

    <button type="button" class="menu-toggle" aria-expanded="false" aria-controls="adminNav" aria-label="Menu">
      <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>

    <nav class="admin-nav" id="adminNav" aria-label="Admin">
      <?php foreach (ADMIN_NAV as $slug => $label): ?>
        <a href="<?= e(url($slug)) ?>"<?= $navActive === $slug ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
      <a class="out" href="<?= e(url('')) ?>" target="_blank" rel="noopener">View Website ↗</a>
      <a class="out" href="<?= e(url('logout')) ?>">Sign Out</a>
    </nav>
  </div>
</header>

<main class="wrap">

  <?php foreach (take_flashes() as $flash): ?>
    <?= flash_box($flash['message'], $flash['kind']) ?>
  <?php endforeach; ?>

  <div class="page-head">
    <div>
      <h1><?= e($title) ?></h1>
      <?php if ($intro !== ''): ?><p><?= e($intro) ?></p><?php endif; ?>
    </div>
    <?php if ($actions !== ''): ?><div class="actions"><?= $actions ?></div><?php endif; ?>
  </div>
