<?php
/**
 * The 404 and 500 pages. They need no database, so they still render when
 * that is what failed.
 *
 * @var array $error status, title, heading, message
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../hero.php';

http_response_code($error['status']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($error['title']) ?> — The Collection</title>
<?php require __DIR__ . '/favicons.php'; ?>
<link rel="stylesheet" href="<?= e(asset_url('css/floor.css')) ?>">
</head>
<body>

<header class="hero<?= hero_classes() ?>">
  <?= hero_intro() ?>
  <div class="hero-inner">
    <div class="hero-text">
      <div class="display"><?= hero_title($error['heading']) ?></div>
      <?= hero_rule() ?>
      <p><?= e($error['message']) ?></p>
      <p style="margin-top:1.2rem;"><a class="back" href="<?= e(url('')) ?>">← Full Collection</a></p>
    </div>
  </div>
  <?= hero_platter() ?>
</header>

</body>
</html>
