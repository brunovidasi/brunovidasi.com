<?php
/**
 * @var string $title
 * @var string $intro
 * @var string $actions HTML for the right of the page heading
 */

$navActive = ADMIN_NAV_PARENTS[current_page()] ?? current_page();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> — Bruno's Music Collection</title>
<meta name="robots" content="noindex, nofollow">
<?php require __DIR__ . '/favicons.php'; ?>
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
      <?php foreach (ADMIN_NAV as $slug => $label): ?>
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
    <?= flash_box($flash['message'], $flash['kind']) ?>
  <?php endforeach; ?>

  <div class="page-head">
    <div>
      <h1><?= e($title) ?></h1>
      <?php if ($intro !== ''): ?><p><?= e($intro) ?></p><?php endif; ?>
    </div>
    <?php if ($actions !== ''): ?><div class="actions"><?= $actions ?></div><?php endif; ?>
  </div>
