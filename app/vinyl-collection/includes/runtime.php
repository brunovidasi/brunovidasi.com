<?php

/**
 * In development, errors go to the screen. In production they must not: a stack
 * trace would expose absolute server paths and SQL. They go to a log inside the
 * data directory instead, which sits outside the web root in production.
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

/**
 * Apache's ErrorDocument only fires for statuses Apache itself produces; a PHP
 * fatal sends a bare 500 with whatever half-page was already flushed. This turns
 * one into the app's own 500 page.
 */
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
 * Whether THIS request arrived over HTTPS. Used for the session cookie's
 * 'secure' flag: deriving that from base_url instead would mark the cookie
 * secure whenever base_url is https, and the browser would then refuse to send
 * it back over a plain http://localhost dev server — silently breaking login
 * locally.
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
 * Session cookie scoped to this app's own URL path and hostname, so it isn't
 * shared with anything else running on the same domain, and given a distinct
 * name so it can't collide with a sibling app's session.
 *
 * SameSite is 'Strict' here (unlike the eBay app next door): nothing ever
 * redirects into this admin from another site, so there is no cross-site return
 * that needs to carry the cookie.
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
