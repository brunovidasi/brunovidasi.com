<?php

/** The bootstrap for the admin and its sign-in pages: the app, the admin's helpers and a session. */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/admin_ui.php';

start_app_session();

// the admin is never cached, nor shown in a frame
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('X-Robots-Tag: noindex, nofollow');
