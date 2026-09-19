<?php
// Thin wrapper: the real page lives at app/bidwraith/404.php, one level above the
// web root. This file exists only because .htaccess and dev-server.php can route
// to files inside public/ and nowhere else.
require __DIR__ . '/../404.php';
