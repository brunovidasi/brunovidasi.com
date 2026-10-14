<?php

$error = [
    'status'  => 404,
    'title'   => 'Not found',
    'heading' => 'Not on the <em>shelf</em>',
    'message' => "That page isn't here. It may have been filed somewhere else.",
];

require __DIR__ . '/../includes/templates/error_page.php';
