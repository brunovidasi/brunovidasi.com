<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/runtime.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/fields.php';
require_once __DIR__ . '/regions.php';
require_once __DIR__ . '/items.php';
require_once __DIR__ . '/hero.php';
require_once __DIR__ . '/DiscogsClient.php';
require_once __DIR__ . '/sync.php';

date_default_timezone_set(app_timezone());

configure_error_reporting();
start_app_session();
