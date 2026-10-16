<?php

/**
 * Copy this to config/config.php for local development (it's gitignored).
 *
 * ON THE SERVER, DO NOT PUT THIS FILE HERE. The repo is public and the deployed
 * folder sits under public_html, so the key that unlocks the saved sign-ins
 * must be neither committable nor web-reachable.
 *
 * Instead, create a directory named 'games-instance' ABOVE public_html:
 *
 *   /home/<user>/domains/<domain>/games-instance/config.php   <- this file's contents
 *   /home/<user>/domains/<domain>/games-instance/data/        <- SQLite database
 *
 * The app walks up the directory tree to find it, so no absolute server path is
 * ever hardcoded in the repo. The admin dashboard's "About the site" card
 * reports which one it found.
 *
 * Everything else — the Steam key, the PSN, Xbox, Nintendo, EA and Rockstar
 * sign-ins, the cron token — is set in the admin (Settings → Connections) and
 * kept in the database, encrypted with app_key below.
 */

return [
    // The ONLY value that differs between your laptop and the server.
    'env' => 'development',

    'timezone' => 'Australia/Sydney',

    // The one account that can sign in to the admin. Everything behind /admin is
    // for this address only; there is no registration page and no second user.
    // Create the password once at /setup; the README's "Signing in" says how to reset it.
    'owner_email' => 'you@example.com',

    // Encrypts the API keys and sign-in tokens kept in the database, so a copy of
    // the database alone gives none of them away. Generate one with:
    //   php -r "echo bin2hex(random_bytes(32));"
    // Keep the same key when moving the database to another machine, or the
    // saved sign-ins can't be read and have to be entered again.
    'app_key' => '',

    'environments' => [
        'development' => [
            'base_url' => 'http://localhost:8020',
            'debug'    => true,
            // Relative paths resolve against the project root locally, or the
            // instance directory on the server. Leave null for the default.
            'data_dir' => null,
            'db_file'  => 'games.development.sqlite',
        ],
        'production' => [
            'base_url' => 'https://brunovida.si/games',
            'debug'    => false,
            'data_dir' => null,
            'db_file'  => 'games.production.sqlite',
        ],
    ],
];
