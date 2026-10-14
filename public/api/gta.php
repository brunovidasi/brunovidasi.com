<?php

/** The GTA checklist (the shape data/gta.json had). */

require_once __DIR__ . '/../../includes/bootstrap.php';

send_api_json(encode_json(gta_json()));
