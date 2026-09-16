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

    // Kept as an active admin on every request, so you can't lock yourself out.
    // Leave empty to disable.
    'owner_email' => '',

    // See https://developer.ebay.com/api-docs/static/rest-request-components.html#marketp
    // for other marketplace/site IDs. 15/EBAY_AU/AUD is Australia.
    'marketplace' => [
        'site_id'        => 15,
        'marketplace_id' => 'EBAY_AU',
        'currency'       => 'AUD',
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
