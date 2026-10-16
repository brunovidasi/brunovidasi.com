<?php

$error = [
    'status'  => 500,
    'title'   => 'Something went wrong',
    'heading' => 'Game <em>over</em>',
    'message' => 'Something went wrong on our side. Try again in a moment.',
];

require __DIR__ . '/../includes/templates/error_page.php';
