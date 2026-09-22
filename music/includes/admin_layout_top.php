<?php
/**
 * Top half of every admin page. Pages set $pageTitle (and optionally
 * $pageIntro and $pageActions, a chunk of HTML for the right of the header)
 * before requiring this.
 */

$navItems = [
    'admin'          => 'Dashboard',
    'admin_items'    => 'Collection',
    'admin_wantlist' => 'Wantlist',
    'admin_artists'  => 'Artists & eras',
    'admin_fields'   => 'Drawer fields',
    'admin_settings' => 'Settings',
];

$page = current_page();
// The sub-pages light up their parent's tab.
$navActive = match ($page) {
    'admin_item'  => 'admin_items',
    'admin_eras'  => 'admin_artists',
    default       => $page,
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Admin') ?> — Bruno's Music Collection</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="<?= e(url('../assets/favicon.ico')) ?>">
<link rel="stylesheet" href="<?= e(url('css/admin.css')) ?>">
</head>
<body>

<header class="admin-bar">
  <div class="admin-bar-inner">
    <a class="mark" href="<?= e(url('admin')) ?>">
      <svg viewBox="0 0 100 100" aria-hidden="true">
        <circle cx="50" cy="50" r="48" fill="#0B0A08" stroke="#3a352c" stroke-width="2"/>
        <circle cx="50" cy="50" r="34" fill="none" stroke="#2a2620" stroke-width="3"/>
        <circle cx="50" cy="50" r="16" fill="#C99A2E"/>
        <circle cx="50" cy="50" r="4" fill="#0B0A08"/>
      </svg>
      Bruno's Music Collection
    </a>

    <nav class="admin-nav">
      <?php foreach ($navItems as $slug => $label): ?>
        <a href="<?= e(url($slug)) ?>"<?= $navActive === $slug ? ' class="on"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>

    <span class="who">
      <a href="<?= e(url('')) ?>" target="_blank" rel="noopener">View site ↗</a>
      &nbsp;·&nbsp;
      <a href="<?= e(url('logout')) ?>">Sign out</a>
    </span>
  </div>
</header>

<main class="wrap">

  <?php foreach (take_flashes() as $flash): ?>
    <div class="flash <?= e($flash['kind']) ?>"><?= e($flash['message']) ?></div>
  <?php endforeach; ?>

  <div class="page-head">
    <div>
      <h1><?= e($pageTitle ?? 'Admin') ?></h1>
      <?php if (!empty($pageIntro)): ?><p><?= e($pageIntro) ?></p><?php endif; ?>
    </div>
    <?php if (!empty($pageActions)): ?><div class="actions"><?= $pageActions ?></div><?php endif; ?>
  </div>
