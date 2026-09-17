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
--
-- bid_mode 'fixed' bids max_bid exactly, decided ahead of time (the default, used
-- by Steps and Scheduled Bid rows). bid_mode 'anyway' ("I want the item anyway")
-- instead computes its amount at fire time as the item's live price plus
-- increment_amount (a flat value or a percentage, per increment_type) — max_bid is
-- then an optional cap, NULL meaning no limit, until the step fires and it's
-- overwritten with whatever was actually bid.
CREATE TABLE IF NOT EXISTS bid_steps (
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
);

CREATE INDEX IF NOT EXISTS idx_bid_steps_status ON bid_steps (status);

-- Audit trail for eBay's Marketplace Account Deletion/Closure notifications
-- (public/ebay_deletion.php). Required by eBay for every production app that
-- stores any data tied to an eBay account, which this one does (the auth token).
-- Keeping this is also how you can show eBay/yourself the requirement was met,
-- not just believed to be met.
CREATE TABLE IF NOT EXISTS ebay_deletion_log (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    notification_id  TEXT UNIQUE,
    ebay_username     TEXT,
    matched_user_id   INTEGER,
    action            TEXT NOT NULL,
    received_at       TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS bid_log (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    bid_step_id      INTEGER NOT NULL REFERENCES bid_steps(id) ON DELETE CASCADE,
    attempted_at     TEXT NOT NULL DEFAULT (datetime('now')),
    success          INTEGER NOT NULL,
    response_summary TEXT
);
