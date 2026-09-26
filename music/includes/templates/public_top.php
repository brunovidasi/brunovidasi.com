<?php
/**
 * The top of a public page, down to the end of the header.
 *
 * @var array $page title, description, styles, body (attributes), lead, heading,
 *                  intro (HTML), stats, links, platter
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page['title']) ?></title>
<?php if (isset($page['description'])): ?>
<meta name="description" content="<?= e($page['description']) ?>">
<?php endif; ?>
<?php require __DIR__ . '/favicons.php'; ?>
<link rel="preload" href="<?= e(url('fonts/fraunces-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(url('fonts/space-grotesk-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<?php foreach ($page['styles'] as $style): ?>
<link rel="stylesheet" href="<?= e(asset_url("css/$style.css")) ?>">
<?php endforeach; ?>
</head>
<body data-api="<?= e(url('api/')) ?>" data-version="<?= e(data_version()) ?>"<?= $page['body'] ?? '' ?>>

<header class="hero<?= hero_classes() ?>">
  <?= hero_intro() ?>
  <div class="hero-inner">
    <div class="hero-text">
      <?= $page['lead'] ?? '<a class="back" href="' . e(url('')) . '">← Full Collection</a>' ?>
      <div class="display"><?= hero_title($page['heading']) ?></div>
      <?= hero_rule() ?>
      <p><?= $page['intro'] ?></p>
<?php if (isset($page['stats'])): ?>
      <?= $page['stats'] ?>
<?php endif; ?>
      <?= $page['links'] ?>
    </div>
  </div>
  <?= $page['platter'] ?>
</header>
