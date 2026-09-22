<?php

/**
 * Locates the "instance directory" — the folder holding this deployment's real
 * config.php and its SQLite database. It deliberately lives OUTSIDE the deployed
 * tree, for three independent reasons:
 *
 *  1. This app ships inside a PUBLIC repo. A Discogs token and a password hash
 *     must not sit in a committable folder.
 *  2. The deployed tree is under public_html, i.e. web-reachable. A .sqlite file
 *     served over HTTP hands over the whole collection and the login hash.
 *  3. Deploys are an FTP sync that tracks its own state and removes what it no
 *     longer ships. It won't usually touch an unknown file, but a production
 *     database is not something to bet on that behaviour.
 *
 * Found by walking up from this file looking for a directory named
 * 'music-instance', so no absolute server path is ever hardcoded in the repo.
 * Returns null during local development, where config/config.php is used instead.
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
            // Deliberately vague in the response: this fires on a public URL in
            // the window between a first deploy and the config being created,
            // and the absolute path is not something to hand to a visitor. The
            // detail goes to the error log.
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
    return app_config()['timezone'] ?? 'UTC';
}

/** Full base URL of this deployment, no trailing slash. */
function base_url(): string
{
    return rtrim(env_config()['base_url'], '/');
}

/**
 * The URL path this app is mounted at, e.g. '/music/' when deployed
 * to a subfolder, or '/' locally. Used to scope the session cookie so it isn't
 * shared with anything else on the same hostname, and to build links.
 */
function app_path(): string
{
    $path = parse_url(base_url(), PHP_URL_PATH);
    return $path ? rtrim($path, '/') . '/' : '/';
}

/** An app URL from a path: url('admin') -> '/music/admin'. */
function url(string $path = ''): string
{
    return app_path() . ltrim($path, '/');
}

/**
 * The URL of a file under public/ that changes when the file does, so a browser
 * that cached last week's script can't run it against this week's API.
 */
function asset_url(string $path): string
{
    $mtime = (int) @filemtime(dirname(__DIR__) . '/public/' . ltrim($path, '/'));

    return url($path) . '?v=' . $mtime;
}

function is_debug(): bool
{
    return (bool) (env_config()['debug'] ?? !is_production());
}

/**
 * The one address allowed to sign in. Read from config rather than hardcoded so
 * a personal email isn't published in a public repo.
 */
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

/** Absolute path to the directory holding the SQLite database and logs. */
function data_dir(): string
{
    static $path = null;

    if ($path !== null) {
        return $path;
    }

    $configured = env_config()['data_dir'] ?? null;

    if ($configured !== null && $configured !== '') {
        // Absolute paths are used as-is; relative ones resolve against the
        // instance directory when there is one, otherwise the project root.
        $path = str_starts_with($configured, '/')
            ? rtrim($configured, '/')
            : rtrim((instance_dir() ?? __DIR__ . '/..') . '/' . $configured, '/');
    } else {
        $path = instance_dir() !== null ? instance_dir() . '/data' : __DIR__ . '/../data';
    }

    return $path;
}

/**
 * Absolute path to this environment's SQLite file. Separate per environment so
 * development's test data can never be mistaken for production's real data.
 */
function db_path(): string
{
    return data_dir() . '/' . (env_config()['db_file'] ?? 'collection.' . app_env() . '.sqlite');
}
