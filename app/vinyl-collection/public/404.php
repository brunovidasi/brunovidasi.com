<?php
/**
 * Styled 404. Reached through Apache's ErrorDocument, or included directly by a
 * page that knows the thing asked for doesn't exist (an unknown artist slug).
 */

if (!function_exists('url')) {
    require_once __DIR__ . '/../includes/config.php';
}

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Not found — The Collection</title>
<link rel="icon" href="../assets/favicon.ico">
<link rel="stylesheet" href="<?= htmlspecialchars(url('css/floor.css')) ?>">
</head>
<body>

<header class="hero">
  <div class="hero-inner">
    <div class="hero-text">
      <div class="display">Not in the crate</div>
      <p>That page isn't here. It may have been filed somewhere else.</p>
      <p style="margin-top:1.2rem;"><a class="back" href="<?= htmlspecialchars(url('')) ?>">← The Collection</a></p>
    </div>
    <svg id="disc" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <circle cx="50" cy="50" r="48" fill="#0B0A08" stroke="#3a352c" stroke-width="1"/>
      <circle cx="50" cy="50" r="16" fill="#C99A2E"/>
      <circle cx="50" cy="50" r="3" fill="#0B0A08"/>
    </svg>
  </div>
</header>

</body>
</html>
