<?php

/**
 * Find cover, for a copy's edit page: "find" answers with every cover the
 * sources have for it, best first; "apply" saves the one picked as its cover
 * (with its shape and colours), and the page reloads to show it.
 */

require_once __DIR__ . '/../includes/admin.php';

admin_json_request();
session_write_close();

$game = find_game(post('id'));
if (!$game) {
    json_response(['error' => 'There is no such copy.'], 404);
}

try {
    if (post('action') === 'find') {
        @set_time_limit(120);
        $errors = [];
        $hits = find_covers($game, $errors);
        json_response([
            'covers' => array_map(fn ($h) => ['url' => $h['url'], 'source' => $h['source'], 'ref' => $h['ref'], 'offRegion' => !empty($h['offRegion'])], $hits),
            'errors' => $errors,
        ]);
    }

    if (post('action') === 'apply') {
        $url = post('url');
        $game = apply_cover($game, download_image($url), post('source') ?: (parse_url($url, PHP_URL_HOST) ?: 'manual'), post('ref') ?: null);
        update_game($game);
        // a cover picked by hand is yours: a sync no longer replaces it
        lock_field($game['id'], 'cover', true);
        lock_field($game['id'], 'colors', true);
        json_response(['cover' => $game['cover'], 'colors' => $game['colors']]);
    }
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 422);
}

json_response(['error' => 'Unknown action.'], 400);
