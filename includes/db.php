<?php

function db(): PDO
{
    static $db = null;

    if ($db !== null) {
        return $db;
    }

    $path = db_path();
    $isNew = !file_exists($path);

    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
        http_response_code(500);
        die('Data directory is not writable: ' . $dir);
    }

    $db = new PDO('sqlite:' . $path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('PRAGMA foreign_keys = ON');
    // WAL lets the site keep reading while a long sync writes.
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('PRAGMA busy_timeout = 5000');

    $db->exec(file_get_contents(__DIR__ . '/../sql/schema.sql'));
    run_migrations($db);

    if ($isNew) {
        @chmod($path, 0640);
    }

    return $db;
}

/**
 * Columns added after a table was first created. CREATE TABLE IF NOT EXISTS
 * can't add them to a database that already exists, so they go here rather
 * than into schema.sql.
 */
const MIGRATED_COLUMNS = [
    'hardware' => ['released' => 'TEXT', 'manufacturer' => 'TEXT', 'specs_json' => 'TEXT', 'photo_json' => 'TEXT'],
];

function run_migrations(PDO $db): void
{
    foreach (MIGRATED_COLUMNS as $table => $columns) {
        $existing = array_column($db->query("PRAGMA table_info($table)")->fetchAll(), 'name');
        foreach ($columns as $name => $type) {
            if (!in_array($name, $existing, true)) {
                $db->exec("ALTER TABLE $table ADD COLUMN $name $type");
            }
        }
    }

    // Once: a favourite used to face out on the shelf. Now Faces out is its own field, so each
    // favourite that faced out keeps doing so (one marked faceOut either way already says).
    if (!$db->query("SELECT 1 FROM settings WHERE key = 'migrated_fav_face_out'")->fetch()) {
        db_transaction(function (PDO $db) {
            $db->exec("
                UPDATE games SET extra_json = json_set(COALESCE(extra_json, '{}'), '$.faceOut', json('true'))
                WHERE fav = 1 AND json_type(COALESCE(extra_json, '{}'), '$.faceOut') IS NULL
            ");
            $db->exec("INSERT INTO settings (key, value) VALUES ('migrated_fav_face_out', 'true')");
            $db->exec("INSERT INTO settings (key, value) VALUES ('data_changed_at', '\"" . gmdate('c') . "\"')
                ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = datetime('now')");
        });
    }
}

/** A settings row, JSON-decoded and cached for the rest of the request. */
function setting(string $key, mixed $default = null): mixed
{
    $cache = &settings_cache();

    if (!array_key_exists($key, $cache)) {
        $stmt = db()->prepare('SELECT value FROM settings WHERE key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $cache[$key] = $row === false ? null : json_decode($row['value'], true);
    }

    return $cache[$key] ?? $default;
}

function set_setting(string $key, mixed $value): void
{
    db()->prepare("
        INSERT INTO settings (key, value) VALUES (?, ?)
        ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = datetime('now')
    ")->execute([$key, json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);

    $cache = &settings_cache();
    $cache[$key] = $value;
}

function &settings_cache(): array
{
    static $cache = [];

    return $cache;
}

/** A JSON column as an array, or $default when it is empty or not an array. */
function json_column(?string $raw, array $default = []): array
{
    if ($raw === null || $raw === '') {
        return $default;
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : $default;
}

/** Any value as JSON for a column, and back. */
function to_json(mixed $value): ?string
{
    return $value === null ? null : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function from_json(?string $raw): mixed
{
    return $raw === null || $raw === '' ? null : json_decode($raw, true);
}

/** Runs $fn inside a transaction, or inside the one already open. */
function db_transaction(callable $fn): mixed
{
    $db = db();
    if ($db->inTransaction()) {
        return $fn($db);
    }

    $db->beginTransaction();
    try {
        $result = $fn($db);
        $db->commit();
        return $result;
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Marks the public data as changed, so the API's ETag (data_version()) moves on
 * and the "updated" date the site is handed is today's.
 */
function touch_data(): void
{
    set_setting('data_changed_at', gmdate('c'));
}
