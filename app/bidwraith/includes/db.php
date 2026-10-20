<?php

function db(): PDO
{
    static $db = null;

    if ($db === null) {
        // A connection that fails throws, so preflight.php can report why.
        $c = db_config();
        $db = new PDO(
            'mysql:host=' . $c['host'] . ';port=' . $c['port'] . ';dbname=' . $c['name'] . ';charset=utf8mb4',
            $c['user'],
            $c['pass'],
            [
                PDO::ATTR_ERRMODE         => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_STATEMENT_CLASS => [DbStatement::class],
                // an UPDATE counts the rows it matched, as SQLite did, not only the ones it
                // changed: flush_outbox()'s claim relies on rowCount() being 1
                (PHP_VERSION_ID >= 80400 ? Pdo\Mysql::ATTR_FOUND_ROWS : PDO::MYSQL_ATTR_FOUND_ROWS) => true,
            ]
        );

        // UTC_TIMESTAMP() is UTC whatever the server's zone; end_time is plain text from
        // PHP's clock either way (DATETIME columns are never converted).
        $db->exec("SET time_zone = '+00:00', sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

        apply_schema($db);
        grant_owner_admin($db);
    }

    return $db;
}

/**
 * Runs sql/schema.sql when it has changed since it last ran (its hash is kept in
 * schema_meta), then adds any MIGRATED_COLUMNS a table is still without.
 */
function apply_schema(PDO $db): void
{
    $file = __DIR__ . '/../sql/schema.sql';
    $hash = md5_file($file);

    try {
        if ($db->query("SELECT value FROM schema_meta WHERE name = 'schema_hash'")->fetchColumn() === $hash) {
            return;
        }
    } catch (PDOException $e) {
        // no schema_meta table yet: a new database
    }

    // one statement at a time: a batch in one exec() reports only the first one's error
    foreach (preg_split('/;\s*$/m', file_get_contents($file)) as $sql) {
        if (trim(preg_replace('/^\s*--.*$/m', '', $sql)) !== '') {
            $db->exec($sql);
        }
    }

    foreach (MIGRATED_COLUMNS as $table => $columns) {
        $stmt = $db->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->execute([$table]);
        $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($columns as $name => $type) {
            if (!in_array($name, $existing, true)) {
                $db->exec("ALTER TABLE $table ADD COLUMN $name $type");
            }
        }
    }

    $db->prepare("
        INSERT INTO schema_meta (name, value) VALUES ('schema_hash', ?)
        ON DUPLICATE KEY UPDATE value = VALUES(value)
    ")->execute([$hash]);
}

/**
 * Columns added after a table was first created. CREATE TABLE IF NOT EXISTS is a
 * no-op once the table exists, so a column added to schema.sql later goes here as
 * well, for the databases that already have the table.
 */
const MIGRATED_COLUMNS = [];

/**
 * Binds true and false as 1 and 0. PDO would send false as '', which a strict
 * MySQL refuses for a number column (SQLite stored whatever it was given).
 */
class DbStatement extends PDOStatement
{
    protected function __construct()
    {
    }

    public function execute(?array $params = null): bool
    {
        if ($params !== null) {
            $params = array_map(fn ($v) => is_bool($v) ? (int) $v : $v, $params);
        }

        return parent::execute($params);
    }
}

/**
 * The app's owner (identified by email) is always an active admin, regardless of
 * how the users table was seeded — reapplied on every request so the owner can't
 * end up locked out of their own admin dashboard. The address comes from config,
 * which lives outside the repo, so a personal email isn't published publicly.
 */
function grant_owner_admin(PDO $db): void
{
    $owner = owner_email();
    if ($owner === null) {
        return;
    }

    $db->prepare('UPDATE users SET is_admin = 1, is_active = 1 WHERE email = ?')->execute([$owner]);
}
