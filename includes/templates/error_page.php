<?php
/**
 * The 404 and 500 pages. They need no database, so they still render when
 * that is what failed.
 *
 * @var array $error status, title, heading, message, and for the 404 the
 *                   console whose error screen it shows (templates/console_error.php)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../fields.php';

http_response_code($error['status']);

// the accent: the console's family colour, or the site's own
$family = array_key_first(array_filter(CONSOLE_FAMILIES, fn ($ids) => in_array($error['console'] ?? '', $ids, true)));
[$c, $ink] = match ($family) {
    'playstation' => ['#3b7be0', '#fff'],
    'nintendo'    => ['#e8303a', '#fff'],
    'xbox'        => ['#3aa64a', '#fff'],
    'pc'          => ['#9aa4ad', '#0d1424'],
    default       => ['var(--gold)', '#fff'],
};
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
<link rel="stylesheet" href="<?= e(asset_url('css/lost.css')) ?>">
<style>
  .lost { --c: <?= $c ?>; --c-ink: <?= $ink ?>; min-height: 100vh; display: grid; grid-template-columns: minmax(0, 680px);
    place-content: center; justify-items: center;
    text-align: center; padding: 24px 16px; gap: 14px; }
  .lost h1 { font: 800 clamp(26px, 5.5vw, 48px) var(--font-display); margin: 8px 0 0; letter-spacing: .02em; }
  .lost h1 em { color: var(--c); font-style: normal; }
  .lost > p { margin: 0; opacity: .75; max-width: 34em; }
</style>
</head>
<body>
<main class="lost">
<?php if (isset($error['console'])) { $console = $error['console']; require __DIR__ . '/console_error.php'; } ?>
  <h1><?= $error['heading'] ?></h1>
  <p><?= e($error['message']) ?></p>
  <a class="home" href="<?= e(url('')) ?>">Back to home</a>
</main>
</body>
</html>
