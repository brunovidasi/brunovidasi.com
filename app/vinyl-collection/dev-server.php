<?php

/**
 * Router for PHP's built-in server:
 *
 *     php -S localhost:8010 -t public dev-server.php
 *
 * In production .htaccess maps /admin onto public/admin.php and /lady-gaga onto
 * public/artist.php. The built-in server has no rewrite engine, so without this
 * file every extensionless link would 404 locally while working fine once
 * deployed — exactly the dev/prod drift the layout goes out of its way to avoid.
 *
 * It deliberately mirrors .htaccess and nothing more: same name patterns, same
 * "file must exist" checks, same fall-through to the artist page.
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

// /api/collection -> public/api/collection.php
if (preg_match('#^api/([A-Za-z0-9_]+)$#', $name, $m) && is_file("$public/api/$m[1].php")) {
    $script = "$public/api/$m[1].php";
    $name = "api/$m[1]";
} elseif (preg_match('/^[A-Za-z0-9_]+$/', $name) && is_file("$public/$name.php")) {
    // Single path segment only, matching the .htaccess pattern. This is also what
    // keeps a crafted /../config/config.php from resolving anywhere near the config.
    $script = "$public/$name.php";
} elseif (is_dir("$public/$name") && is_file("$public/$name/index.html")) {
    // DirectoryIndex for a real folder under public/ (the prototypes), which
    // .htaccess leaves to Apache and this router has to do itself.
    header('Content-Type: text/html; charset=utf-8');
    readfile("$public/$name/index.html");
    return true;
} elseif (preg_match('/^[a-z0-9][a-z0-9-]*$/', $name)) {
    // An artist slug. artist.php answers 404 itself for one it doesn't know.
    $_GET['slug'] = $name;
    $script = "$public/artist.php";
    $name = 'artist';
} else {
    http_response_code(404);
    $script = "$public/404.php";
    $name = '404';
}

// Pages read the current page name out of SCRIPT_NAME (see current_page() in
// includes/helpers.php); left alone it would say 'dev-server.php' every request.
$_SERVER['SCRIPT_NAME'] = "/$name.php";
$_SERVER['SCRIPT_FILENAME'] = $script;
$_SERVER['PHP_SELF'] = "/$name.php";

require $script;
return true;
