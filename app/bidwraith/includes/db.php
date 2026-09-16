<?php

function db(): PDO
{
    static $db = null;

    if ($db === null) {
        $dbPath = db_path();
        $isNew = !file_exists($dbPath);

        // The data directory lives outside the deployed tree in production, so it
        // won't exist until the first request after a fresh deploy.
        $dir = dirname($dbPath);
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            http_response_code(500);
            die('Data directory is not writable: ' . $dir);
        }

        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA foreign_keys = ON');

        $schema = file_get_contents(__DIR__ . '/../sql/schema.sql');
        $db->exec($schema);
        run_migrations($db);

        if ($isNew) {
            chmod($dbPath, 0640);
        }
    }

    return $db;
}

/**
 * Adds columns introduced after a table's initial CREATE TABLE, for databases that
 * already existed before that column was added. schema.sql alone can't do this since
 * CREATE TABLE IF NOT EXISTS is a no-op once the table exists.
 */
function run_migrations(PDO $db): void
{
    $columns = [
        'watched_auctions' => ['current_price' => 'REAL', 'shipping_cost' => 'REAL', 'item_country' => 'TEXT', 'price_checked_at' => 'TEXT', 'image_url' => 'TEXT'],
        'users' => ['is_admin' => 'INTEGER NOT NULL DEFAULT 0', 'is_active' => 'INTEGER NOT NULL DEFAULT 1'],
    ];

    foreach ($columns as $table => $cols) {
        $existing = array_column($db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC), 'name');
        foreach ($cols as $name => $type) {
            if (!in_array($name, $existing, true)) {
                $db->exec("ALTER TABLE $table ADD COLUMN $name $type");
            }
        }
    }

    migrate_single_bid_to_steps($db);
    grant_owner_admin($db);
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

/**
 * One-time migration from the original single max_bid/snipe_seconds_before columns
 * on watched_auctions to the bid_steps table (which supports up to 5 scheduled bids
 * per auction). Runs only for databases created before this change — the columns
 * are gone afterwards, so the guard below is false on every later request.
 */
function migrate_single_bid_to_steps(PDO $db): void
{
    $columns = array_column($db->query('PRAGMA table_info(watched_auctions)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (!in_array('max_bid', $columns, true)) {
        return;
    }

    $rows = $db->query('SELECT id, max_bid, snipe_seconds_before, status, result_message, last_checked_at FROM watched_auctions')->fetchAll(PDO::FETCH_ASSOC);
    $insert = $db->prepare('
        INSERT OR IGNORE INTO bid_steps (watched_auction_id, seconds_before, max_bid, status, fired_at, result_message)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    foreach ($rows as $row) {
        $stepStatus = in_array($row['status'], ['bid_placed', 'failed'], true) ? $row['status'] : 'pending';
        $insert->execute([
            $row['id'],
            $row['snipe_seconds_before'],
            $row['max_bid'],
            $stepStatus,
            $stepStatus !== 'pending' ? $row['last_checked_at'] : null,
            $stepStatus !== 'pending' ? $row['result_message'] : null,
        ]);
    }

    $db->exec('ALTER TABLE watched_auctions DROP COLUMN max_bid');
    $db->exec('ALTER TABLE watched_auctions DROP COLUMN snipe_seconds_before');

    // bid_log used to reference watched_auctions directly; it now references the
    // specific bid_steps row that fired. It's internal audit history only (never
    // shown in the UI), so recreating it empty is simpler than remapping old rows.
    $logColumns = array_column($db->query('PRAGMA table_info(bid_log)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (in_array('watched_auction_id', $logColumns, true)) {
        $db->exec('DROP TABLE bid_log');
        $db->exec('
            CREATE TABLE bid_log (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                bid_step_id      INTEGER NOT NULL REFERENCES bid_steps(id) ON DELETE CASCADE,
                attempted_at     TEXT NOT NULL DEFAULT (datetime(\'now\')),
                success          INTEGER NOT NULL,
                response_summary TEXT
            )
        ');
    }
}
