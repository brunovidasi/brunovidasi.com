<?php

/**
 * Where the platforms send you back to: Microsoft's "Connect Xbox", "Sign in
 * through Steam", and the "Send to my shelf" bookmark (which brings the PSN or
 * Nintendo sign-in in the address's #fragment, never sent to any server).
 *
 * The admin's session cookie is SameSite=Strict, so a browser leaves it off a
 * page another site sent it to. This page needs none: it hands everything on to
 * Settings → Connections from this site itself, where the cookie is sent and
 * the sign-in is checked against the session that started it.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');

$next = url('admin_settings') . '?tab=connections';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Connecting…</title>
<link rel="stylesheet" href="<?= e(asset_url('css/admin.css')) ?>">
</head>
<body class="auth-page">
<main class="auth"><div class="card"><h1>Connecting…</h1><p class="sub"><a id="go" href="<?= e($next) ?>">Continue to Settings</a></p></div></main>
<script>
  // the query (Microsoft's code, Steam's answer) and the #fragment (the bookmark's sign-in) go on to Settings
  var next = <?= json_encode($next) ?> + (location.search ? '&' + location.search.slice(1) : '') + location.hash;
  document.getElementById('go').href = next;
  location.replace(next);
</script>
</body>
</html>
