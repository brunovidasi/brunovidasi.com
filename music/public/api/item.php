<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

$item = item_by_id((int) query('id'));

// A hidden, removed or sold record 404s, so an old link can't keep showing it.
if ($item === null || !item_is_public($item)) {
    json_response(['error' => 'No such record.'], 404);
}

json_cache_headers();
json_response(item_drawer($item));
