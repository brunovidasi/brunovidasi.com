-- ebay_bidder database schema (MySQL / MariaDB)
-- Applied automatically at runtime by includes/db.php whenever this file changes
-- (it keeps a hash of it in schema_meta) — no manual migration needed.
--
-- Every table is utf8mb4_bin: emails, tokens and ids compare byte by byte, as they
-- did in SQLite; a query that wants case-insensitive order sorts on LOWER(column).
-- The DATETIME columns filled by UTC_TIMESTAMP() are UTC; end_time is the one
-- exception, written by PHP in the app's timezone (see db_time_local() in helpers.php).

-- What schema.sql was when it last ran, so db.php runs it again only when it changes.
CREATE TABLE IF NOT EXISTS schema_meta (
    name    VARCHAR(64) NOT NULL PRIMARY KEY,
    value   VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- currency is NULL until the user picks one or eBay's account currency is detected
-- when they connect (see ebay_callback.php); user_currency() in helpers.php is what
-- falls back to 'AUD' for display and bidding, so NULL never leaks into the UI.
CREATE TABLE IF NOT EXISTS users (
    id            INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_admin      TINYINT NOT NULL DEFAULT 0,
    is_active     TINYINT NOT NULL DEFAULT 1,
    created_at    DATETIME NOT NULL DEFAULT (UTC_TIMESTAMP()),
    currency      VARCHAR(8),

    -- Billing (see includes/billing.php). Stripe is the source of truth; these are the
    -- local copy of the parts the app needs. subscription_status holds Stripe's own
    -- status word (trialing, active, past_due, canceled, …) and is NULL for someone
    -- who never subscribed. Times are UTC, like created_at. free_access is granted by
    -- an admin and bypasses billing entirely.
    stripe_customer_id     VARCHAR(191),
    stripe_subscription_id VARCHAR(191),
    subscription_status    VARCHAR(32),
    trial_ends_at          DATETIME NULL,
    current_period_end     DATETIME NULL,
    cancel_at_period_end   TINYINT NOT NULL DEFAULT 0,
    free_access            TINYINT NOT NULL DEFAULT 0,
    free_access_note       TEXT,

    -- Email (see includes/Mailer.php). email_verified_at is NULL until the person
    -- clicks the link we sent (admins never need it); email_bid_alerts is their choice
    -- about "your bid was placed / failed" mail. Account, billing and eBay-connection
    -- mail is not optional.
    email_verified_at      DATETIME NULL,
    email_bid_alerts       TINYINT NOT NULL DEFAULT 1,

    KEY idx_users_stripe_customer (stripe_customer_id),
    KEY idx_users_stripe_subscription (stripe_subscription_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- One row per email the app has decided to send. Everything goes through here rather
-- than straight to the mail server so a slow or down mail server can never hold up a
-- bid being fired, and so a failed send is retried instead of lost. dedupe_key makes
-- "tell them once" idempotent: a webhook delivered twice, or a cron pass that sees the
-- same expiring token every minute, queues the message only the first time.
CREATE TABLE IF NOT EXISTS email_outbox (
    id              INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id         INT,
    kind            VARCHAR(32) NOT NULL,
    to_email        VARCHAR(255) NOT NULL,
    subject         TEXT NOT NULL,
    body_text       MEDIUMTEXT NOT NULL,
    body_html       MEDIUMTEXT,
    dedupe_key      VARCHAR(191) UNIQUE,
    attempts        INT NOT NULL DEFAULT 0,
    claimed_at      DATETIME NULL,
    next_attempt_at DATETIME NULL,
    sent_at         DATETIME NULL,
    last_error      TEXT,
    created_at      DATETIME NOT NULL DEFAULT (UTC_TIMESTAMP()),
    KEY idx_email_outbox_pending (sent_at, next_attempt_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- Single-use links sent by email: purpose 'verify' (confirm an address) or 'reset'
-- (choose a new password). Only a SHA-256 of the token is stored, so a copy of the
-- database can't be used to reset anyone's password.
CREATE TABLE IF NOT EXISTS user_tokens (
    id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    purpose     VARCHAR(16) NOT NULL,
    token_hash  VARCHAR(191) NOT NULL UNIQUE,
    expires_at  DATETIME NOT NULL,
    used_at     DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT (UTC_TIMESTAMP()),
    KEY idx_user_tokens_user (user_id, purpose),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- token_expires_at is eBay's HardExpirationTime as eBay writes it (ISO 8601, UTC).
CREATE TABLE IF NOT EXISTS ebay_accounts (
    id               INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL UNIQUE,
    environment      VARCHAR(16) NOT NULL DEFAULT 'sandbox',
    auth_token       TEXT NOT NULL,
    token_expires_at VARCHAR(32),
    ebay_username    VARCHAR(255),
    connected_at     DATETIME NOT NULL DEFAULT (UTC_TIMESTAMP()),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- expires_at is in the app's timezone, as EbayClient writes it.
CREATE TABLE IF NOT EXISTS app_tokens (
    environment  VARCHAR(16) NOT NULL PRIMARY KEY,
    access_token TEXT NOT NULL,
    expires_at   DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE IF NOT EXISTS watched_auctions (
    id                   INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id              INT NOT NULL,
    item_id              VARCHAR(64) NOT NULL,
    title                TEXT,
    end_time             DATETIME NULL,
    status               VARCHAR(32) NOT NULL DEFAULT 'pending',
    last_checked_at      DATETIME NULL,
    result_message       TEXT,
    current_price        DOUBLE,
    shipping_cost        DOUBLE,
    item_country         VARCHAR(8),
    price_checked_at     DATETIME NULL,
    image_url            TEXT,
    created_at           DATETIME NOT NULL DEFAULT (UTC_TIMESTAMP()),
    KEY idx_watched_auctions_status_end (status, end_time),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

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
    id                  INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    watched_auction_id  INT NOT NULL,
    seconds_before      INT NOT NULL,
    bid_mode            VARCHAR(16) NOT NULL DEFAULT 'fixed',
    max_bid             DOUBLE,
    increment_type      VARCHAR(16),
    increment_amount    DOUBLE,
    status              VARCHAR(16) NOT NULL DEFAULT 'pending',
    fired_at            DATETIME NULL,
    result_message      TEXT,
    created_at          DATETIME NOT NULL DEFAULT (UTC_TIMESTAMP()),
    end_user_ip         VARCHAR(64),
    UNIQUE (watched_auction_id, seconds_before),
    KEY idx_bid_steps_status (status),
    FOREIGN KEY (watched_auction_id) REFERENCES watched_auctions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- Audit trail for eBay's Marketplace Account Deletion/Closure notifications
-- (public/ebay_deletion.php). Required by eBay for every production app that
-- stores any data tied to an eBay account, which this one does (the auth token).
-- Keeping this is also how you can show eBay/yourself the requirement was met,
-- not just believed to be met.
CREATE TABLE IF NOT EXISTS ebay_deletion_log (
    id               INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    notification_id  VARCHAR(191) UNIQUE,
    ebay_username    VARCHAR(255),
    matched_user_id  INT,
    action           TEXT NOT NULL,
    received_at      DATETIME NOT NULL DEFAULT (UTC_TIMESTAMP())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE IF NOT EXISTS bid_log (
    id               INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    bid_step_id      INT NOT NULL,
    attempted_at     DATETIME NOT NULL DEFAULT (UTC_TIMESTAMP()),
    success          TINYINT NOT NULL,
    response_summary TEXT,
    FOREIGN KEY (bid_step_id) REFERENCES bid_steps(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
