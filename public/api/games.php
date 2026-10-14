<?php

/** Every copy, as the site reads it (the shape data/games.json had). Hidden ones too: the site leaves them out. */

require_once __DIR__ . '/../../includes/bootstrap.php';

send_api_json(encode_json(['updated' => data_updated(), 'games' => array_values(all_games())]));
