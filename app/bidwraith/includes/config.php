<?php

/**
 * Locates the "instance directory" — the folder holding this deployment's real
 * config.php and its SQLite database. It deliberately lives OUTSIDE the deployed
 * tree, for three independent reasons:
 *
 *  1. This app ships inside a PUBLIC repo. eBay credentials and a database holding
 *     auth tokens that can place real bids must not sit in a committable folder.
 *  2. The deployed tree is under public_html, i.e. web-reachable. A .sqlite file
 *     served over HTTP is a complete credential dump in one request.
 *  3. Deploys are an FTP sync that tracks its own state and removes what it no
 *     longer ships. It won't usually touch an unknown file, but a production
 *     database is not something to bet on that behaviour.
 *
 * Found by walking up from this file looking for a directory named
 * 'bidwraith-instance', so no absolute server path is ever hardcoded in the repo.
 * Returns null during local development, where config/config.php is used instead.
 */
function instance_dir(): ?string
{
    static $dir = false;

    if ($dir !== false) {
        return $dir;
    }

    $fromEnv = getenv('BIDWRAITH_INSTANCE');
    if ($fromEnv !== false && $fromEnv !== '' && is_dir($fromEnv)) {
        return $dir = rtrim($fromEnv, '/');
    }

    $cursor = __DIR__;
    for ($i = 0; $i < 6; $i++) {
        $parent = dirname($cursor);
        if ($parent === $cursor) {
            break;
        }
        $cursor = $parent;
        if (is_dir($cursor . '/bidwraith-instance')) {
            return $dir = $cursor . '/bidwraith-instance';
        }
    }

    return $dir = null;
}

function config_path(): string
{
    $instance = instance_dir();
    return $instance !== null ? $instance . '/config.php' : __DIR__ . '/../config/config.php';
}

function app_config(): array
{
    static $config = null;

    if ($config === null) {
        $path = config_path();
        if (!file_exists($path)) {
            // Deliberately vague in the response: this fires on a public URL in the
            // window between a first deploy and the config being created, and the
            // absolute path is not something to hand to anyone who visits. The
            // detail goes to the error log, and preflight.php shows it to an admin.
            error_log('Bidwraith: no config file at ' . $path);
            http_response_code(500);
            die(PHP_SAPI === 'cli'
                ? "Missing config file at $path\n"
                : 'This application is not configured yet.');
        }
        $config = require $path;
    }

    return $config;
}

/** 'development' or 'production' — the one value that differs between machines. */
function app_env(): string
{
    return app_config()['env'];
}

function is_production(): bool
{
    return app_env() === 'production';
}

/** The active environment's settings block. */
function env_config(): array
{
    $config = app_config();
    $env = $config['env'];

    if (!isset($config['environments'][$env])) {
        http_response_code(500);
        die("Config error: env is '$env' but there is no 'environments' block by that name.");
    }

    return $config['environments'][$env];
}

function app_timezone(): string
{
    return app_config()['timezone'];
}

/** Full base URL of this deployment, no trailing slash. */
function base_url(): string
{
    return rtrim(env_config()['base_url'], '/');
}

/**
 * The URL eBay redirects back to after the user authorizes the app. This exact
 * string has to be registered as the RuName's "Accepted URL" in the eBay
 * Developer console — ebay_setup.php renders it for copy/paste.
 */
/**
 * eBay requires every production app that stores eBay-account-linked data (this one
 * stores the auth token) to expose an endpoint for Marketplace Account
 * Deletion/Closure notifications. The verification token is the shared secret eBay
 * uses to prove it's really eBay calling — generate one with:
 *   php -r "echo bin2hex(random_bytes(24));"
 * eBay requires 32-80 characters.
 */
function ebay_deletion_url(): string
{
    return base_url() . '/ebay_deletion.php';
}

function ebay_deletion_token(): string
{
    return (string) (app_config()['ebay_deletion_token'] ?? '');
}

function ebay_callback_url(): string
{
    return base_url() . '/ebay_callback.php';
}

/**
 * The URL path this app is mounted at, e.g. '/bidwraith/' when deployed to a
 * subfolder, or '/' locally. Used to scope the session cookie so it isn't shared
 * with anything else on the same hostname.
 */
function app_path(): string
{
    $path = parse_url(base_url(), PHP_URL_PATH);
    return $path ? rtrim($path, '/') . '/' : '/';
}

function is_debug(): bool
{
    return (bool) (env_config()['debug'] ?? !is_production());
}

function registration_allowed(): bool
{
    return (bool) (env_config()['allow_registration'] ?? true);
}

/**
 * The app owner's email, always kept as an active admin by db.php. Read from
 * config rather than hardcoded so a personal email isn't published in a public repo.
 */
function owner_email(): ?string
{
    $email = app_config()['owner_email'] ?? null;
    return $email !== '' ? $email : null;
}

/** Absolute path to the directory holding the SQLite database and logs. */
function data_dir(): string
{
    static $path = null;

    if ($path !== null) {
        return $path;
    }

    $configured = env_config()['data_dir'] ?? null;

    if ($configured !== null && $configured !== '') {
        // Absolute paths are used as-is; relative ones resolve against the instance
        // directory when there is one, otherwise against the project root.
        $path = str_starts_with($configured, '/')
            ? rtrim($configured, '/')
            : rtrim((instance_dir() ?? __DIR__ . '/..') . '/' . $configured, '/');
    } else {
        $path = instance_dir() !== null ? instance_dir() . '/data' : __DIR__ . '/../data';
    }

    return $path;
}

/** Absolute path to this environment's SQLite file. Separate per environment so */
/** development's test data can never be mistaken for production's real data. */
function db_path(): string
{
    return data_dir() . '/' . (env_config()['db_file'] ?? 'app.' . app_env() . '.sqlite');
}

/** Which eBay API side this environment talks to: 'sandbox' or 'production'. */
function ebay_api(): string
{
    $api = env_config()['ebay_api'];

    if (!in_array($api, ['sandbox', 'production'], true)) {
        http_response_code(500);
        die("Config error: ebay_api must be 'sandbox' or 'production', got '$api'.");
    }

    return $api;
}

/**
 * eBay credentials + marketplace settings for the active environment. Returns the
 * same shape it always has, so EbayClient and every page using ebay_config() need
 * no changes.
 *
 * Credentials are keyed by API side rather than by app environment, because the two
 * are deliberately independent: a production deployment can run against sandbox eBay
 * while real API keys are still being issued, and it must use the sandbox keys to do
 * so. Defining them once here means flipping ebay_api is genuinely a one-line change.
 */
function ebay_config(): array
{
    $config = app_config();
    $api = ebay_api();

    if (empty($config['ebay_keys'][$api])) {
        http_response_code(500);
        die("Config error: ebay_api is '$api' but there is no 'ebay_keys' block by that name.");
    }

    return $config['ebay_keys'][$api] + [
        'environment' => $api,
        'site_id' => $config['marketplace']['site_id'],
        'marketplace_id' => $config['marketplace']['marketplace_id'],
        'currency' => $config['marketplace']['currency'],
    ];
}

/** True when any of the active eBay credentials are still blank. */
function ebay_keys_missing(): array
{
    $keys = app_config()['ebay_keys'][ebay_api()] ?? [];
    return array_keys(array_filter(
        ['app_id' => 1, 'dev_id' => 1, 'cert_id' => 1, 'ru_name' => 1],
        fn ($_, $k) => empty($keys[$k]),
        ARRAY_FILTER_USE_BOTH
    ));
}
