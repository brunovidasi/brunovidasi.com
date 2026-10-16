<?php

require_once __DIR__ . '/../includes/config.php';

$error = [
    'status'  => 404,
    'title'   => 'Not found',
    'heading' => 'Error <em>404</em>',
    'message' => 'This game is not on the shelf.',
];

require __DIR__ . '/../includes/templates/error_page.php';
