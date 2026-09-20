<?php
/**
 * What isn't on the shelf yet: the Discogs wantlist, and the things being
 * hunted that Discogs doesn't list at all.
 */

require_once __DIR__ . '/../../includes/bootstrap_api.php';

if (!setting('show_wantlist', true)) {
    json_response(['error' => 'The wantlist is not public.'], 404);
}

json_cache_headers(300);

json_response([
    'updated'  => setting('last_successful_sync'),
    'wanted'   => array_map('item_card', public_items('wantlist')),
    'hunting'  => array_map('item_card', public_items('searching')),
]);
