<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/bid_steps_view.php';
require_once __DIR__ . '/scheduled_bid_view.php';
require_once __DIR__ . '/anyway_bid_view.php';
require_once __DIR__ . '/auction_timeline.php';
require_once __DIR__ . '/EbayClient.php';
require_once __DIR__ . '/StripeClient.php';
require_once __DIR__ . '/billing.php';
require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/email_templates.php';
require_once __DIR__ . '/account_email.php';
require_once __DIR__ . '/bid_alerts.php';
require_once __DIR__ . '/runtime.php';

date_default_timezone_set(app_timezone());

configure_error_reporting();
start_app_session();
