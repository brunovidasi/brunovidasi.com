<?php
/**
 * Styled 500. Reached through Apache's ErrorDocument, and through the shutdown
 * handler in includes/runtime.php, which catches a PHP fatal that Apache would
 * otherwise answer with a blank page.
 */

if (!function_exists('url')) {
    require_once __DIR__ . '/../includes/config.php';
}
require_once __DIR__ . '/../includes/hero.php';

http_response_code(500);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Something broke — The Collection</title>
<link rel="icon" href="../assets/favicon.ico">
<link rel="stylesheet" href="<?= htmlspecialchars(url('css/floor.css')) ?>">
</head>
<body>

<header class="hero<?= hero_classes() ?>">
  <div class="hero-inner">
    <div class="hero-text">
      <div class="display"><?= hero_title('A scratch in the record') ?></div>
      <?= hero_rule() ?>
      <p>Something went wrong on this page. It has been logged; try again in a moment.</p>
      <p style="margin-top:1.2rem;"><a class="back" href="<?= htmlspecialchars(url('')) ?>">← Full Collection</a></p>
    </div>
  </div>
  <?= hero_platter() ?>
</header>

</body>
</html>
