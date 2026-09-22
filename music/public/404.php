<?php
/**
 * Styled 404. Reached through Apache's ErrorDocument, or included directly by a
 * page that knows the thing asked for doesn't exist (an unknown artist slug).
 */

if (!function_exists('url')) {
    require_once __DIR__ . '/../includes/config.php';
}
require_once __DIR__ . '/../includes/hero.php';

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Not found — The Collection</title>
<link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(url('favicon/favicon.svg')) ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars(url('favicon/favicon-32x32.png')) ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars(url('favicon/favicon-16x16.png')) ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars(url('favicon/apple-touch-icon.png')) ?>">
<link rel="manifest" href="<?= htmlspecialchars(url('favicon/site.webmanifest')) ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(url('css/floor.css')) ?>">
</head>
<body>

<header class="hero<?= hero_classes() ?>">
  <div class="hero-inner">
    <div class="hero-text">
      <div class="display"><?= hero_title('Not in the crate') ?></div>
      <?= hero_rule() ?>
      <p>That page isn't here. It may have been filed somewhere else.</p>
      <p style="margin-top:1.2rem;"><a class="back" href="<?= htmlspecialchars(url('')) ?>">← Full Collection</a></p>
    </div>
  </div>
  <?= hero_platter() ?>
</header>

</body>
</html>
