-- ebay_bidder database schema (SQLite)
-- Applied automatically at runtime by includes/db.php — no manual migration needed.

CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    email         TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    is_admin      INTEGER NOT NULL DEFAULT 0,
    is_active     INTEGER NOT NULL DEFAULT 1,
    created_at    TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS ebay_accounts (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id          INTEGER NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    environment      TEXT NOT NULL DEFAULT 'sandbox',
    auth_token       TEXT NOT NULL,
    token_expires_at TEXT,
    ebay_username    TEXT,
    connected_at     TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS app_tokens (
    environment  TEXT PRIMARY KEY,
    access_token TEXT NOT NULL,
    expires_at   TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS watched_auctions (
    id                   INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id              INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    item_id              TEXT NOT NULL,
    title                TEXT,
    end_time             TEXT,
    status               TEXT NOT NULL DEFAULT 'pending',
    last_checked_at      TEXT,
    result_message       TEXT,
    current_price        REAL,
    shipping_cost        REAL,
    item_country         TEXT,
    price_checked_at     TEXT,
    image_url            TEXT,
    created_at           TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_watched_auctions_status_end
    ON watched_auctions (status, end_time);

-- A scheduled bid at a given number of seconds before the auction ends. An auction
-- can have up to 5 of these, e.g. $50 at 10s before, $60 at 3s before, $70 at 1s
-- before — a "ladder" that raises the ceiling as the close gets nearer, to compete
-- with other snipers rather than relying on a single fixed max.
CREATE TABLE IF NOT EXISTS bid_steps (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    watched_auction_id  INTEGER NOT NULL REFERENCES watched_auctions(id) ON DELETE CASCADE,
    seconds_before      INTEGER NOT NULL,
    max_bid             REAL NOT NULL,
    status              TEXT NOT NULL DEFAULT 'pending',
    fired_at            TEXT,
    result_message      TEXT,
    created_at          TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE (watched_auction_id, seconds_before)
);

CREATE INDEX IF NOT EXISTS idx_bid_steps_status ON bid_steps (status);

CREATE TABLE IF NOT EXISTS bid_log (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    bid_step_id      INTEGER NOT NULL REFERENCES bid_steps(id) ON DELETE CASCADE,
    attempted_at     TEXT NOT NULL DEFAULT (datetime('now')),
    success          INTEGER NOT NULL,
    response_summary TEXT
);
