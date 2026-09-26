-- The Collection — SQLite schema.
--
-- Applied in full on every request (includes/db.php), so every statement is
-- CREATE ... IF NOT EXISTS. Columns added to a table later go in
-- MIGRATED_COLUMNS in includes/db.php instead, since this can't add them.
--
-- The shape follows Discogs' own: a `releases` row is the record as Discogs
-- knows it (shared by everyone who owns a copy), and an `items` row is one
-- physical copy on Bruno's shelf — or one line on the wantlist. Everything
-- Discogs sends lives in `releases` and is overwritten on every sync;
-- everything typed by hand lives in `items` and is never touched by a sync.
-- That split is what makes "sync whenever I want" safe.

CREATE TABLE IF NOT EXISTS users (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    email           TEXT NOT NULL UNIQUE,
    password_hash   TEXT NOT NULL,
    created_at      TEXT NOT NULL DEFAULT (datetime('now')),
    last_login_at   TEXT
);

-- Small JSON blobs keyed by name: which Discogs fields the drawer shows, when
-- the last sync ran, and so on. Anything that is configuration rather than data.
CREATE TABLE IF NOT EXISTS settings (
    key         TEXT PRIMARY KEY,
    value       TEXT NOT NULL,
    updated_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

-- A release as Discogs describes it. Overwritten wholesale on each sync.
--
-- The columns down to cover_image come from the collection listing itself
-- ("basic information"), which arrives 100 at a time. Everything below
-- basic_synced_at is only on the per-release endpoint, one HTTP call each, so
-- it is filled in progressively and detail_fetched_at records when.
CREATE TABLE IF NOT EXISTS releases (
    discogs_id          INTEGER PRIMARY KEY,
    master_id           INTEGER,
    title               TEXT NOT NULL DEFAULT '',
    artists_text        TEXT NOT NULL DEFAULT '',
    primary_artist      TEXT NOT NULL DEFAULT '',
    artists_json        TEXT,
    year                INTEGER,
    labels_json         TEXT,
    formats_json        TEXT,
    formats_text        TEXT NOT NULL DEFAULT '',
    genres_json         TEXT,
    styles_json         TEXT,
    thumb               TEXT,
    cover_image         TEXT,
    basic_synced_at     TEXT,

    country             TEXT,
    released            TEXT,
    released_formatted  TEXT,
    uri                 TEXT,
    data_quality        TEXT,
    release_notes       TEXT,
    estimated_weight    REAL,
    num_for_sale        INTEGER,
    lowest_price        REAL,
    barcode             TEXT,
    community_json      TEXT,
    images_json         TEXT,
    tracklist_json      TEXT,
    identifiers_json    TEXT,
    companies_json      TEXT,
    extraartists_json   TEXT,
    videos_json         TEXT,
    series_json         TEXT,
    date_changed        TEXT,
    detail_fetched_at   TEXT,

    updated_at          TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_releases_master ON releases (master_id);
CREATE INDEX IF NOT EXISTS idx_releases_artist ON releases (primary_artist);
CREATE INDEX IF NOT EXISTS idx_releases_detail ON releases (detail_fetched_at);

-- The artists that get their own page (Lady Gaga, Anitta, RBD, Beyoncé…).
-- match_names holds the Discogs spellings that belong to this page, as a JSON
-- array, so "RBD" can also catch "Rebelde" without a second page.
CREATE TABLE IF NOT EXISTS artists (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    slug            TEXT NOT NULL UNIQUE,
    name            TEXT NOT NULL,
    match_names     TEXT,
    discogs_artist_id INTEGER,
    tagline         TEXT,
    intro           TEXT,
    accent          TEXT,
    position        INTEGER NOT NULL DEFAULT 0,
    is_published    INTEGER NOT NULL DEFAULT 1,
    created_at      TEXT NOT NULL DEFAULT (datetime('now'))
);

-- One era of an artist's career: The Fame, Born This Way, Mayhem…
CREATE TABLE IF NOT EXISTS eras (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    artist_id   INTEGER NOT NULL REFERENCES artists(id) ON DELETE CASCADE,
    slug        TEXT NOT NULL,
    name        TEXT NOT NULL,
    years       TEXT,
    tagline     TEXT,
    position    INTEGER NOT NULL DEFAULT 0,
    created_at  TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE (artist_id, slug)
);

-- How a release finds its era without anyone assigning it by hand.
--
-- A master id catches every pressing of an album at once — CD, reissue, coloured
-- vinyl, the lot — which is why the hand-built Lady Gaga page was already
-- written this way. Releases with no master (most promos) are matched by their
-- own id. `rank` is also the display order inside the era: album first, then
-- singles, in the order they were added here.
CREATE TABLE IF NOT EXISTS era_rules (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    era_id      INTEGER NOT NULL REFERENCES eras(id) ON DELETE CASCADE,
    kind        TEXT NOT NULL,              -- 'master' | 'release'
    discogs_id  INTEGER NOT NULL,
    rank        INTEGER NOT NULL DEFAULT 0,
    UNIQUE (kind, discogs_id)
);

CREATE INDEX IF NOT EXISTS idx_era_rules_era ON era_rules (era_id);

-- One physical copy on the shelf, one line on the wantlist, or one record still
-- being hunted for.
--
-- Discogs' own key for a copy is instance_id (a release owned twice has two
-- instances), so that is what a collection row is matched on. Wantlist rows have
-- no instance, so they are keyed by source + release_id instead.
--
-- Columns from `media_kind` down are Bruno's, not Discogs': a sync never writes
-- them. Where one is blank, the reader falls back to the Discogs value (see
-- item_field_value() in includes/fields.php), so filling one in is an override
-- rather than a duplicate.
CREATE TABLE IF NOT EXISTS items (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    source              TEXT NOT NULL DEFAULT 'collection',   -- collection | wantlist | searching | for_sale
    instance_id         INTEGER,
    release_id          INTEGER REFERENCES releases(discogs_id) ON DELETE SET NULL,
    folder_id           INTEGER,
    date_added          TEXT,
    rating              INTEGER,
    discogs_fields_json TEXT,                                 -- the collection's own custom fields

    -- Free-text stand-ins for a 'searching' row that isn't on Discogs at all.
    manual_title        TEXT,
    manual_artist       TEXT,

    media_kind          TEXT NOT NULL DEFAULT 'other',        -- vinyl | cd | dvd | bluray | other
    media_kind_locked   INTEGER NOT NULL DEFAULT 0,
    artist_id           INTEGER REFERENCES artists(id) ON DELETE SET NULL,
    era_id              INTEGER REFERENCES eras(id) ON DELETE SET NULL,
    era_locked          INTEGER NOT NULL DEFAULT 0,
    -- Position inside the era, copied from the matching era_rules row at sync
    -- time (album first, then its singles). sort_rank below overrides it when
    -- an order is set by hand.
    era_rank            INTEGER NOT NULL DEFAULT 1000,

    -- The fields Bruno edits at the top of the form.
    barcode             TEXT,
    release_date        TEXT,
    region              TEXT,
    media               TEXT,
    item_type           TEXT,        -- album | single | promo | ep | compilation | other
    vinyl_size          TEXT,        -- 7" | 10" | 12" …
    vinyl_color         TEXT,        -- colour / variant, e.g. "Pink Opaque"
    notes               TEXT,

    -- Chosen from the release's Discogs images in the admin.
    cover_url           TEXT,
    disc_url            TEXT,

    is_visible          INTEGER NOT NULL DEFAULT 1,
    is_featured         INTEGER NOT NULL DEFAULT 0,
    sort_rank           INTEGER NOT NULL DEFAULT 0,

    -- Set when a sync no longer finds this copy on Discogs. The row is kept, not
    -- deleted, because it carries hand-typed notes — the admin lists it as gone
    -- and offers to remove it.
    missing_since       TEXT,

    created_at          TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at          TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_items_instance ON items (instance_id) WHERE instance_id IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS idx_items_wantlist ON items (source, release_id) WHERE source = 'wantlist' AND release_id IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_items_release ON items (release_id);
CREATE INDEX IF NOT EXISTS idx_items_artist ON items (artist_id);
CREATE INDEX IF NOT EXISTS idx_items_era ON items (era_id);
CREATE INDEX IF NOT EXISTS idx_items_source ON items (source);

-- One row per sync, so the admin can say what happened and when, and so a run
-- that dies halfway is visibly unfinished rather than silently lost.
CREATE TABLE IF NOT EXISTS sync_runs (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    trigger_source      TEXT NOT NULL DEFAULT 'admin',        -- admin | cron | cli
    status              TEXT NOT NULL DEFAULT 'running',      -- running | ok | partial | error
    phase               TEXT,                                 -- collection | wantlist | details | done
    started_at          TEXT NOT NULL DEFAULT (datetime('now')),
    finished_at         TEXT,
    collection_seen     INTEGER NOT NULL DEFAULT 0,
    wantlist_seen       INTEGER NOT NULL DEFAULT 0,
    items_added         INTEGER NOT NULL DEFAULT 0,
    items_removed       INTEGER NOT NULL DEFAULT 0,
    details_fetched     INTEGER NOT NULL DEFAULT 0,
    details_pending     INTEGER NOT NULL DEFAULT 0,
    api_calls           INTEGER NOT NULL DEFAULT 0,
    message             TEXT,
    log                 TEXT
);

CREATE INDEX IF NOT EXISTS idx_sync_runs_started ON sync_runs (started_at);

-- Failed sign-ins by address, so the wait after five wrong passwords can't be
-- skipped by dropping the session cookie. A successful sign-in clears its row.
CREATE TABLE IF NOT EXISTS login_failures (
    ip          TEXT PRIMARY KEY,
    fails       INTEGER NOT NULL DEFAULT 0,
    last_fail   INTEGER NOT NULL DEFAULT 0              -- unix time
);
