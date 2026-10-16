<?php

/**
 * Checks that the API hands the site back exactly what the old JSON files said:
 *
 *   php cron/verify_import.php          (against seed/*.json)
 *   php cron/verify_import.php path/
 *
 * The order of an object's keys doesn't count, and neither does {} against []
 * (PHP can't tell an empty object from an empty list; nor does the site).
 * Run it right after cron/import_json.php, before any sync or edit.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Command line only.\n");
}

require_once __DIR__ . '/../includes/bootstrap.php';

$dir = rtrim($argv[1] ?? __DIR__ . '/../seed', '/');

$checks = [
    'games.json'      => fn () => ['updated' => data_updated(), 'games' => array_values(array_map('public_game', all_games()))],
    'steam.json'      => fn () => json_decode(platform_json('steam') ?? 'null', true),
    'psn.json'        => fn () => json_decode(platform_json('psn') ?? 'null', true),
    'nintendo.json'   => fn () => json_decode(platform_json('nintendo') ?? 'null', true),
    'xbox.json'       => fn () => json_decode(platform_json('xbox') ?? 'null', true),
    'ea.json'         => fn () => json_decode(platform_json('ea') ?? 'null', true),
    'rockstar.json'   => fn () => json_decode(platform_json('rockstar') ?? 'null', true),
    'sims-wants.json' => fn () => wants_json('sims'),
    'gta.json'        => fn () => gta_json(),
];

$failed = 0;
foreach ($checks as $file => $build) {
    if (!is_file("$dir/$file")) {
        echo "$file: no file, skipped\n";
        continue;
    }
    $want = json_decode((string) file_get_contents("$dir/$file"), true);
    if ($file === 'games.json') {
        $want['games'] = array_map('public_game', $want['games']);
    }
    $diffs = [];
    json_diff($want, $build(), '', $diffs);
    if ($diffs) {
        $failed++;
        echo "$file: DIFFERENT\n  " . implode("\n  ", $diffs) . "\n";
    } else {
        echo "$file: the same\n";
    }
}

exit($failed ? 1 : 0);
