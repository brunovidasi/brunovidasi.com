<?php
/**
 * The 404 and 500 pages. They need no database, so they still render when
 * that is what failed.
 *
 * @var array $error status, title, heading, message
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

http_response_code($error['status']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($error['title']) ?> · Bruno's Game Shelf</title>
<meta name="robots" content="noindex">
<link rel="icon" href="<?= e(url('favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(url('css/site.css')) ?>">
<style>
  .lost { min-height: 100vh; display: grid; place-content: center; text-align: center; padding: 24px 16px; gap: 14px; }
  .lost h1 { font: 800 clamp(28px, 6vw, 52px) var(--font-display); margin: 0; letter-spacing: .02em; }
  .lost h1 em { color: var(--gold); font-style: normal; }
  .lost p { margin: 0; opacity: .75; max-width: 34em; }
  .lost a { color: var(--gold); text-decoration: none; font-weight: 600; }
</style>
</head>
<body>
<main class="lost">
  <h1><?= $error['heading'] ?></h1>
  <p><?= e($error['message']) ?></p>
  <p><a href="<?= e(url('')) ?>">← Back to the shelf</a></p>
</main>
</body>
</html>
