<?php

/**
 * Brings the site's old JSON files into the database, once:
 *
 *   php cron/import_json.php            (seed/*.json, the files the site had before the admin)
 *   php cron/import_json.php path/      (the same files from another folder)
 *   php cron/import_json.php --force    (replace what the database already has)
 *
 * games.json becomes the games table; steam.json, psn.json, nintendo.json,
 * xbox.json, ea.json and rockstar.json the platform records; sims-wants.json
 * the want list and gta.json the GTA checklist. Nothing else is touched: the
 * account, the settings and the saved sign-ins stay. cron/verify_import.php
 * then checks the API hands back what the files said.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Command line only.\n");
}

require_once __DIR__ . '/../includes/bootstrap.php';

$args = array_slice($argv, 1);
$force = in_array('--force', $args, true);
$dir = rtrim(current(array_filter($args, fn ($a) => !str_starts_with($a, '--'))) ?: __DIR__ . '/../seed', '/');

$read = function (string $name) use ($dir): ?array {
    $file = "$dir/$name";
    if (!is_file($file)) {
        return null;
    }
    $data = json_decode((string) file_get_contents($file), true);
    if (!is_array($data)) {
        fwrite(STDERR, "$file is not valid JSON.\n");
        exit(1);
    }
    return $data;
};

$games = $read('games.json');
if ($games === null) {
    fwrite(STDERR, "No games.json in $dir.\n");
    exit(1);
}

$existing = (int) db()->query('SELECT COUNT(*) FROM games')->fetchColumn();
if ($existing && !$force) {
    fwrite(STDERR, "The database already has $existing games. Run again with --force to replace them (and the platform records, want list and GTA checklist).\n");
    exit(1);
}

db_transaction(function (PDO $db) use ($games, $read) {
    foreach (['games', 'field_state', 'platform_records', 'platform_record_history', 'platform_accounts', 'platform_overrides', 'wantlist', 'gta_games', 'gta_eras'] as $table) {
        $db->exec("DELETE FROM $table");
    }

    // the copies, in the file's order
    foreach (array_values($games['games']) as $i => $game) {
        insert_game($game, $i);
    }
    set_setting('seed_updated', $games['updated'] ?? date('Y-m-d'));
    echo count($games['games']) . " games\n";

    // what each platform said, its lists by id and the rest as the account's figures
    $files = ['steam' => 'steam.json', 'psn' => 'psn.json', 'nintendo' => 'nintendo.json', 'xbox' => 'xbox.json', 'ea' => 'ea.json', 'rockstar' => 'rockstar.json'];
    $insert = $db->prepare('INSERT INTO platform_records (platform, collection, ext_id, position, data_json, synced_at) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($files as $platform => $file) {
        $data = $read($file);
        if ($data === null) {
            echo "$file: none\n";
            continue;
        }
        $synced = $data['synced'] ?? null;
        $syncedAt = $synced ? gmdate('Y-m-d H:i:s', strtotime($synced)) : gmdate('Y-m-d H:i:s');
        $counts = [];
        foreach (array_keys(PLATFORMS[$platform]['collections']) as $collection) {
            $n = 0;
            foreach ($data[$collection] ?? [] as $extId => $record) {
                $insert->execute([$platform, $collection, (string) $extId, $n++, to_json($record), $syncedAt]);
            }
            $counts[] = "$n $collection";
            unset($data[$collection]);
        }
        set_platform_account($platform, $data);
        $db->prepare('UPDATE platform_accounts SET synced_at = ? WHERE platform = ?')->execute([$syncedAt, $platform]);
        echo "$file: " . implode(', ', $counts) . "\n";
    }

    // the Sims want list
    if ($wants = $read('sims-wants.json')) {
        $stmt = $db->prepare('INSERT INTO wantlist (id, collection, position, series, title, pack, pack_code, released, platform, note, extra_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($wants['wants'] as $i => $w) {
            $extra = array_diff_key($w, array_flip(['id', 'series', 'title', 'pack', 'packCode', 'released', 'platform', 'note']));
            $stmt->execute([$w['id'], 'sims', $i, $w['series'] ?? null, $w['title'], $w['pack'] ?? null, $w['packCode'] ?? null,
                $w['released'] ?? null, $w['platform'] ?? null, $w['note'] ?? null, $extra ? to_json($extra) : null]);
        }
        set_setting('wants_updated', $wants['updated'] ?? null);
        echo count($wants['wants']) . " on the Sims want list\n";
    }

    // the GTA checklist
    if ($gta = $read('gta.json')) {
        $era = $db->prepare('INSERT INTO gta_eras (id, position, name, blurb, extra_json) VALUES (?, ?, ?, ?, ?)');
        $game = $db->prepare('INSERT INTO gta_games (era_id, position, title, released, copies_json, missing_json, extra_json) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $n = 0;
        foreach ($gta['eras'] as $i => $e) {
            $extra = array_diff_key($e, array_flip(['id', 'name', 'blurb', 'games']));
            $era->execute([$e['id'], $i, $e['name'], $e['blurb'] ?? null, $extra ? to_json($extra) : null]);
            foreach ($e['games'] as $j => $g) {
                $extra = array_diff_key($g, array_flip(['title', 'released', 'copies', 'missing']));
                $game->execute([$e['id'], $j, $g['title'], $g['released'] ?? null, to_json($g['copies'] ?? []), to_json($g['missing'] ?? []), $extra ? to_json($extra) : null]);
                $n++;
            }
        }
        set_setting('gta_updated', $gta['updated'] ?? null);
        echo count($gta['eras']) . " GTA eras, $n games\n";
    }
});

// the import is the starting point, not a change: the site keeps the old "updated" dates
db()->exec("DELETE FROM settings WHERE key = 'data_changed_at'");
echo "Imported into " . db_path() . "\n";
