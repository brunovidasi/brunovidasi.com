<?php

/**
 * The 500 page. See 404.php for why this lives outside public/.
 *
 * Currently reached by .htaccess's ErrorDocument 500 (an error Apache itself raises,
 * e.g. mod_security blocking a request) and by visiting it directly. A PHP fatal
 * isn't routed here yet — that needs a shutdown handler in includes/runtime.php,
 * which .htaccess's comment already describes but which doesn't exist yet.
 */
require_once __DIR__ . '/includes/bootstrap.php';

http_response_code(500);
$pageTitle = 'Something went wrong';
require __DIR__ . '/includes/layout_top.php';
?>
<h1>Something went wrong</h1>
<p class="hint">Sorry about that — the error's been logged. <a href="dashboard">Back to your auction list</a>.</p>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
