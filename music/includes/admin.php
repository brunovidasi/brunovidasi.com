<?php

/** The bootstrap for the admin and its sign-in pages: the app, the admin's helpers and a session. */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/admin_ui.php';
require_once __DIR__ . '/admin_records.php';

start_app_session();
