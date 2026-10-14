<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/fields.php';

// the console the lost address names (/ps2-final-fanatsy-x, /sims/wiiu-…), whose error it shows; else one at random
$path = substr(rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'), strlen(rtrim(app_path(), '/')));
$console = null;
foreach (explode('/', strtolower(trim($path, '/'))) as $part) {
    $id = explode('-', $part, 2)[0];
    if (isset(CONSOLES[$id])) {
        $console = $id;
        break;
    }
}

$error = [
    'status'  => 404,
    'title'   => 'Not found',
    'heading' => 'This game is not on the <em>shelf</em>',
    'message' => "Check the address, or head back and pick it out from the shelf.",
    'console' => $console ?? array_rand(CONSOLES),
];

require __DIR__ . '/../includes/templates/error_page.php';
