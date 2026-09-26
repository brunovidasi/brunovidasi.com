<?php

$error = [
    'status'  => 500,
    'title'   => 'Something broke',
    'heading' => 'A scratch in the record',
    'message' => 'Something went wrong on this page. It has been logged; try again in a moment.',
];

require __DIR__ . '/../includes/templates/error_page.php';
