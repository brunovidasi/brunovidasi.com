<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

json_cache_headers();

$rows = public_items('collection');

json_response([
    'updated' => setting('last_successful_sync'),
    'count'   => count($rows),
    'items'   => array_map('item_card', $rows),
]);
