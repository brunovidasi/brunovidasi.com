<?php

/** Every copy, as the site reads it (the shape data/games.json had). Hidden ones too: the site leaves them out. */

require_once __DIR__ . '/../../includes/bootstrap.php';

// each with when it was added (added), for the site's Recently added sort
$added = games_added();
$games = array_map(fn ($g) => public_game($g) + ['added' => $added[$g['id']] ?? null], all_games());

send_api_json(encode_json(['updated' => data_updated(), 'games' => array_values($games)]));
