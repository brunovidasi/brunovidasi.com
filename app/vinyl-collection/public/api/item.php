<?php
/**
 * One record's drawer: Bruno's fields, then Discogs', filtered by what
 * /admin_fields says this format should show.
 */

require_once __DIR__ . '/../../includes/bootstrap_api.php';

$item = item_by_id((int) query('id'));

// A hidden or removed record must 404 rather than open, or an old link would
// keep showing something the admin took off the site.
if ($item === null || !$item['is_visible'] || $item['missing_since'] !== null) {
    json_response(['error' => 'No such record.'], 404);
}

json_cache_headers(300);
json_response(item_drawer($item));
