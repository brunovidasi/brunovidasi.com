<?php

/**
 * The public API's bootstrap: the same app without a session.
 *
 * The JSON endpoints under public/api are read-only, anonymous, and cached by
 * the browser. Starting a session for them would mean a Set-Cookie on every
 * shelf load and, worse, a cached response carrying someone's session — so
 * they deliberately skip it.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/runtime.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/fields.php';
require_once __DIR__ . '/regions.php';
require_once __DIR__ . '/items.php';
require_once __DIR__ . '/hero.php';

date_default_timezone_set(app_timezone());
configure_error_reporting();
