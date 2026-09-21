<?php

/**
 * Pulling Discogs into the database.
 *
 * A sync is deliberately restartable and deliberately slow to finish. The
 * collection listing arrives 100 releases at a time and is cheap, but the
 * interesting half — tracklist, credits, images, identifiers, the barcode —
 * only exists on the per-release endpoint, one HTTP call each, against a limit
 * of 60 calls a minute. A 400-record shelf is therefore a seven-minute job, and
 * no web request should sit there for seven minutes.
 *
 * So a run is a row in sync_runs that moves through phases, and sync_step()
 * does one slice of work within a time budget and returns. The admin's Sync
 * button polls it (so the page shows progress and nothing times out); the cron
 * URL loops it until done. Either way the work already done is committed, and a
 * run that dies halfway resumes on the next pass instead of starting over.
 *
 * What a sync never touches: any column on `items` that Bruno edits. Discogs
 * data lives on `releases` and is overwritten freely; his notes, barcodes,
 * chosen covers and era assignments are on `items` and are only ever read here.
 */

const SYNC_PER_PAGE = 100;

/** How long a release's full detail is trusted before it is fetched again. */
function detail_max_age_days(): int
{
    return max(1, (int) setting('detail_max_age_days', 45));
}

function sync_client(): DiscogsClient
{
    static $client = null;
    return $client ??= new DiscogsClient();
}

/* ---------- Runs ---------- */

function sync_start(string $trigger = 'admin'): int
{
    // Only one run at a time. A run left 'running' by a crashed request would
    // otherwise block every later sync forever, so anything older than an hour
    // is called abandoned and closed.
    db()->exec("
        UPDATE sync_runs
           SET status = 'error', finished_at = datetime('now'),
               message = COALESCE(message, 'Abandoned — the run stopped without finishing.')
         WHERE status = 'running' AND started_at < datetime('now', '-1 hour')
    ");

    $running = db()->query("SELECT id FROM sync_runs WHERE status = 'running' ORDER BY id DESC LIMIT 1")->fetchColumn();
    if ($running) {
        return (int) $running;
    }

    db()->prepare("INSERT INTO sync_runs (trigger_source, status, phase) VALUES (?, 'running', 'collection')")
        ->execute([$trigger]);

    return (int) db()->lastInsertId();
}

function sync_run(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM sync_runs WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function latest_sync_run(): ?array
{
    return db()->query('SELECT * FROM sync_runs ORDER BY id DESC LIMIT 1')->fetch() ?: null;
}

function sync_log(int $runId, string $message): void
{
    db()->prepare("UPDATE sync_runs SET log = COALESCE(log, '') || ? WHERE id = ?")
        ->execute(['[' . date('H:i:s') . '] ' . $message . "\n", $runId]);
}

function sync_finish(int $runId, string $status, ?string $message = null): void
{
    db()->prepare("UPDATE sync_runs SET status = ?, message = ?, finished_at = datetime('now') WHERE id = ?")
        ->execute([$status, $message, $runId]);

    if ($status === 'ok') {
        set_setting('last_successful_sync', date('c'));
    }
}

/**
 * Does the next slice of a run and returns where it got to.
 *
 * @param float $budget seconds of work to do before handing control back.
 * @return array{done:bool,phase:string,status:string,message:string,progress:array}
 */
function sync_step(int $runId, float $budget = 15.0): array
{
    $run = sync_run($runId);
    if ($run === null) {
        throw new RuntimeException('No sync run with id ' . $runId);
    }
    if ($run['status'] !== 'running') {
        return sync_progress($run, true);
    }

    $client = sync_client();
    $deadline = microtime(true) + $budget;

    try {
        if ($run['phase'] === 'collection') {
            $counts = sync_collection($runId, $client);
            sync_log($runId, "Collection: {$counts['seen']} copies ({$counts['added']} new, {$counts['gone']} no longer there).");
            db()->prepare("UPDATE sync_runs SET phase = 'wantlist' WHERE id = ?")->execute([$runId]);
        } elseif ($run['phase'] === 'wantlist') {
            $counts = sync_wantlist($runId, $client);
            sync_log($runId, "Wantlist: {$counts['seen']} records ({$counts['added']} new, {$counts['gone']} removed).");
            assign_items_to_artists_and_eras();
            sync_log($runId, 'Filed everything into artists and eras.');
            db()->prepare("UPDATE sync_runs SET phase = 'details' WHERE id = ?")->execute([$runId]);
        } elseif ($run['phase'] === 'details') {
            $fetched = sync_details($runId, $client, $deadline);
            $pending = count_pending_details();
            db()->prepare('UPDATE sync_runs SET details_pending = ? WHERE id = ?')->execute([$pending, $runId]);

            if ($pending === 0) {
                assign_items_to_artists_and_eras();
                sync_log($runId, 'Release details complete.');
                db()->prepare("UPDATE sync_runs SET phase = 'done' WHERE id = ?")->execute([$runId]);
                sync_finish($runId, 'ok', 'Sync complete.');
            } elseif ($fetched === 0) {
                // Budget spent without a single call finishing: let the caller
                // come back rather than spin.
                sync_log($runId, "Paused with $pending release(s) still to fetch.");
            }
        }
    } catch (DiscogsException $e) {
        sync_log($runId, 'Stopped: ' . $e->getMessage());
        // A rate limit or a dropped connection is not a reason to throw away
        // everything already written — the next pass picks up where this left off.
        $status = $run['phase'] === 'details' ? 'partial' : 'error';
        sync_finish($runId, $status, $e->getMessage());
    }

    record_api_calls($runId, $client);

    $run = sync_run($runId);
    return sync_progress($run, $run['status'] !== 'running');
}

/**
 * Adds the calls made since the last time this was asked to the run's total.
 * The client counts for the whole request, and sync_step() can be called many
 * times in one (the cron loop), so only the difference is new.
 */
function record_api_calls(int $runId, DiscogsClient $client): void
{
    static $counted = 0;

    $new = $client->callsMade() - $counted;
    $counted = $client->callsMade();

    if ($new > 0) {
        db()->prepare('UPDATE sync_runs SET api_calls = api_calls + ? WHERE id = ?')->execute([$new, $runId]);
    }
}

function sync_progress(array $run, bool $done): array
{
    $phaseLabels = [
        'collection' => 'Reading the collection…',
        'wantlist'   => 'Reading the wantlist…',
        'details'    => 'Fetching release details…',
        'done'       => 'Done.',
    ];

    return [
        'run_id'   => (int) $run['id'],
        'done'     => $done,
        'phase'    => (string) $run['phase'],
        'status'   => (string) $run['status'],
        'message'  => (string) ($run['message'] ?? $phaseLabels[$run['phase']] ?? ''),
        'progress' => [
            'collection'  => (int) $run['collection_seen'],
            'wantlist'    => (int) $run['wantlist_seen'],
            'added'       => (int) $run['items_added'],
            'removed'     => (int) $run['items_removed'],
            'details'     => (int) $run['details_fetched'],
            'pending'     => (int) $run['details_pending'],
            'api_calls'   => (int) $run['api_calls'],
        ],
        'log' => (string) ($run['log'] ?? ''),
    ];
}

/**
 * Runs a whole sync to completion (or until the budget runs out). Used by the
 * cron endpoints, where there is nobody watching a progress bar.
 */
function run_full_sync(string $trigger, callable $log, float $budget = 600.0): array
{
    $runId = sync_start($trigger);
    $deadline = microtime(true) + $budget;

    do {
        $state = sync_step($runId, min(30.0, $deadline - microtime(true)));
        $log(sprintf(
            '%s — %d in collection, %d wanted, %d details fetched, %d pending',
            $state['phase'],
            $state['progress']['collection'],
            $state['progress']['wantlist'],
            $state['progress']['details'],
            $state['progress']['pending']
        ));
    } while (!$state['done'] && microtime(true) < $deadline);

    if (!$state['done']) {
        // Out of time, not out of work. The run stays 'running' so the next
        // cron pass continues it instead of starting from the first page again.
        $log('Budget spent; the next pass will continue this run.');
    }

    return $state;
}

/* ---------- Phase 1: the collection ---------- */

function sync_collection(int $runId, DiscogsClient $client): array
{
    $username = discogs_username();
    if ($username === '') {
        throw new DiscogsException('No Discogs username is set in the config.');
    }

    $seen = [];
    $added = 0;
    $page = 1;
    $pages = 1;

    do {
        $data = $client->collectionPage($username, $page, SYNC_PER_PAGE);
        $pages = (int) ($data['pagination']['pages'] ?? 1);

        db()->beginTransaction();
        foreach (($data['releases'] ?? []) as $entry) {
            $basic = $entry['basic_information'] ?? null;
            if (!$basic || empty($entry['instance_id'])) {
                continue;
            }

            upsert_release_basic($basic);
            $added += upsert_collection_item($entry, $basic);
            $seen[] = (int) $entry['instance_id'];
        }
        db()->commit();

        db()->prepare('UPDATE sync_runs SET collection_seen = ? WHERE id = ?')->execute([count($seen), $runId]);
        $page++;
    } while ($page <= $pages);

    $gone = mark_missing('collection', $seen, 'instance_id');

    db()->prepare('UPDATE sync_runs SET items_added = items_added + ?, items_removed = items_removed + ? WHERE id = ?')
        ->execute([$added, $gone, $runId]);

    return ['seen' => count($seen), 'added' => $added, 'gone' => $gone];
}

/**
 * Writes the release as the collection listing describes it. This is the cheap
 * half of a release: enough for the shelf, the grid and the artist pages, but
 * not the tracklist or the barcode.
 *
 * Deliberately does NOT clear the detail columns — a re-sync of the basics must
 * not throw away detail already paid for in API calls.
 */
function upsert_release_basic(array $basic): void
{
    $artists = $basic['artists'] ?? [];
    $formats = $basic['formats'] ?? [];

    db()->prepare("
        INSERT INTO releases (
            discogs_id, master_id, title, artists_text, primary_artist, artists_json, year,
            labels_json, formats_json, formats_text, genres_json, styles_json, thumb, cover_image,
            basic_synced_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
        ON CONFLICT(discogs_id) DO UPDATE SET
            master_id       = excluded.master_id,
            title           = excluded.title,
            artists_text    = excluded.artists_text,
            primary_artist  = excluded.primary_artist,
            artists_json    = excluded.artists_json,
            year            = excluded.year,
            labels_json     = excluded.labels_json,
            formats_json    = excluded.formats_json,
            formats_text    = excluded.formats_text,
            genres_json     = excluded.genres_json,
            styles_json     = excluded.styles_json,
            thumb           = excluded.thumb,
            cover_image     = excluded.cover_image,
            basic_synced_at = excluded.basic_synced_at,
            updated_at      = excluded.updated_at
    ")->execute([
        (int) $basic['id'],
        !empty($basic['master_id']) ? (int) $basic['master_id'] : null,
        (string) ($basic['title'] ?? ''),
        artists_text($artists),
        clean_artist_name((string) ($artists[0]['name'] ?? '')),
        json_encode($artists, JSON_UNESCAPED_UNICODE),
        !empty($basic['year']) ? (int) $basic['year'] : null,
        json_encode($basic['labels'] ?? [], JSON_UNESCAPED_UNICODE),
        json_encode($formats, JSON_UNESCAPED_UNICODE),
        formats_text($formats),
        json_encode($basic['genres'] ?? [], JSON_UNESCAPED_UNICODE),
        json_encode($basic['styles'] ?? [], JSON_UNESCAPED_UNICODE),
        (string) ($basic['thumb'] ?? ''),
        (string) ($basic['cover_image'] ?? ''),
    ]);
}

/** @return int 1 if this copy is new to the database, 0 if it was already here. */
function upsert_collection_item(array $entry, array $basic): int
{
    $instanceId = (int) $entry['instance_id'];

    $exists = db()->prepare('SELECT 1 FROM items WHERE instance_id = ?');
    $exists->execute([$instanceId]);
    $isNew = !$exists->fetchColumn();

    db()->prepare("
        INSERT INTO items (source, instance_id, release_id, folder_id, date_added, rating, discogs_fields_json, media_kind)
        VALUES ('collection', ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT(instance_id) WHERE instance_id IS NOT NULL DO UPDATE SET
            source              = 'collection',
            release_id          = excluded.release_id,
            folder_id           = excluded.folder_id,
            date_added          = excluded.date_added,
            rating              = excluded.rating,
            discogs_fields_json = excluded.discogs_fields_json,
            -- A kind set by hand in the admin is never overwritten by a sync.
            media_kind          = CASE WHEN items.media_kind_locked = 1 THEN items.media_kind ELSE excluded.media_kind END,
            missing_since       = NULL,
            updated_at          = datetime('now')
    ")->execute([
        $instanceId,
        (int) $basic['id'],
        isset($entry['folder_id']) ? (int) $entry['folder_id'] : null,
        (string) ($entry['date_added'] ?? ''),
        !empty($entry['rating']) ? (int) $entry['rating'] : null,
        json_encode($entry['notes'] ?? [], JSON_UNESCAPED_UNICODE),
        detect_media_kind($basic['formats'] ?? []),
    ]);

    return $isNew ? 1 : 0;
}

/* ---------- Phase 2: the wantlist ---------- */

function sync_wantlist(int $runId, DiscogsClient $client): array
{
    $username = discogs_username();
    $seen = [];
    $added = 0;
    $page = 1;
    $pages = 1;

    do {
        $data = $client->wantlistPage($username, $page, SYNC_PER_PAGE);
        $pages = (int) ($data['pagination']['pages'] ?? 1);

        db()->beginTransaction();
        foreach (($data['wants'] ?? []) as $want) {
            $basic = $want['basic_information'] ?? null;
            if (!$basic || empty($basic['id'])) {
                continue;
            }

            upsert_release_basic($basic);
            $added += upsert_wantlist_item($want, $basic);
            $seen[] = (int) $basic['id'];
        }
        db()->commit();

        db()->prepare('UPDATE sync_runs SET wantlist_seen = ? WHERE id = ?')->execute([count($seen), $runId]);
        $page++;
    } while ($page <= $pages);

    $gone = mark_missing('wantlist', $seen, 'release_id');

    db()->prepare('UPDATE sync_runs SET items_added = items_added + ?, items_removed = items_removed + ? WHERE id = ?')
        ->execute([$added, $gone, $runId]);

    return ['seen' => count($seen), 'added' => $added, 'gone' => $gone];
}

function upsert_wantlist_item(array $want, array $basic): int
{
    $releaseId = (int) $basic['id'];

    $exists = db()->prepare("SELECT 1 FROM items WHERE source = 'wantlist' AND release_id = ?");
    $exists->execute([$releaseId]);
    $isNew = !$exists->fetchColumn();

    db()->prepare("
        INSERT INTO items (source, release_id, date_added, rating, notes, media_kind)
        VALUES ('wantlist', ?, ?, ?, NULL, ?)
        ON CONFLICT(source, release_id) WHERE source = 'wantlist' AND release_id IS NOT NULL DO UPDATE SET
            date_added    = excluded.date_added,
            rating        = excluded.rating,
            media_kind    = CASE WHEN items.media_kind_locked = 1 THEN items.media_kind ELSE excluded.media_kind END,
            missing_since = NULL,
            updated_at    = datetime('now')
    ")->execute([
        $releaseId,
        (string) ($want['date_added'] ?? ''),
        !empty($want['rating']) ? (int) $want['rating'] : null,
        detect_media_kind($basic['formats'] ?? []),
    ]);

    return $isNew ? 1 : 0;
}

/**
 * Flags the rows Discogs no longer lists.
 *
 * They are flagged, not deleted: an item row carries hand-typed notes, a chosen
 * cover and an era, and a copy can vanish from a listing because it was moved
 * between folders mid-sync as easily as because it was sold. The admin lists
 * them under "No longer on Discogs" with a Delete button, and any sync that
 * finds one again clears the flag.
 *
 * Rows added by hand ('searching') are never touched — they were never on
 * Discogs to begin with.
 */
function mark_missing(string $source, array $seenIds, string $column): int
{
    if (!$seenIds) {
        // An empty listing is far more likely to be a half-answered API call
        // than an emptied shelf, so nothing is flagged on that basis.
        return 0;
    }

    // The ids go into a temporary table rather than a NOT IN (?, ?, ?…) list:
    // SQLite caps the number of bound variables in one statement (999 on
    // builds before 3.32), and a collection of a few hundred copies is already
    // within sight of that. A temp table has no such limit and is dropped with
    // the connection.
    db()->exec('CREATE TEMP TABLE IF NOT EXISTS seen_ids (id INTEGER PRIMARY KEY)');
    db()->exec('DELETE FROM seen_ids');

    $insert = db()->prepare('INSERT OR IGNORE INTO seen_ids (id) VALUES (?)');
    db()->beginTransaction();
    foreach ($seenIds as $id) {
        $insert->execute([$id]);
    }
    db()->commit();

    $now = date('c');

    $flag = db()->prepare("
        UPDATE items
           SET missing_since = COALESCE(missing_since, ?), updated_at = datetime('now')
         WHERE source = ? AND missing_since IS NULL
           AND $column IS NOT NULL
           AND $column NOT IN (SELECT id FROM seen_ids)
    ");
    $flag->execute([$now, $source]);
    $flagged = $flag->rowCount();

    db()->prepare("
        UPDATE items SET missing_since = NULL
         WHERE source = ? AND missing_since IS NOT NULL
           AND $column IN (SELECT id FROM seen_ids)
    ")->execute([$source]);

    db()->exec('DELETE FROM seen_ids');

    return $flagged;
}

/* ---------- Phase 3: the expensive half ---------- */

/** Releases that are on a shelf or a wantlist but whose detail is missing or stale. */
function count_pending_details(): int
{
    $stmt = db()->prepare("
        SELECT COUNT(*) FROM releases
         WHERE discogs_id IN (SELECT release_id FROM items WHERE release_id IS NOT NULL)
           AND (detail_fetched_at IS NULL OR detail_fetched_at < datetime('now', ?))
    ");
    $stmt->execute(['-' . detail_max_age_days() . ' days']);

    return (int) $stmt->fetchColumn();
}

/** Fetches full release detail until the time budget runs out. */
function sync_details(int $runId, DiscogsClient $client, float $deadline): int
{
    // Never-fetched releases first (a new record is invisible in the drawer
    // until its detail lands), then the stalest.
    $stmt = db()->prepare("
        SELECT r.discogs_id
          FROM releases r
         WHERE r.discogs_id IN (SELECT release_id FROM items WHERE release_id IS NOT NULL)
           AND (r.detail_fetched_at IS NULL OR r.detail_fetched_at < datetime('now', ?))
         ORDER BY r.detail_fetched_at IS NOT NULL, r.detail_fetched_at ASC
         LIMIT 500
    ");
    $stmt->execute(['-' . detail_max_age_days() . ' days']);

    $fetched = 0;
    foreach ($stmt->fetchAll() as $row) {
        if (microtime(true) >= $deadline) {
            break;
        }

        $full = $client->release((int) $row['discogs_id']);
        upsert_release_detail($full);
        $fetched++;

        db()->prepare('UPDATE sync_runs SET details_fetched = details_fetched + 1 WHERE id = ?')->execute([$runId]);
    }

    return $fetched;
}

function upsert_release_detail(array $full): void
{
    $identifiers = $full['identifiers'] ?? [];
    $formats = $full['formats'] ?? $full['format'] ?? [];

    db()->prepare("
        UPDATE releases SET
            master_id          = COALESCE(?, master_id),
            title              = ?,
            artists_text       = ?,
            primary_artist     = ?,
            artists_json       = ?,
            year               = COALESCE(?, year),
            labels_json        = ?,
            formats_json       = ?,
            formats_text       = ?,
            genres_json        = ?,
            styles_json        = ?,
            country            = ?,
            released           = ?,
            released_formatted = ?,
            uri                = ?,
            data_quality       = ?,
            release_notes      = ?,
            estimated_weight   = ?,
            num_for_sale       = ?,
            lowest_price       = ?,
            barcode            = ?,
            community_json     = ?,
            images_json        = ?,
            tracklist_json     = ?,
            identifiers_json   = ?,
            companies_json     = ?,
            extraartists_json  = ?,
            videos_json        = ?,
            series_json        = ?,
            date_changed       = ?,
            detail_fetched_at  = datetime('now'),
            updated_at         = datetime('now')
        WHERE discogs_id = ?
    ")->execute([
        !empty($full['master_id']) ? (int) $full['master_id'] : null,
        (string) ($full['title'] ?? ''),
        artists_text($full['artists'] ?? []),
        clean_artist_name((string) ($full['artists'][0]['name'] ?? '')),
        json_encode($full['artists'] ?? [], JSON_UNESCAPED_UNICODE),
        !empty($full['year']) ? (int) $full['year'] : null,
        json_encode($full['labels'] ?? [], JSON_UNESCAPED_UNICODE),
        json_encode($formats, JSON_UNESCAPED_UNICODE),
        formats_text($formats),
        json_encode($full['genres'] ?? [], JSON_UNESCAPED_UNICODE),
        json_encode($full['styles'] ?? [], JSON_UNESCAPED_UNICODE),
        (string) ($full['country'] ?? ''),
        (string) ($full['released'] ?? ''),
        (string) ($full['released_formatted'] ?? ''),
        (string) ($full['uri'] ?? ''),
        (string) ($full['data_quality'] ?? ''),
        (string) ($full['notes'] ?? ''),
        isset($full['estimated_weight']) ? (float) $full['estimated_weight'] : null,
        isset($full['num_for_sale']) ? (int) $full['num_for_sale'] : null,
        isset($full['lowest_price']) ? (float) $full['lowest_price'] : null,
        identifiers_barcode($identifiers),
        json_encode($full['community'] ?? [], JSON_UNESCAPED_UNICODE),
        json_encode($full['images'] ?? [], JSON_UNESCAPED_UNICODE),
        json_encode($full['tracklist'] ?? [], JSON_UNESCAPED_UNICODE),
        json_encode($identifiers, JSON_UNESCAPED_UNICODE),
        json_encode($full['companies'] ?? [], JSON_UNESCAPED_UNICODE),
        json_encode($full['extraartists'] ?? [], JSON_UNESCAPED_UNICODE),
        json_encode($full['videos'] ?? [], JSON_UNESCAPED_UNICODE),
        json_encode($full['series'] ?? [], JSON_UNESCAPED_UNICODE),
        (string) ($full['date_changed'] ?? ''),
        (int) $full['id'],
    ]);
}

/* ---------- One record, on demand ---------- */

/**
 * Refreshes a single record from Discogs: the full release detail and, for a
 * copy on the shelf, the rating and date on its own collection entry. It is the
 * admin's per-record Sync button — the same writes the full sync makes, for one
 * release, at the cost of one or two API calls.
 *
 * It only ever touches what Discogs owns: the release cache and the Discogs-side
 * columns of the item (rating, date added, folder). Everything typed in the
 * admin — corrections, notes, chosen pictures, locked format / artist / era —
 * lives in other columns and is not written here, which is what makes it safe
 * to press whenever.
 *
 * @return string a sentence saying what happened, for the flash message
 * @throws DiscogsException if Discogs can't be reached or has no such release
 */
function sync_one_item(array $item): string
{
    $releaseId = (int) ($item['release_id'] ?? 0);
    if ($releaseId <= 0) {
        throw new DiscogsException("This record isn't on Discogs, so there is nothing to sync.");
    }

    $client = sync_client();
    $full = $client->release($releaseId);
    $note = '';

    if ($item['source'] === 'collection' && !empty($item['instance_id'])) {
        try {
            $data = $client->request('GET', sprintf(
                '/users/%s/collection/releases/%d',
                rawurlencode(discogs_username()),
                $releaseId
            ));

            foreach (($data['releases'] ?? []) as $entry) {
                $basic = $entry['basic_information'] ?? null;
                if ($basic && (int) ($entry['instance_id'] ?? 0) === (int) $item['instance_id']) {
                    upsert_release_basic($basic);
                    upsert_collection_item($entry, $basic);
                }
            }
        } catch (DiscogsException $e) {
            // The release itself is the point; a copy that has since left the
            // collection shouldn't stop it being refreshed.
            $note = " Its collection entry couldn't be refreshed ({$e->getMessage()})";
        }
    }

    // After the basics above, so the fuller detail is what stays.
    upsert_release_detail($full);

    // A format the admin locked stays put; otherwise follow what Discogs now says.
    db()->prepare("UPDATE items SET media_kind = ?, updated_at = datetime('now') WHERE id = ? AND media_kind_locked = 0")
        ->execute([detect_media_kind($full['formats'] ?? $full['format'] ?? []), $item['id']]);

    // A changed title or credit can move it to another artist page or era.
    assign_items_to_artists_and_eras();

    return 'Synced with Discogs. What you typed here was left alone.' . $note;
}

/* ---------- Filing everything into artists and eras ---------- */

/**
 * Works out which artist page an item belongs on, and which era within it.
 *
 * Both are recomputed from scratch on every sync — except where the admin has
 * chosen one by hand (artist_locked, era_locked), which always wins. That way
 * adding a master id to an era in the admin re-files every pressing of that
 * album on the next sync without anyone touching an item.
 */
function assign_items_to_artists_and_eras(): void
{
    $artists = db()->query('SELECT id, name, match_names FROM artists')->fetchAll();

    // name (lowercased) -> artist id, including the alternative spellings.
    $byName = [];
    foreach ($artists as $artist) {
        $names = json_column($artist['match_names'], []);
        $names[] = $artist['name'];
        foreach ($names as $name) {
            $name = mb_strtolower(clean_artist_name((string) $name));
            if ($name !== '') {
                $byName[$name] = (int) $artist['id'];
            }
        }
    }

    // master/release id -> [era_id, rank]
    $rules = ['master' => [], 'release' => []];
    foreach (db()->query('SELECT era_id, kind, discogs_id, rank FROM era_rules') as $rule) {
        $rules[$rule['kind']][(int) $rule['discogs_id']] = [(int) $rule['era_id'], (int) $rule['rank']];
    }

    $rows = db()->query('
        SELECT i.id, i.artist_locked, i.era_locked, i.artist_id, i.era_id, i.era_rank,
               r.discogs_id, r.master_id, r.artists_json, r.primary_artist
          FROM items i
          LEFT JOIN releases r ON r.discogs_id = i.release_id
    ')->fetchAll();

    $update = db()->prepare('UPDATE items SET artist_id = ?, era_id = ?, era_rank = ? WHERE id = ?');

    db()->beginTransaction();
    foreach ($rows as $row) {
        // Every credited artist is considered, not just the first: a Beyoncé
        // single credited "Beyoncé & Jay-Z" still belongs on her page.
        $artistId = null;
        foreach (json_column($row['artists_json'], []) as $credit) {
            $name = mb_strtolower(clean_artist_name((string) ($credit['name'] ?? '')));
            if (isset($byName[$name])) {
                $artistId = $byName[$name];
                break;
            }
        }
        $artistId ??= $byName[mb_strtolower((string) $row['primary_artist'])] ?? null;

        if ($row['artist_locked']) {
            $artistId = $row['artist_id'] !== null ? (int) $row['artist_id'] : null;
        }

        if ($row['era_locked']) {
            $eraId = $row['era_id'] !== null ? (int) $row['era_id'] : null;
            $rank = (int) $row['era_rank'];
        } else {
            [$eraId, $rank] = $rules['release'][(int) $row['discogs_id']]
                ?? $rules['master'][(int) $row['master_id']]
                ?? [null, 1000];
        }

        // Only write when something actually changed: a no-op UPDATE on a few
        // hundred rows every sync is a lot of pointless disk for nothing.
        if ((int) $row['artist_id'] !== (int) $artistId
            || (int) $row['era_id'] !== (int) $eraId
            || (int) $row['era_rank'] !== $rank) {
            $update->execute([$artistId, $eraId, $rank, $row['id']]);
        }
    }
    db()->commit();
}
