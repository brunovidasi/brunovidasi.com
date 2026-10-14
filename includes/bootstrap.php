<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/runtime.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/secrets.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/fields.php';
require_once __DIR__ . '/games.php';
require_once __DIR__ . '/platforms.php';
require_once __DIR__ . '/site.php';
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/images.php';
require_once __DIR__ . '/covers.php';
require_once __DIR__ . '/sync/engine.php';

date_default_timezone_set(app_timezone());
configure_error_reporting();
