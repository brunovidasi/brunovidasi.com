<?php

/**
 * In development, errors go to the screen. In production they must not: a stack
 * trace from this app would expose absolute server paths and SQL. They go to a log
 * inside the data directory instead, which sits outside the web root in production.
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
}

/**
 * Whether THIS request arrived over HTTPS. Used for the session cookie's 'secure'
 * flag: deriving that from base_url instead would mark the cookie secure whenever
 * base_url is https, and the browser would then refuse to send it back over a plain
 * http://localhost dev server — silently breaking login locally.
 *
 * X-Forwarded-Proto is trusted because a tunnel (cloudflared) or the host's proxy
 * terminates TLS upstream. Spoofing it only ever makes a cookie MORE restrictive.
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
 * The IP address of whoever is making this request, or null if it can't be determined.
 *
 * eBay's PlaceOffer requires an EndUserIP, and the bid itself is placed later by cron,
 * so the address is captured here, when the person saves the bid, and stored with it.
 *
 * REMOTE_ADDR is the real client only when nothing sits in front of the app. Behind a
 * proxy or CDN it is the proxy's address, so 'client_ip_header' in the config names the
 * header the proxy sets (e.g. 'X-Forwarded-For', 'CF-Connecting-IP'). It is only
 * trusted when configured, because any client can send that header itself.
 */
function client_ip(): ?string
{
    $header = app_config()['client_ip_header'] ?? '';
    if ($header !== '') {
        $value = $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $header))] ?? '';
        $first = trim(explode(',', $value)[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) {
            return $first;
        }
    }

    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : null;
}

/**
 * Session cookie scoped to this app's own URL path and hostname, so it isn't shared
 * with anything else running on the same domain, and given a distinct name so it
 * can't collide with a parent site's PHPSESSID.
 *
 * SameSite is deliberately 'Lax' rather than 'Strict': eBay's sign-in flow redirects
 * the user back to ebay_callback.php from ebay.com, and that request has to still
 * carry the session holding $_SESSION['ebay_session_id']. 'Strict' would drop the
 * cookie on that cross-site return and silently break connecting an eBay account.
 */
function start_app_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    session_name('BIDWRAITH_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => app_path(),
        // Always secure in production (where .htaccess forces HTTPS anyway); in
        // development follow the actual request, so plain-HTTP localhost still works.
        'secure'   => is_production() || request_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}
