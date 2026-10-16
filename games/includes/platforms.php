<?php

/**
 * What each platform says about the games tied to it: the records a sync keeps
 * (platform_records), the account's figures (platform_accounts), and the values
 * corrected by hand (platform_overrides). The site is handed each platform in
 * the shape data/steam.json, data/psn.json… had, by platform_json().
 */

const PLATFORMS = [
    'steam' => [
        'name' => 'Steam', 'short' => 'Steam',
        // the record lists, and the copy's field that ties a copy to a record in it
        'collections' => ['games' => 'steamAppId'],
        'about' => 'Steam\'s official Web API: the library, play time, achievements, and the store page of each game.',
        'unofficial' => false,
    ],
    'psn' => [
        'name' => 'PlayStation Network', 'short' => 'PSN',
        'collections' => ['titles' => 'psnTitleId', 'trophySets' => 'psnTrophySet'],
        'about' => 'The PS4 and PS5 play history, and the trophies of every PS3, PS4 and PS5 game, through the PlayStation app\'s own services.',
        'unofficial' => true,
    ],
    'xbox' => [
        'name' => 'Xbox', 'short' => 'Xbox',
        'collections' => ['titles' => 'xboxTitleId'],
        'about' => 'Every game played on the account, the minutes Xbox keeps, and the achievements, through the Xbox app\'s own services.',
        'unofficial' => true,
    ],
    'nintendo' => [
        'name' => 'Nintendo', 'short' => 'Nintendo',
        'collections' => ['titles' => 'nintendoTitleId'],
        'about' => 'The Switch and Switch 2 play history, as the Nintendo Store app shows it.',
        'unofficial' => true,
    ],
    'ea' => [
        'name' => 'EA app', 'short' => 'EA',
        'collections' => ['games' => 'eaSlug'],
        'about' => 'Every game and pack the EA account owns on PC, the play time the EA app keeps and the achievements, through the EA app\'s own service.',
        'unofficial' => true,
    ],
    'rockstar' => [
        'name' => 'Rockstar Games', 'short' => 'Rockstar',
        'collections' => ['games' => 'rockstarTitle'],
        'about' => 'The games the Rockstar account has played, where and when, and the PC achievements, through the Social Club.',
        'unofficial' => true,
    ],
];

/**
 * The synced values that can be corrected by hand on a copy's edit page, by
 * platform and record list: path in the record => [label, type].
 * type: minutes | datetime | int
 */
const PLATFORM_FIELDS = [
    'steam' => ['games' => [
        'playtime.total'        => ['Play time', 'minutes'],
        'lastPlayed'            => ['Last played', 'datetime'],
        'achievements.unlocked' => ['Achievements unlocked', 'int'],
        'achievements.total'    => ['Achievements in all', 'int'],
    ]],
    'psn' => [
        'titles' => [
            'playtime'    => ['Play time', 'minutes'],
            'playCount'   => ['Times played', 'int'],
            'firstPlayed' => ['First played', 'datetime'],
            'lastPlayed'  => ['Last played', 'datetime'],
        ],
        'trophySets' => [
            'earnedTotal'     => ['Trophies earned', 'int'],
            'total'           => ['Trophies in all', 'int'],
            'earned.platinum' => ['Platinum earned', 'int'],
            'earned.gold'     => ['Gold earned', 'int'],
            'earned.silver'   => ['Silver earned', 'int'],
            'earned.bronze'   => ['Bronze earned', 'int'],
        ],
    ],
    'xbox' => ['titles' => [
        'playtime'                  => ['Play time', 'minutes'],
        'lastPlayed'                => ['Last played', 'datetime'],
        'achievements.earned'       => ['Achievements unlocked', 'int'],
        'achievements.total'        => ['Achievements in all', 'int'],
        'achievements.gamerscore'   => ['Gamerscore', 'int'],
    ]],
    'nintendo' => ['titles' => [
        'playtime'    => ['Play time', 'minutes'],
        'playDays'    => ['Days played', 'int'],
        'firstPlayed' => ['First played', 'datetime'],
        'lastPlayed'  => ['Last played', 'datetime'],
    ]],
    'ea' => ['games' => [
        'playtime'            => ['Play time', 'minutes'],
        'lastPlayed'          => ['Last played', 'datetime'],
        'achievements.earned' => ['Achievements unlocked', 'int'],
        'achievements.total'  => ['Achievements in all', 'int'],
    ]],
    'rockstar' => ['games' => [
        'lastPlayed'          => ['Last played', 'datetime'],
        'achievements.earned' => ['Achievements unlocked', 'int'],
    ]],
];

function platform_name(string $platform): string
{
    return PLATFORMS[$platform]['name'] ?? $platform;
}

/** 'a.b' in a nested array. */
function path_get(array $data, string $path): mixed
{
    foreach (explode('.', $path) as $key) {
        if (!is_array($data) || !array_key_exists($key, $data)) {
            return null;
        }
        $data = $data[$key];
    }

    return $data;
}

function path_set(array &$data, string $path, mixed $value): void
{
    $ref = &$data;
    foreach (explode('.', $path) as $key) {
        if (!is_array($ref)) {
            $ref = [];
        }
        $ref = &$ref[$key];
    }
    $ref = $value;
}

/* ---------- records ---------- */

/** ext_id => record, for one list of a platform, in its order. */
function platform_records(string $platform, string $collection): array
{
    $stmt = db()->prepare('SELECT ext_id, data_json FROM platform_records WHERE platform = ? AND collection = ? ORDER BY position, ext_id');
    $stmt->execute([$platform, $collection]);
    $out = [];
    foreach ($stmt as $row) {
        $out[(string) $row['ext_id']] = json_decode($row['data_json'], true);
    }

    return $out;
}

function platform_record(string $platform, string $collection, string $extId): ?array
{
    $stmt = db()->prepare('SELECT data_json FROM platform_records WHERE platform = ? AND collection = ? AND ext_id = ?');
    $stmt->execute([$platform, $collection, $extId]);
    $raw = $stmt->fetchColumn();

    return $raw === false ? null : json_decode($raw, true);
}

function platform_record_row(string $platform, string $collection, string $extId): ?array
{
    $stmt = db()->prepare('SELECT * FROM platform_records WHERE platform = ? AND collection = ? AND ext_id = ?');
    $stmt->execute([$platform, $collection, $extId]);

    return $stmt->fetch() ?: null;
}

function platform_account(string $platform): ?array
{
    $stmt = db()->prepare('SELECT data_json FROM platform_accounts WHERE platform = ?');
    $stmt->execute([$platform]);
    $raw = $stmt->fetchColumn();

    return $raw === false ? null : json_decode($raw, true);
}

function set_platform_account(string $platform, array $data): void
{
    db()->prepare("
        INSERT INTO platform_accounts (platform, data_json) VALUES (?, ?)
        ON CONFLICT(platform) DO UPDATE SET data_json = excluded.data_json, synced_at = datetime('now')
    ")->execute([$platform, to_json($data)]);
}

/** The overrides of one platform, as "collection\0ext_id" => [path => row]. */
function platform_overrides(string $platform): array
{
    $stmt = db()->prepare('SELECT * FROM platform_overrides WHERE platform = ?');
    $stmt->execute([$platform]);
    $out = [];
    foreach ($stmt as $row) {
        $out[$row['collection'] . "\0" . $row['ext_id']][$row['path']] = $row;
    }

    return $out;
}

function record_overrides(string $platform, string $collection, string $extId): array
{
    $stmt = db()->prepare('SELECT * FROM platform_overrides WHERE platform = ? AND collection = ? AND ext_id = ?');
    $stmt->execute([$platform, $collection, $extId]);

    return array_column($stmt->fetchAll(), null, 'path');
}

function set_override(string $platform, string $collection, string $extId, string $path, mixed $value, string $mode, mixed $base): void
{
    db()->prepare("
        INSERT INTO platform_overrides (platform, collection, ext_id, path, value_json, mode, base_json) VALUES (?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT(platform, collection, ext_id, path) DO UPDATE SET value_json = excluded.value_json, mode = excluded.mode,
            base_json = excluded.base_json, updated_at = datetime('now')
    ")->execute([$platform, $collection, $extId, $path, to_json($value), $mode === 'until_sync' ? 'until_sync' : 'keep', to_json($base)]);
    touch_data();
}

function clear_override(string $platform, string $collection, string $extId, string $path): void
{
    db()->prepare('DELETE FROM platform_overrides WHERE platform = ? AND collection = ? AND ext_id = ? AND path = ?')
        ->execute([$platform, $collection, $extId, $path]);
    touch_data();
}

/** A record with the values corrected by hand put over the synced ones. */
function apply_overrides(array $record, array $overrides): array
{
    foreach ($overrides as $path => $o) {
        path_set($record, $path, from_json($o['value_json']));
    }

    return $record;
}

/**
 * One platform as the site reads it: the account's figures and each list of
 * records by id, with the corrections put in. Built as a string from the stored
 * JSON, so a record no one corrected goes out exactly as it was kept.
 */
function platform_json(string $platform): ?string
{
    $account = platform_account($platform);
    if ($account === null) {
        return null;
    }

    $overrides = platform_overrides($platform);
    $parts = [];
    foreach ($account as $key => $value) {
        $parts[] = json_encode((string) $key) . ':' . json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    foreach (array_keys(PLATFORMS[$platform]['collections']) as $collection) {
        $stmt = db()->prepare('SELECT ext_id, data_json FROM platform_records WHERE platform = ? AND collection = ? ORDER BY position, ext_id');
        $stmt->execute([$platform, $collection]);
        $records = [];
        foreach ($stmt as $row) {
            $data = $row['data_json'];
            $o = $overrides[$collection . "\0" . $row['ext_id']] ?? null;
            if ($o) {
                $data = json_encode(apply_overrides(json_decode($data, true), $o), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            $records[] = json_encode((string) $row['ext_id']) . ':' . $data;
        }
        $parts[] = json_encode($collection) . ':{' . implode(',', $records) . '}';
    }

    return '{' . implode(',', $parts) . '}';
}

/** The ids a copy is tied to in one list (a copy with several games in it has several). */
function game_links(array $game, string $field): array
{
    $value = $game[$field] ?? null;
    if ($value === null || $value === '' || $value === []) {
        return [];
    }

    return array_map('strval', is_array($value) ? $value : [$value]);
}

/** platform => [collection => [ext_id…]] a copy is tied to. */
function game_platform_links(array $game): array
{
    $out = [];
    foreach (PLATFORMS as $platform => $p) {
        foreach ($p['collections'] as $collection => $field) {
            $ids = game_links($game, $field);
            if ($ids) {
                $out[$platform][$collection] = $ids;
            }
        }
    }

    return $out;
}

/**
 * What the platforms say about each copy, for the admin's collection list: its
 * play time (minutes), achievements or trophies earned and in all, and when it
 * was last played, added up over every record it is tied to, with the values
 * corrected by hand put in. game id => [minutes, earned, total, lastPlayed].
 */
function games_play_stats(array $games): array
{
    // where each platform keeps the figures, by record list
    $paths = [
        'steam'    => ['games' => ['playtime.total', 'achievements.unlocked', 'achievements.total', 'lastPlayed']],
        'psn'      => ['titles' => ['playtime', null, null, 'lastPlayed'], 'trophySets' => [null, 'earnedTotal', 'total', null]],
        'xbox'     => ['titles' => ['playtime', 'achievements.earned', 'achievements.total', 'lastPlayed']],
        'nintendo' => ['titles' => ['playtime', null, null, 'lastPlayed']],
        'ea'       => ['games' => ['playtime', 'achievements.earned', 'achievements.total', 'lastPlayed']],
        'rockstar' => ['games' => [null, 'achievements.earned', null, 'lastPlayed']],
    ];
    $records = [];
    foreach ($paths as $platform => $lists) {
        $overrides = platform_overrides($platform);
        foreach (array_keys($lists) as $collection) {
            foreach (platform_records($platform, $collection) as $extId => $record) {
                $records[$platform][$collection][$extId] = apply_overrides($record, $overrides[$collection . "\0" . $extId] ?? []);
            }
        }
    }

    $out = [];
    foreach ($games as $g) {
        $stats = ['minutes' => null, 'earned' => null, 'total' => null, 'lastPlayed' => null];
        foreach (game_platform_links($g) as $platform => $lists) {
            foreach ($lists as $collection => $ids) {
                [$minutes, $earned, $total, $last] = $paths[$platform][$collection] ?? [null, null, null, null];
                foreach ($ids as $extId) {
                    $r = $records[$platform][$collection][$extId] ?? null;
                    if (!$r) {
                        continue;
                    }
                    foreach (['minutes' => $minutes, 'earned' => $earned, 'total' => $total] as $k => $path) {
                        $v = $path ? path_get($r, $path) : null;
                        if (is_numeric($v)) {
                            $stats[$k] = ($stats[$k] ?? 0) + (int) $v;
                        }
                    }
                    $v = $last ? path_get($r, $last) : null;
                    if (is_string($v) && $v > (string) $stats['lastPlayed']) {
                        $stats['lastPlayed'] = $v;
                    }
                }
            }
        }
        $out[$g['id']] = $stats;
    }

    return $out;
}
