<?php

function db(): PDO
{
    static $db = null;

    if ($db === null) {
        $dbPath = db_path();
        $isNew = !file_exists($dbPath);

        // The data directory lives outside the deployed tree in production, so
        // it won't exist until the first request after a fresh deploy.
        $dir = dirname($dbPath);
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            http_response_code(500);
            die('Data directory is not writable: ' . $dir);
        }

        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec('PRAGMA foreign_keys = ON');
        // A sync writes for a long time while the site keeps reading. WAL lets
        // those happen at once instead of handing visitors a "database is
        // locked"; the busy timeout covers the brief moments it can't.
        $db->exec('PRAGMA journal_mode = WAL');
        $db->exec('PRAGMA busy_timeout = 5000');

        $schema = file_get_contents(__DIR__ . '/../sql/schema.sql');
        $db->exec($schema);
        run_migrations($db);

        if ($isNew) {
            chmod($dbPath, 0640);
            seed_database($db);
        }
    }

    return $db;
}

/**
 * Adds columns introduced after a table's initial CREATE TABLE, for databases
 * that already existed before that column was added. schema.sql alone can't do
 * this since CREATE TABLE IF NOT EXISTS is a no-op once the table exists.
 */
function run_migrations(PDO $db): void
{
    $columns = [
        // Add entries here, never edit a CREATE TABLE in schema.sql, once a
        // database exists in production.
        'items' => [
            // Set when the artist page was chosen by hand, so a sync keeps it.
            'artist_locked' => 'INTEGER NOT NULL DEFAULT 0',
            // The box a disc sits in: a CD from a box set that Discogs holds only
            // as the box, kept as its own record so it can be filed in its era.
            'parent_item_id' => 'INTEGER',
            // How many discs are in the sleeve, and the picture on each: JSON,
            // {"count": 2|null, "art": ["url", "", ""]}. Null count means what
            // Discogs' formats say; see disc_config() in includes/items.php.
            'disc_config'    => 'TEXT',
            // A vinyl's colour picked by hand, "#rrggbb". Blank means the
            // keyword match on the colour text; see vinyl_color() in includes/items.php.
            'vinyl_hex'      => 'TEXT',
            // For a DVD that actually came in a CD-sized jewel case: 'cd' draws
            // that case instead of the tall DVD one. Blank/NULL means the DVD
            // case, and it's ignored on every other format; see item_card().
            'case_kind'      => 'TEXT',
            // 1 = translucent, 0 = opaque, NULL = as the colour text says
            // ("Clear", "Transparent"…); see item_discs() in includes/items.php.
            'vinyl_translucent' => 'INTEGER',
            // Discogs' facts corrected by hand (see OVERRIDE_FIELDS). A sync
            // rewrites the release cache and never these.
            'labels'         => 'TEXT',
            'catalog_number' => 'TEXT',
            'formats'        => 'TEXT',
            'genres'         => 'TEXT',
            'styles'         => 'TEXT',
            'tracklist'      => 'TEXT',
        ],
        'artists' => [
            // The picture on this artist's pill in the header, a URL. Blank means
            // their first record with a cover; see hero_artist_cover().
            'hero_cover' => 'TEXT',
        ],
    ];

    foreach ($columns as $table => $cols) {
        $existing = array_column($db->query("PRAGMA table_info($table)")->fetchAll(), 'name');
        foreach ($cols as $name => $type) {
            if (!in_array($name, $existing, true)) {
                $db->exec("ALTER TABLE $table ADD COLUMN $name $type");
            }
        }
    }
}

/**
 * Reads a settings row, JSON-decoded. Cached per request — the drawer field
 * config is read on every public page load.
 */
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

/** The shared store behind setting()/set_setting(), by reference so a write is seen by the next read. */
function &settings_cache(): array
{
    static $cache = [];
    return $cache;
}

function json_column(?string $raw, array $default = []): array
{
    if ($raw === null || $raw === '') {
        return $default;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $default;
}

/**
 * First-run content for a brand-new database: the four artist pages and Lady
 * Gaga's eras, carried over from the hand-written page this app replaces
 * (public/lady-gaga/script.js in git history) so nothing that already worked is
 * lost. Everything here is editable in the admin afterwards, and this never runs
 * again — it is called only when the .sqlite file has just been created.
 */
function seed_database(PDO $db): void
{
    require_once __DIR__ . '/seed_data.php';
    seed_artists_and_eras($db);
}
