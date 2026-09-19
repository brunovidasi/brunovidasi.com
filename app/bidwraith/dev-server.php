<?php

/**
 * Router for PHP's built-in server:
 *
 *     php -S localhost:8000 -t public dev-server.php
 *
 * In production .htaccess maps /dashboard onto public/dashboard.php. The built-in
 * server has no rewrite engine, so without this file every extensionless link would
 * 404 locally while working fine once deployed — exactly the dev/prod drift the rest
 * of the layout goes out of its way to avoid.
 *
 * It deliberately mirrors .htaccess and nothing more: same single-segment name
 * pattern, same "file must exist" check, same styled 404.
 */

$public = __DIR__ . '/public';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// A real file — an asset, or someone asking for a .php URL directly. Returning
// false hands it back to the built-in server, which serves or executes it itself.
if ($path !== '/' && is_file($public . $path)) {
    return false;
}

$name = trim($path, '/');

if ($name === '') {
    $name = 'index';
}

// Single path segment only, matching the .htaccess pattern. This is also what keeps
// a crafted /../config/config.php from resolving anywhere near the config.
if (preg_match('/^[A-Za-z0-9_]+$/', $name) && is_file("$public/$name.php")) {
    $script = "$public/$name.php";
} else {
    http_response_code(404);
    $script = "$public/404.php";
    $name = '404';
}

// The pages read the current page name out of SCRIPT_NAME (see current_page() in
// includes/helpers.php); left alone it would say 'dev-server.php' for every request.
$_SERVER['SCRIPT_NAME'] = "/$name.php";
$_SERVER['SCRIPT_FILENAME'] = $script;
$_SERVER['PHP_SELF'] = "/$name.php";

require $script;
return true;
