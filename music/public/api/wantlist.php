<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!setting('show_wantlist', true)) {
    json_response(['error' => 'The wantlist is not public.'], 404);
}

json_cache_headers();

json_response([
    'updated' => setting('last_successful_sync'),
    'wanted'  => array_map('item_card', public_items('wantlist')),
    'hunting' => array_map('item_card', public_items('searching')),
]);
