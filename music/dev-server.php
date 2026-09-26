<?php

/**
 * The router for PHP's built-in server, which has no rewrite engine:
 *
 *     php -S localhost:8010 -t public dev-server.php
 *
 * It mirrors .htaccess and nothing more, so /admin and /lady-gaga resolve
 * locally exactly as they do on the server.
 */

$public = __DIR__ . '/public';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// A real file: the built-in server serves or runs it itself.
if ($path !== '/' && is_file($public . $path)) {
    return false;
}

$name = trim($path, '/');
if ($name === '') {
    $name = 'index';
}

if (preg_match('#^api/([A-Za-z0-9_]+)$#', $name, $m) && is_file("$public/api/$m[1].php")) {
    $script = "$public/api/$m[1].php";
    $name = "api/$m[1]";
} elseif (preg_match('/^[A-Za-z0-9_]+$/', $name) && is_file("$public/$name.php")) {
    // One path segment only, as in .htaccess, which also keeps /../config out of reach.
    $script = "$public/$name.php";
} elseif (is_dir("$public/$name") && is_file("$public/$name/index.html")) {
    // A folder's index.html (the prototypes), which Apache's DirectoryIndex serves in production.
    header('Content-Type: text/html; charset=utf-8');
    readfile("$public/$name/index.html");
    return true;
} elseif (preg_match('/^[a-z0-9][a-z0-9-]*$/', $name)) {
    // An artist's page; artist.php answers 404 for a slug it doesn't know.
    $_GET['slug'] = $name;
    $script = "$public/artist.php";
    $name = 'artist';
} else {
    http_response_code(404);
    $script = "$public/404.php";
    $name = '404';
}

// current_page() reads the page's name from SCRIPT_NAME.
$_SERVER['SCRIPT_NAME'] = "/$name.php";
$_SERVER['SCRIPT_FILENAME'] = $script;
$_SERVER['PHP_SELF'] = "/$name.php";

require $script;
return true;
