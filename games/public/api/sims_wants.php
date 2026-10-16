<?php

/** The Sims want list (the shape data/sims-wants.json had). */

require_once __DIR__ . '/../../includes/bootstrap.php';

send_api_json(encode_json(wants_json('sims')));
