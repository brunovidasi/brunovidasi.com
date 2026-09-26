<?php

/**
 * The instance directory holds this deployment's real config.php and its
 * database. It lives outside the deployed tree (the repo is public, and
 * public_html is web-reachable), and is found by walking up from here to a
 * folder named 'music-instance'. Null in local development.
 */
function instance_dir(): ?string
{
    static $dir = false;

    if ($dir !== false) {
        return $dir;
    }

    $fromEnv = getenv('MUSIC_INSTANCE');
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
        if (is_dir($cursor . '/music-instance')) {
            return $dir = $cursor . '/music-instance';
        }
    }

    return $dir = null;
}

function config_path(): string
{
    $instance = instance_dir();

    return $instance !== null ? $instance . '/config.php' : __DIR__ . '/../config/config.php';
}

function config_exists(): bool
{
    return file_exists(config_path());
}

function app_config(): array
{
    static $config = null;

    if ($config === null) {
        $path = config_path();
        if (!file_exists($path)) {
            // The path goes to the log only: this can be reached by a visitor
            // between a first deploy and the config being created.
            error_log('The Collection: no config file at ' . $path);
            http_response_code(500);
            die(PHP_SAPI === 'cli'
                ? "Missing config file at $path\n"
                : 'This application is not configured yet.');
        }
        $config = require $path;
    }

    return $config;
}

/** 'development' or 'production'. */
function app_env(): string
{
    return app_config()['env'];
}

function is_production(): bool
{
    return app_env() === 'production';
}

/** The settings block of the active environment. */
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
    return app_config()['timezone'] ?? 'UTC';
}

/** Full base URL of this deployment, without a trailing slash. */
function base_url(): string
{
    return rtrim(env_config()['base_url'], '/');
}

/** The URL path the app is mounted at: '/music/' in production, '/' locally. */
function app_path(): string
{
    $path = parse_url(base_url(), PHP_URL_PATH);

    return $path ? rtrim($path, '/') . '/' : '/';
}

/** url('admin') -> '/music/admin' */
function url(string $path = ''): string
{
    return app_path() . ltrim($path, '/');
}

/** A URL for a file under public/ that changes whenever the file does. */
function asset_url(string $path): string
{
    $mtime = (int) @filemtime(dirname(__DIR__) . '/public/' . ltrim($path, '/'));

    return url($path) . '?v=' . $mtime;
}

function is_debug(): bool
{
    return (bool) (env_config()['debug'] ?? !is_production());
}

/** The one address allowed to sign in, kept in the config so it isn't in the public repo. */
function owner_email(): ?string
{
    $email = app_config()['owner_email'] ?? null;

    return $email !== null && $email !== '' ? strtolower(trim($email)) : null;
}

function discogs_config(): array
{
    return (app_config()['discogs'] ?? []) + [
        'username'   => '',
        'token'      => '',
        'user_agent' => 'BrunoVinylCollection/1.0',
        'rate_limit' => 25,
    ];
}

function discogs_username(): string
{
    return (string) discogs_config()['username'];
}

function discogs_token(): string
{
    return (string) discogs_config()['token'];
}

function cron_token(): string
{
    return (string) (app_config()['cron_token'] ?? '');
}

/** Where the SQLite database and the logs live. */
function data_dir(): string
{
    static $path = null;

    if ($path !== null) {
        return $path;
    }

    $configured = env_config()['data_dir'] ?? null;

    if ($configured !== null && $configured !== '') {
        // Relative paths resolve against the instance directory, or the project root without one.
        $path = str_starts_with($configured, '/')
            ? rtrim($configured, '/')
            : rtrim((instance_dir() ?? __DIR__ . '/..') . '/' . $configured, '/');
    } else {
        $path = instance_dir() !== null ? instance_dir() . '/data' : __DIR__ . '/../data';
    }

    return $path;
}

/** One SQLite file per environment, so test data can't be mistaken for the real thing. */
function db_path(): string
{
    return data_dir() . '/' . (env_config()['db_file'] ?? 'collection.' . app_env() . '.sqlite');
}
