<?php

/** What the platform says about the games tied to it (the shape data/nintendo.json had), with the values corrected by hand. */

require_once __DIR__ . '/../../includes/bootstrap.php';

$json = platform_json('nintendo');
if ($json === null) {
    json_response(['error' => 'Not synced yet.'], 404);
}

send_api_json($json);
