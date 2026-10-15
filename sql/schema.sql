-- Bruno's Game Shelf — SQLite schema.
--
-- Applied in full on every request (includes/db.php), so every statement is
-- CREATE ... IF NOT EXISTS. Columns added to a table later go in
-- MIGRATED_COLUMNS in includes/db.php instead, since this can't add them.
--
-- A `games` row is one copy on the shelf (or in a launcher's library), with
-- everything the site draws it with. What a platform says about it — play
-- time, achievements, trophies — lives apart in `platform_records`, keyed by
-- the platform's own id, and is rewritten by every sync. A sync only ever adds
-- a copy, ties one to a platform id, or fills a field that is still empty (or
-- one it filled itself and you left alone: see `field_state`); nothing you
-- typed is ever overwritten, and nothing is ever deleted.

CREATE TABLE IF NOT EXISTS users (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    email           TEXT NOT NULL UNIQUE,
    password_hash   TEXT NOT NULL,
    created_at      TEXT NOT NULL DEFAULT (datetime('now')),
    last_login_at   TEXT
);

-- Small JSON blobs keyed by name: the site's titles and texts, the views, the
-- friend links, the cron token. Anything that is configuration rather than data.
CREATE TABLE IF NOT EXISTS settings (
    key         TEXT PRIMARY KEY,
    value       TEXT NOT NULL,
    updated_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

-- One copy. The id is the one the site has always used ("ps5-avatar-frontiers-of-pandora"),
-- so links to #ps5-avatar-frontiers-of-pandora keep working.
--
-- The columns are the fields the admin searches, filters and sorts on, plus the
-- platform ids the syncs match on. Every other field the shelf draws a copy
-- with (its spine, case colour, cartridge, box size…) is in extra_json. Which
-- field lives where is one list, FIELDS in includes/fields.php.
CREATE TABLE IF NOT EXISTS games (
    id                  TEXT PRIMARY KEY,
    -- where it stands in the list the site is handed: copies on a console in the
    -- order they were added, a new one after the last on its console
    position            INTEGER NOT NULL DEFAULT 0,

    console             TEXT NOT NULL,
    title               TEXT NOT NULL,
    listed_as           TEXT,
    sort_as             TEXT,
    edition             TEXT,
    region              TEXT,
    released            TEXT,
    date_source         TEXT,
    publisher           TEXT,
    developer           TEXT,
    genre               TEXT,
    format              TEXT,
    launcher            TEXT,
    series              TEXT,
    pack                TEXT,
    pack_code           TEXT,
    note                TEXT,
    check_note          TEXT,

    fav                 INTEGER,
    hidden              INTEGER,
    also_digital        INTEGER,
    mac                 INTEGER,
    platinum            INTEGER,

    -- the platform ids: a plain value, or a JSON list for a copy with more than
    -- one game in it (a trilogy has each game's play history)
    steam_app_id        INTEGER,
    psn_title_id        TEXT,
    psn_trophy_set      TEXT,
    nintendo_title_id   TEXT,
    xbox_title_id       TEXT,
    ea_slug             TEXT,
    rockstar_title      TEXT,

    contains_json       TEXT,       -- a bundle's packs, by id
    cover_json          TEXT,       -- {file, ratio, source, ref, label, labelRef, back, …}
    colors_json         TEXT,       -- the three colours the spine is drawn in
    extra_json          TEXT,

    created_at          TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at          TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_games_console ON games (console, position);
CREATE INDEX IF NOT EXISTS idx_games_steam ON games (steam_app_id);
CREATE INDEX IF NOT EXISTS idx_games_ea ON games (ea_slug);

-- Which of a copy's fields a sync wrote, and whether you have taken it over.
--
-- A field a sync filled in (a Steam copy's publisher, its cover) is the sync's
-- until you change it: the next sync may bring a newer value. Changing it in
-- the admin locks it (locked = 1), and from then on a sync only notes what it
-- would have written (synced_value), shown beside yours on the edit page.
CREATE TABLE IF NOT EXISTS field_state (
    game_id         TEXT NOT NULL REFERENCES games(id) ON DELETE CASCADE ON UPDATE CASCADE,
    field           TEXT NOT NULL,              -- the field's name as the site knows it: "publisher", "cover"
    source          TEXT NOT NULL,              -- steam | psn | nintendo | xbox | ea | rockstar
    synced_value    TEXT,                       -- JSON
    synced_at       TEXT NOT NULL DEFAULT (datetime('now')),
    locked          INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (game_id, field)
);

-- What a platform says about one of its games, by its own id, as the site shows
-- it (the shape data/steam.json, psn.json… had). `collection` is which list it
-- is in: Steam, EA and Rockstar have "games", PSN "titles" and "trophySets",
-- Nintendo and Xbox "titles".
CREATE TABLE IF NOT EXISTS platform_records (
    platform        TEXT NOT NULL,
    collection      TEXT NOT NULL,
    ext_id          TEXT NOT NULL,
    position        INTEGER NOT NULL DEFAULT 0,
    data_json       TEXT NOT NULL,
    first_seen_at   TEXT NOT NULL DEFAULT (datetime('now')),
    synced_at       TEXT NOT NULL DEFAULT (datetime('now')),
    run_id          INTEGER,
    -- set when a sync no longer finds it; the record stays, as the copy tied to it does
    missing_since   TEXT,
    PRIMARY KEY (platform, collection, ext_id)
);

-- Every earlier version of a record a sync replaced, so a sync that brought in
-- rubbish (the APIs are unofficial) can be undone: see revert_sync_run().
CREATE TABLE IF NOT EXISTS platform_record_history (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    run_id          INTEGER NOT NULL,
    platform        TEXT NOT NULL,
    collection      TEXT NOT NULL,
    ext_id          TEXT NOT NULL,
    data_json       TEXT,                       -- NULL: the run added it
    missing_since   TEXT,
    replaced_at     TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_record_history_run ON platform_record_history (run_id);
CREATE INDEX IF NOT EXISTS idx_record_history_record ON platform_record_history (platform, collection, ext_id);

-- The account-wide part of each platform's data: the PSN trophy level, the Xbox
-- gamerscore, Steam's totals, when it was last synced.
CREATE TABLE IF NOT EXISTS platform_accounts (
    platform        TEXT PRIMARY KEY,
    data_json       TEXT NOT NULL,
    synced_at       TEXT NOT NULL DEFAULT (datetime('now'))
);

-- A synced value you corrected by hand: hours played, trophies earned… `path` is
-- where in the record it is ("playtime.total"). With mode 'keep' it stays yours
-- whatever a sync says; with 'until_sync' it holds until a sync brings a value
-- other than base_json (what the platform said when you changed it).
CREATE TABLE IF NOT EXISTS platform_overrides (
    platform        TEXT NOT NULL,
    collection      TEXT NOT NULL,
    ext_id          TEXT NOT NULL,
    path            TEXT NOT NULL,
    value_json      TEXT,
    mode            TEXT NOT NULL DEFAULT 'keep',     -- keep | until_sync
    base_json       TEXT,
    updated_at      TEXT NOT NULL DEFAULT (datetime('now')),
    PRIMARY KEY (platform, collection, ext_id, path)
);

-- The keys and sign-ins each platform needs, encrypted (includes/secrets.php).
CREATE TABLE IF NOT EXISTS credentials (
    platform        TEXT NOT NULL,
    name            TEXT NOT NULL,
    value_enc       TEXT NOT NULL,
    updated_at      TEXT NOT NULL DEFAULT (datetime('now')),
    PRIMARY KEY (platform, name)
);

-- How each connection last fared, for the dashboard and Settings → Connections.
CREATE TABLE IF NOT EXISTS connections (
    platform        TEXT PRIMARY KEY,
    status          TEXT NOT NULL DEFAULT 'unset',    -- unset | ok | error
    message         TEXT,
    account         TEXT,                             -- the name signed in as, when the platform says
    checked_at      TEXT,
    connected_at    TEXT
);

-- One row per sync of one platform, so the admin can say what happened and when,
-- and so a run that dies halfway is visibly unfinished rather than silently lost.
CREATE TABLE IF NOT EXISTS sync_runs (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    platform            TEXT NOT NULL,
    trigger_source      TEXT NOT NULL DEFAULT 'admin',        -- admin | cron | cli
    status              TEXT NOT NULL DEFAULT 'running',      -- running | ok | error | reverted
    started_at          TEXT NOT NULL DEFAULT (datetime('now')),
    finished_at         TEXT,
    records_seen        INTEGER NOT NULL DEFAULT 0,
    games_added         INTEGER NOT NULL DEFAULT 0,
    games_linked        INTEGER NOT NULL DEFAULT 0,
    games_changed       INTEGER NOT NULL DEFAULT 0,
    records_changed     INTEGER NOT NULL DEFAULT 0,
    unmatched           INTEGER NOT NULL DEFAULT 0,
    api_calls           INTEGER NOT NULL DEFAULT 0,
    message             TEXT,
    log                 TEXT,
    reverted_at         TEXT
);

CREATE INDEX IF NOT EXISTS idx_sync_runs_started ON sync_runs (started_at);
CREATE INDEX IF NOT EXISTS idx_sync_runs_platform ON sync_runs (platform, started_at);

-- Everything one run changed, field by field, with the value before and after
-- (JSON), so Sync History can show it and a run can be reverted.
--
-- kind: added (a new copy) | linked (a copy tied to a platform id) | filled (a
-- copy's field) | kept (a field you locked, left as it was) | record (a platform
-- record's value) | new_record | ambiguous | unmatched | missing
CREATE TABLE IF NOT EXISTS sync_changes (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    run_id          INTEGER NOT NULL REFERENCES sync_runs(id) ON DELETE CASCADE,
    kind            TEXT NOT NULL,
    game_id         TEXT,
    collection      TEXT,
    ext_id          TEXT,
    label           TEXT,                       -- the game's name, for the history page
    field           TEXT,
    old_json        TEXT,
    new_json        TEXT,
    note            TEXT
);

CREATE INDEX IF NOT EXISTS idx_sync_changes_run ON sync_changes (run_id);
CREATE INDEX IF NOT EXISTS idx_sync_changes_game ON sync_changes (game_id);

-- What each platform answered, gzipped, for the last few runs of each.
CREATE TABLE IF NOT EXISTS sync_snapshots (
    run_id          INTEGER PRIMARY KEY REFERENCES sync_runs(id) ON DELETE CASCADE,
    platform        TEXT NOT NULL,
    raw_gz          BLOB NOT NULL,
    bytes           INTEGER NOT NULL DEFAULT 0,
    created_at      TEXT NOT NULL DEFAULT (datetime('now'))
);

-- A platform's game a sync couldn't tie to one copy: none on the shelf, or more
-- than one it could be. The dashboard lists them to tie by hand, or to dismiss.
CREATE TABLE IF NOT EXISTS sync_unmatched (
    platform        TEXT NOT NULL,
    collection      TEXT NOT NULL,
    ext_id          TEXT NOT NULL,
    name            TEXT,
    reason          TEXT NOT NULL,              -- ambiguous | unmatched
    candidates_json TEXT,
    first_seen_at   TEXT NOT NULL DEFAULT (datetime('now')),
    last_seen_at    TEXT NOT NULL DEFAULT (datetime('now')),
    dismissed       INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (platform, collection, ext_id)
);

-- What is not on the shelf yet. Only the Sims want list today (collection
-- 'sims'); the site's own wantlist will be rows here too.
CREATE TABLE IF NOT EXISTS wantlist (
    id              TEXT PRIMARY KEY,
    collection      TEXT NOT NULL DEFAULT 'sims',
    position        INTEGER NOT NULL DEFAULT 0,
    series          TEXT,
    title           TEXT NOT NULL,
    pack            TEXT,
    pack_code       TEXT,
    released        TEXT,
    platform        TEXT,
    note            TEXT,
    extra_json      TEXT,
    created_at      TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at      TEXT NOT NULL DEFAULT (datetime('now'))
);

-- The GTA checklist: the series era by era, each game with the copies on the
-- shelf (game ids) and the platforms still to find.
CREATE TABLE IF NOT EXISTS gta_eras (
    id              TEXT PRIMARY KEY,
    position        INTEGER NOT NULL DEFAULT 0,
    name            TEXT NOT NULL,
    blurb           TEXT,
    extra_json      TEXT
);

CREATE TABLE IF NOT EXISTS gta_games (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    era_id          TEXT NOT NULL REFERENCES gta_eras(id) ON DELETE CASCADE ON UPDATE CASCADE,
    position        INTEGER NOT NULL DEFAULT 0,
    title           TEXT NOT NULL,
    released        TEXT,
    copies_json     TEXT,
    missing_json    TEXT,
    extra_json      TEXT
);

-- Failed sign-ins by address, so the wait after five wrong passwords can't be
-- skipped by dropping the session cookie. A successful sign-in clears its row.
CREATE TABLE IF NOT EXISTS login_failures (
    ip          TEXT PRIMARY KEY,
    fails       INTEGER NOT NULL DEFAULT 0,
    last_fail   INTEGER NOT NULL DEFAULT 0              -- unix time
);

-- The consoles themselves: one row per machine, with what is on its label. Only
-- the admin reads it for now; nothing here is in what the site is handed. The
-- serial numbers stay out of the repo too (the repo is public), so the rows
-- live only in the database: see cron/import_hardware.php.
CREATE TABLE IF NOT EXISTS hardware (
    id              TEXT PRIMARY KEY,           -- "switch-oled", "wii-white"
    position        INTEGER NOT NULL DEFAULT 0,
    console         TEXT NOT NULL,              -- the shelf it goes with: "switch", "ps2"
    name            TEXT NOT NULL,              -- "Nintendo Switch OLED"
    model           TEXT,                       -- "HEG-001", "CECH-3002B"
    edition         TEXT,                       -- "Pokémon: Let's Go Edition"
    color           TEXT,
    region          TEXT,                       -- a region code, as a copy's: AUS, EUR, USA…
    video           TEXT,                       -- PAL | NTSC
    region_free     INTEGER,
    year            TEXT,                       -- when that model came out
    storage         TEXT,                       -- "32 GB"
    serial          TEXT,
    manufactured    TEXT,                       -- the date on the label
    label_text      TEXT,                       -- everything the label says, as written
    extra_json      TEXT,                       -- other markings: product id, FCC id, the Wii U GamePad…
    note            TEXT,
    -- (released, manufacturer, specs_json and photo_json are added by MIGRATED_COLUMNS)
    created_at      TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at      TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_hardware_console ON hardware (console, position);
