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
        'users' => ['is_admin' => 'INTEGER NOT NULL DEFAULT 0', 'is_active' => 'INTEGER NOT NULL DEFAULT 1', 'currency' => 'TEXT'],
    ];

    foreach ($columns as $table => $cols) {
        $existing = array_column($db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC), 'name');
        foreach ($cols as $name => $type) {
            if (!in_array($name, $existing, true)) {
                $db->exec("ALTER TABLE $table ADD COLUMN $name $type");
            }
        }
    }

    migrate_billing($db);
    migrate_email($db);
    migrate_bid_steps_anyway_mode($db);
    migrate_single_bid_to_steps($db);
    grant_owner_admin($db);
}

/**
 * Adds the billing columns to a users table that predates them.
 *
 * Everyone already in the database when billing arrives is granted free access. They
 * were invited before there was a plan to buy, and locking out someone with bids
 * scheduled for auctions that are about to end would be the worst way to introduce a
 * price. An admin can switch it off per person. It runs once: the columns exist from
 * then on, and a brand-new database is created with them already (see schema.sql),
 * with no one to grandfather.
 *
 * The lookup indexes live here rather than in schema.sql because schema.sql runs
 * before migrations, when an old table doesn't have the columns yet.
 */
function migrate_billing(PDO $db): void
{
    $existing = array_column($db->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC), 'name');

    if (!in_array('free_access', $existing, true)) {
        $columns = [
            'stripe_customer_id' => 'TEXT',
            'stripe_subscription_id' => 'TEXT',
            'subscription_status' => 'TEXT',
            'trial_ends_at' => 'TEXT',
            'current_period_end' => 'TEXT',
            'cancel_at_period_end' => 'INTEGER NOT NULL DEFAULT 0',
            'free_access' => 'INTEGER NOT NULL DEFAULT 0',
            'free_access_note' => 'TEXT',
        ];
        foreach ($columns as $name => $type) {
            if (!in_array($name, $existing, true)) {
                $db->exec("ALTER TABLE users ADD COLUMN $name $type");
            }
        }

        $db->exec("UPDATE users SET free_access = 1, free_access_note = 'Existing user when billing was introduced'");
    }

    $db->exec('CREATE INDEX IF NOT EXISTS idx_users_stripe_customer ON users (stripe_customer_id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_users_stripe_subscription ON users (stripe_subscription_id)');
}

/**
 * Adds the email columns to a users table that predates them. Like billing, it
 * grandfathers everyone already there — as verified — because they were created by
 * hand or before verification existed, and locking them out of their own account to
 * click a link would be absurd. It runs once; new databases get the columns from
 * schema.sql with nobody to grandfather.
 */
function migrate_email(PDO $db): void
{
    $existing = array_column($db->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC), 'name');

    if (!in_array('email_verified_at', $existing, true)) {
        $db->exec('ALTER TABLE users ADD COLUMN email_verified_at TEXT');
        $db->exec("UPDATE users SET email_verified_at = datetime('now')");
    }
    if (!in_array('email_bid_alerts', $existing, true)) {
        $db->exec('ALTER TABLE users ADD COLUMN email_bid_alerts INTEGER NOT NULL DEFAULT 1');
    }
}

/**
 * Rebuilds bid_steps to add bid_mode/increment_type/increment_amount and make
 * max_bid nullable, for the "I want the item anyway" option (see schema.sql).
 * SQLite's ALTER TABLE can add columns but can't relax an existing NOT NULL
 * constraint, so an existing bid_steps table (missing bid_mode) needs a full
 * rebuild rather than the simple ADD COLUMN loop above.
 *
 * bid_log has a foreign key to bid_steps, and by default SQLite's RENAME TABLE
 * rewrites that reference to follow the renamed table — which would leave it
 * pointing at the soon-to-be-dropped bid_steps_old. That rewrite turns out to
 * need both foreign_keys off AND legacy_alter_table on at the same time (tested
 * empirically — foreign_keys alone, or legacy_alter_table alone once
 * foreign_keys has ever been turned on for the connection, isn't enough); with
 * both set, bid_log's reference is left reading plain "bid_steps", which is
 * correct again once the new table of that name exists.
 */
function migrate_bid_steps_anyway_mode(PDO $db): void
{
    $columns = array_column($db->query('PRAGMA table_info(bid_steps)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (in_array('bid_mode', $columns, true)) {
        return;
    }

    $db->exec('PRAGMA foreign_keys = OFF');
    $db->exec('PRAGMA legacy_alter_table = ON');
    $db->exec('ALTER TABLE bid_steps RENAME TO bid_steps_old');
    $db->exec("
        CREATE TABLE bid_steps (
            id                  INTEGER PRIMARY KEY AUTOINCREMENT,
            watched_auction_id  INTEGER NOT NULL REFERENCES watched_auctions(id) ON DELETE CASCADE,
            seconds_before      INTEGER NOT NULL,
            bid_mode            TEXT NOT NULL DEFAULT 'fixed',
            max_bid             REAL,
            increment_type      TEXT,
            increment_amount    REAL,
            status              TEXT NOT NULL DEFAULT 'pending',
            fired_at            TEXT,
            result_message      TEXT,
            created_at          TEXT NOT NULL DEFAULT (datetime('now')),
            UNIQUE (watched_auction_id, seconds_before)
        )
    ");
    $db->exec("
        INSERT INTO bid_steps (id, watched_auction_id, seconds_before, max_bid, status, fired_at, result_message, created_at)
        SELECT id, watched_auction_id, seconds_before, max_bid, status, fired_at, result_message, created_at FROM bid_steps_old
    ");
    $db->exec('DROP TABLE bid_steps_old');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_bid_steps_status ON bid_steps (status)');
    $db->exec('PRAGMA legacy_alter_table = OFF');
    $db->exec('PRAGMA foreign_keys = ON');
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
