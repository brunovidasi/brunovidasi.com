<?php

/**
 * Errors are shown in development and logged in production, where a stack
 * trace would give away server paths and SQL.
 */
function configure_error_reporting(): void
{
    error_reporting(E_ALL);

    if (is_debug()) {
        ini_set('display_errors', '1');
        return;
    }

    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

    $dir = data_dir();
    if (is_dir($dir) || @mkdir($dir, 0750, true)) {
        ini_set('error_log', $dir . '/php-error.log');
    }

    register_shutdown_function('render_fatal_error_page');
}

/** Apache's ErrorDocument never sees a PHP fatal, so this draws the app's own 500 page for one. */
function render_fatal_error_page(): void
{
    $error = error_get_last();
    if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }

    if (PHP_SAPI === 'cli') {
        return;
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }

    $page = __DIR__ . '/../public/500.php';
    if (is_file($page)) {
        include $page;
    }
}

/**
 * Whether this request came over HTTPS. The session cookie's secure flag uses
 * this rather than base_url, or login would break on a plain-http dev server.
 */
function request_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }

    $forwarded = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';

    return strtolower(trim(explode(',', $forwarded)[0])) === 'https';
}

/**
 * The session cookie is scoped to this app's path and named so it can't clash
 * with a sibling app on the same domain. Nothing ever links into the admin
 * from another site, so SameSite can be Strict.
 */
function start_app_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    session_name('VINYL_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => app_path(),
        'secure'   => is_production() || request_is_https(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    session_start();
}
