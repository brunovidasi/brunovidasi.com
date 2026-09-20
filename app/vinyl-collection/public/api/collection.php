<?php
/**
 * The whole shelf, as the site draws it.
 *
 * One request, every visible copy — the floor view needs them all at once to
 * lay out the pile, and the payload is small because item_card() carries only
 * what a sleeve needs. The drawer's full detail is a separate call per record
 * (api/item.php), made only when one is actually opened.
 */

require_once __DIR__ . '/../../includes/bootstrap_api.php';

json_cache_headers(300);

$rows = public_items('collection');

json_response([
    'updated' => setting('last_successful_sync'),
    'count'   => count($rows),
    'items'   => array_map('item_card', $rows),
]);
