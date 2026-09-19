<?php
/**
 * Copy this to config/config.php for local development (it's gitignored).
 *
 * ON THE SERVER, DO NOT PUT THIS FILE HERE. The repo is public and the deployed
 * folder sits under public_html, so real keys must be neither committable nor
 * web-reachable.
 *
 * Instead, create a directory named 'bidwraith-instance' ABOVE public_html:
 *
 *   /home/<user>/domains/<domain>/bidwraith-instance/config.php   <- this file's contents
 *   /home/<user>/domains/<domain>/bidwraith-instance/data/        <- SQLite database
 *
 * The app walks up the directory tree to find it, so no absolute server path is
 * ever hardcoded in the repo. public/preflight.php reports which one it found.
 */

return [
    // The ONLY value that differs between your laptop and the server.
    'env' => 'development',

    'timezone' => 'America/Sao_Paulo',

    // Shared secret for public/cron_http.php, the HTTPS trigger for hosts whose cron
    // cannot run PHP (or cannot run at all). Generate with:
    //   php -r "echo bin2hex(random_bytes(24));"
    // Leave empty to keep the endpoint disabled.
    'cron_token' => '',
    // eBay's Marketplace Account Deletion/Closure notifications require this
    // endpoint (public/ebay_deletion.php) and a verification token, 32-80 chars.
    // Generate with:  php -r "echo bin2hex(random_bytes(24));"
    'ebay_deletion_token' => '',


    // eBay's PlaceOffer needs the bidder's IP address. It is recorded when a bid is
    // saved. If the app sits behind a proxy or CDN, name the header that carries the
    // visitor's real IP ('X-Forwarded-For', 'CF-Connecting-IP'); leave empty otherwise.
    'client_ip_header' => '',

    // Monthly subscription billing through Stripe (see "Billing" in the README).
    // Billing is OFF until both secret_key and price_id are set: nobody is gated and
    // the Billing page says plans aren't switched on. Use TEST keys (sk_test_…) in
    // development and LIVE keys (sk_live_…) on the server — this file already differs
    // between the two.
    'stripe' => [
        'secret_key'     => '',   // Developers -> API keys
        'price_id'       => '',   // The monthly recurring price, price_…
        'webhook_secret' => '',   // Developers -> Webhooks -> the endpoint's signing secret, whsec_…
        'trial_days'     => 7,    // 0 turns the trial off
        // true: Checkout asks for a card and charges when the trial ends.
        // false: no card to start; the subscription is cancelled if none is added by then.
        'trial_requires_card' => true,
    ],

    // Email: verification links, password resets, billing notices, bid alerts (see
    // "Email" in the README). Everything is queued and sent after the fact, so a slow or
    // down mail server never delays a page or a bid.
    'mail' => [
        // 'log'  writes messages to data/mail.log instead of sending (the default, and
        //        right for development — you can read verification links there)
        // 'smtp' a real mail server or provider — use this in production
        // 'mail' PHP's mail(), the host's own sendmail (works, but often lands in spam)
        'transport'  => 'log',
        // Must be an address on a domain you've set SPF/DKIM up for with your provider.
        'from_email' => 'noreply@example.com',
        'from_name'  => 'Bidwraith',
        'reply_to'   => '',          // where replies go, e.g. your own inbox; blank for none
        'smtp' => [
            'host'       => '',      // e.g. smtp.postmarkapp.com, smtp.resend.com, email-smtp.<region>.amazonaws.com
            'port'       => 587,
            'encryption' => 'tls',   // 'tls' = STARTTLS (587), 'ssl' = implicit TLS (465), '' = none
            'username'   => '',
            'password'   => '',
            'verify_peer' => true,   // leave on; only turn off to test against a self-signed server
        ],
        // Email the owner_email below about new sign-ups, trials, subscribers and failed payments.
        'admin_notifications' => true,
    ],

    // Kept as an active admin on every request, so you can't lock yourself out.
    // Leave empty to disable.
    'owner_email' => '',

    // See https://developer.ebay.com/api-docs/static/rest-request-components.html#marketp
    // for other marketplace/site IDs. 15/EBAY_AU is Australia. Bidding/display currency
    // is a per-user setting (users.currency), not configured here — see user_currency()
    // in includes/helpers.php.
    'marketplace' => [
        'site_id'        => 15,
        'marketplace_id' => 'EBAY_AU',
    ],

    // Keyed by eBay API side, NOT by app environment — a production deployment may
    // legitimately run against sandbox eBay while production keys are being issued.
    'ebay_keys' => [
        'sandbox' => [
            'app_id'  => '',
            'dev_id'  => '',
            'cert_id' => '',
            // eBay Developer Program -> Sandbox Keys -> "Get a Token from eBay via
            // Your Application". Its Accepted URL must be {base_url}/ebay_callback.php.
            'ru_name' => '',
        ],
        'production' => [
            'app_id'  => '',
            'dev_id'  => '',
            'cert_id' => '',
            // A SECOND RuName, created under Production Keys, pointing at the real domain.
            'ru_name' => '',
        ],
    ],

    'environments' => [

        'development' => [
            // eBay has to be able to REACH this URL for the OAuth callback, so a bare
            // localhost address won't work for connecting an account — use a tunnel
            // (cloudflared, ngrok) and register that URL on your sandbox RuName.
            'base_url' => 'http://localhost:8000',

            // null => the project's own data/ folder.
            'data_dir' => null,
            'db_file'  => 'app.development.sqlite',

            'ebay_api'           => 'sandbox',
            'debug'              => true,
            'allow_registration' => true,
        ],

        'production' => [
            'base_url' => 'https://example.com/bidwraith',

            // Relative paths resolve against the instance directory, keeping the
            // database outside the web root and safe from deploys.
            'data_dir' => 'data',
            'db_file'  => 'app.production.sqlite',

            // Can stay 'sandbox' while production eBay keys are still being issued,
            // which lets a live deployment be verified end to end with harmless bids.
            'ebay_api'           => 'production',
            'debug'              => false,
            'allow_registration' => false,
        ],
    ],
];
