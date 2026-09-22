<?php
/**
 * Copy this to config/config.php for local development (it's gitignored).
 *
 * ON THE SERVER, DO NOT PUT THIS FILE HERE. The repo is public and the deployed
 * folder sits under public_html, so the Discogs token and your password hash
 * must be neither committable nor web-reachable.
 *
 * Instead, create a directory named 'music-instance' ABOVE public_html:
 *
 *   /home/<user>/domains/<domain>/music-instance/config.php   <- this file's contents
 *   /home/<user>/domains/<domain>/music-instance/data/        <- SQLite database
 *
 * The app walks up the directory tree to find it, so no absolute server path is
 * ever hardcoded in the repo. The admin dashboard's "Where things are" card
 * reports which one it found.
 */

return [
    // The ONLY value that differs between your laptop and the server.
    'env' => 'development',

    'timezone' => 'Australia/Sydney',

    // The one account that can sign in to the admin. Everything behind /admin is
    // for this address only; there is no registration page and no second user.
    // Create the password once at /setup, or reset it with:
    //   php cron/sync.php --help   (see the README's "Signing in" section)
    'owner_email' => 'you@example.com',

    'discogs' => [
        // Your Discogs username — whose collection and wantlist get synced.
        'username' => 'brunovidasi',

        // Personal access token: https://www.discogs.com/settings/developers
        // ("Generate token"). It more than doubles the rate limit — 60 requests
        // a minute against 25 — and every release's full detail is one request,
        // so a few hundred records is the difference between ten minutes and
        // half an hour.
        //
        // The token is read-only until you authorise writes; sending changes
        // back to Discogs (folders, notes, ratings) uses this same token.
        'token' => '',

        // Sent as User-Agent on every call. Discogs requires something
        // identifying, and blocks generic library defaults.
        'user_agent' => 'BrunoVinylCollection/1.0 +https://brunovida.si/music',

        // Requests per minute to stay under. Discogs allows 60 authenticated /
        // 25 anonymous; the client throttles itself to this and also obeys the
        // rate-limit headers it gets back.
        'rate_limit' => 55,
    ],

    // Shared secret for the daily cron URL:
    //   https://brunovida.si/music/cron_sync.php?token=…
    // Generate with:  php -r "echo bin2hex(random_bytes(24));"
    // Leave empty to keep the endpoint disabled — without a token it must never
    // be possible to trigger a sync anonymously.
    'cron_token' => '',

    'environments' => [
        'development' => [
            'base_url' => 'http://localhost:8010',
            'debug'    => true,
            // Relative paths resolve against the project root locally, or the
            // instance directory on the server. Leave null for the default.
            'data_dir' => null,
            'db_file'  => 'collection.development.sqlite',
        ],
        'production' => [
            'base_url' => 'https://brunovida.si/music',
            'debug'    => false,
            'data_dir' => null,
            'db_file'  => 'collection.production.sqlite',
        ],
    ],
];
