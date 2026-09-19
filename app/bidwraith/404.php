<?php

/**
 * The 404 page. Lives here rather than in public/ because it's shared: reached both
 * by public/404.php (the web-reachable wrapper .htaccess and dev-server.php route
 * to) and, eventually, anywhere else in the app that wants the same "not found"
 * rendering without a redirect.
 */
require_once __DIR__ . '/includes/bootstrap.php';

http_response_code(404);
$pageTitle = 'Page not found';
require __DIR__ . '/includes/layout_top.php';
?>
<h1>Page not found</h1>
<p class="hint">That page doesn't exist, or has moved. <a href="dashboard">Back to your auction list</a>.</p>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
