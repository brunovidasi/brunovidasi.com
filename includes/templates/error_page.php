<?php
/**
 * The 404 and 500 pages. They need no database, so they still render when
 * that is what failed. The 404 is a PS2's screen on an old TV (templates/ps2_error.php).
 *
 * @var array $error status, title, heading, message
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../fields.php';
require_once __DIR__ . '/../site.php';

http_response_code($error['status']);
$ps2 = $error['status'] === 404;

// the site's name as Settings has it, its last words in the accent colour; the defaults when the database is what failed
try {
    $site = function_exists('setting') ? site_settings() : SITE_DEFAULTS;
} catch (Throwable) {
    $site = SITE_DEFAULTS;
}
$name = (string) $site['name'];
$accent = (string) $site['accent'];
$brand = $accent !== '' && str_ends_with($name, $accent)
    ? e(rtrim(substr($name, 0, -strlen($accent)))) . ' <span>' . e($accent) . '</span>'
    : e($name);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($error['title']) ?> · <?= e($name) ?></title>
<meta name="robots" content="noindex">
<link rel="icon" href="<?= e(url('favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(url('css/site.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('css/lost.css')) ?>">
<style>
  .lost { --c: <?= $ps2 ? '#ff3344' : 'var(--gold)' ?>; --c-ink: #fff; min-height: 100vh; min-height: 100svh; display: grid;
    grid-template-columns: minmax(0, 680px); place-content: center; justify-items: center;
    text-align: center; padding: 24px 16px; gap: 14px; }
  .lost-ps2 { grid-template-columns: minmax(0, 1fr); padding: 24px 16px 56px; }
  .lost > h1 { font: 800 clamp(26px, 5.5vw, 48px) var(--font-display); margin: 8px 0 0; letter-spacing: .02em; }
  .lost > h1 em { color: var(--c); font-style: normal; }
  .lost > p { margin: 0; opacity: .75; max-width: 34em; }
</style>
</head>
<body>
<main class="lost<?= $ps2 ? ' lost-ps2' : '' ?>">
<?php if ($ps2): ?>
<?php require __DIR__ . '/ps2_error.php'; ?>
<?php else: ?>
  <h1><?= $error['heading'] ?></h1>
  <p><?= e($error['message']) ?></p>
  <a class="home" href="<?= e(url('')) ?>">Back to home</a>
<?php endif ?>
</main>
</body>
</html>
