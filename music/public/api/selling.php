<?php
/**
 * Everything for sale: priced, visible, not yet sold.
 */

require_once __DIR__ . '/../../includes/bootstrap_api.php';

if (!setting('show_selling', true)) {
    json_response(['error' => 'The selling page is not public.'], 404);
}

json_cache_headers(300);

json_response([
    'updated' => setting('last_successful_sync'),
    'items'   => array_map('sale_card', public_items('for_sale', ['i.sold_at IS NULL'])),
]);
