<?php

/**
 * Pulling Discogs into the database.
 *
 * A run is a row in sync_runs that moves through three phases: the collection,
 * the wantlist, and each release's full detail (one API call per release,
 * against 60 a minute). sync_step() does one slice of work within a time
 * budget and returns, so the admin can poll it and cron can loop it; a run
 * that dies halfway carries on from where it stopped.
 *
 * A sync writes Discogs' side only: the `releases` cache and the Discogs
 * columns of an item. Nothing typed in the admin is ever touched.
 */

const SYNC_PER_PAGE = 100;

const SYNC_PHASE_LABELS = [
    'collection' => 'Reading the collection…',
    'wantlist'   => 'Reading the wantlist…',
    'details'    => 'Fetching release details…',
    'done'       => 'Done.',
];

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

/** Discogs data as it is stored in the release cache. */
function json_store(mixed $value): string
{
    return json_encode($value, JSON_UNESCAPED_UNICODE);
}

function release_formats(array $release): array
{
    return $release['formats'] ?? $release['format'] ?? [];
}

/* ---------- Runs ---------- */

function sync_start(string $trigger = 'admin'): int
{
    // Only one run at a time; one left 'running' for an hour has crashed.
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

function recent_sync_runs(int $limit): array
{
    return db()->query('SELECT * FROM sync_runs ORDER BY id DESC LIMIT ' . $limit)->fetchAll();
}

/** Sets columns on a run: sync_update($id, ['phase' => 'done']) */
function sync_update(int $runId, array $columns): void
{
    $sets = implode(', ', array_map(fn ($column) => "$column = ?", array_keys($columns)));
    db()->prepare("UPDATE sync_runs SET $sets WHERE id = ?")->execute([...array_values($columns), $runId]);
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
 * Does the next slice of a run.
 *
 * @param float $budget seconds of work before handing control back
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
            sync_update($runId, ['phase' => 'wantlist']);
        } elseif ($run['phase'] === 'wantlist') {
            $counts = sync_wantlist($runId, $client);
            sync_log($runId, "Wantlist: {$counts['seen']} records ({$counts['added']} new, {$counts['gone']} removed).");
            assign_items_to_artists_and_eras();
            sync_log($runId, 'Filed everything into artists and eras.');
            sync_update($runId, ['phase' => 'details']);
        } elseif ($run['phase'] === 'details') {
            $fetched = sync_details($runId, $client, $deadline);
            $pending = count_pending_details();
            sync_update($runId, ['details_pending' => $pending]);

            if ($pending === 0) {
                assign_items_to_artists_and_eras();
                sync_log($runId, 'Release details complete.');
                sync_update($runId, ['phase' => 'done']);
                sync_finish($runId, 'ok', 'Sync complete.');
            } elseif ($fetched === 0) {
                sync_log($runId, "Paused with $pending release(s) still to fetch.");
            }
        }
    } catch (DiscogsException $e) {
        sync_log($runId, 'Stopped: ' . $e->getMessage());
        // Details already fetched are kept; the next pass picks up where this left off.
        sync_finish($runId, $run['phase'] === 'details' ? 'partial' : 'error', $e->getMessage());
    }

    record_api_calls($runId, $client);

    $run = sync_run($runId);

    return sync_progress($run, $run['status'] !== 'running');
}

/** Adds the API calls made since the last step to the run (cron makes many steps in one request). */
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
    return [
        'run_id'   => (int) $run['id'],
        'done'     => $done,
        'phase'    => (string) $run['phase'],
        'status'   => (string) $run['status'],
        'message'  => (string) ($run['message'] ?? SYNC_PHASE_LABELS[$run['phase']] ?? ''),
        'progress' => [
            'collection' => (int) $run['collection_seen'],
            'wantlist'   => (int) $run['wantlist_seen'],
            'added'      => (int) $run['items_added'],
            'removed'    => (int) $run['items_removed'],
            'details'    => (int) $run['details_fetched'],
            'pending'    => (int) $run['details_pending'],
            'api_calls'  => (int) $run['api_calls'],
        ],
        'log' => (string) ($run['log'] ?? ''),
    ];
}

/** A whole sync, run to the end or until the budget is spent. For cron, where nobody watches. */
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
        // The run stays 'running', so the next pass continues it.
        $log('Budget spent; the next pass will continue this run.');
    }

    return $state;
}

/* ---------- Phases 1 and 2: the collection and the wantlist ---------- */

function sync_collection(int $runId, DiscogsClient $client): array
{
    return sync_listing($runId, [
        'source'      => 'collection',
        'fetch'       => fn (string $user, int $page) => $client->collectionPage($user, $page, SYNC_PER_PAGE),
        'entries'     => 'releases',
        'seen_column' => 'collection_seen',
        'match'       => 'instance_id',
        'id'          => fn (array $entry, array $basic) => (int) ($entry['instance_id'] ?? 0),
        'save'        => 'upsert_collection_item',
    ]);
}

function sync_wantlist(int $runId, DiscogsClient $client): array
{
    return sync_listing($runId, [
        'source'      => 'wantlist',
        'fetch'       => fn (string $user, int $page) => $client->wantlistPage($user, $page, SYNC_PER_PAGE),
        'entries'     => 'wants',
        'seen_column' => 'wantlist_seen',
        'match'       => 'release_id',
        'id'          => fn (array $entry, array $basic) => (int) ($basic['id'] ?? 0),
        'save'        => 'upsert_wantlist_item',
    ]);
}

/**
 * Reads every page of a Discogs listing into the database, then flags the rows
 * that listing no longer has.
 *
 * @return array{seen: int, added: int, gone: int}
 */
function sync_listing(int $runId, array $listing): array
{
    $username = discogs_username();
    if ($username === '') {
        throw new DiscogsException('No Discogs username is set in the config.');
    }

    $seen = [];
    $added = 0;
    $page = 1;

    do {
        $data = $listing['fetch']($username, $page);
        $pages = (int) ($data['pagination']['pages'] ?? 1);

        db()->beginTransaction();
        foreach (($data[$listing['entries']] ?? []) as $entry) {
            $basic = $entry['basic_information'] ?? null;
            $id = $basic ? $listing['id']($entry, $basic) : 0;
            if ($id === 0) {
                continue;
            }

            upsert_release_basic($basic);
            $added += $listing['save']($entry, $basic) ? 1 : 0;
            $seen[] = $id;
        }
        db()->commit();

        sync_update($runId, [$listing['seen_column'] => count($seen)]);
        $page++;
    } while ($page <= $pages);

    $gone = mark_missing($listing['source'], $seen, $listing['match']);

    db()->prepare('UPDATE sync_runs SET items_added = items_added + ?, items_removed = items_removed + ? WHERE id = ?')
        ->execute([$added, $gone, $runId]);

    return ['seen' => count($seen), 'added' => $added, 'gone' => $gone];
}

/**
 * Writes a release as a collection or wantlist listing describes it: enough
 * for the shelves, but not the tracklist or barcode. Detail already fetched
 * is left alone.
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
        json_store($artists),
        !empty($basic['year']) ? (int) $basic['year'] : null,
        json_store($basic['labels'] ?? []),
        json_store($formats),
        formats_text($formats),
        json_store($basic['genres'] ?? []),
        json_store($basic['styles'] ?? []),
        (string) ($basic['thumb'] ?? ''),
        (string) ($basic['cover_image'] ?? ''),
    ]);
}

function item_exists(string $where, array $params): bool
{
    $stmt = db()->prepare("SELECT 1 FROM items WHERE $where");
    $stmt->execute($params);

    return (bool) $stmt->fetchColumn();
}

/** @return bool whether this copy is new to the database */
function upsert_collection_item(array $entry, array $basic): bool
{
    $instanceId = (int) $entry['instance_id'];
    $isNew = !item_exists('instance_id = ?', [$instanceId]);

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
            media_kind          = CASE WHEN items.media_kind_locked = 1 THEN items.media_kind ELSE excluded.media_kind END,
            missing_since       = NULL,
            updated_at          = datetime('now')
    ")->execute([
        $instanceId,
        (int) $basic['id'],
        isset($entry['folder_id']) ? (int) $entry['folder_id'] : null,
        (string) ($entry['date_added'] ?? ''),
        !empty($entry['rating']) ? (int) $entry['rating'] : null,
        json_store($entry['notes'] ?? []),
        detect_media_kind($basic['formats'] ?? []),
    ]);

    return $isNew;
}

/** @return bool whether this want is new to the database */
function upsert_wantlist_item(array $want, array $basic): bool
{
    $releaseId = (int) $basic['id'];
    $isNew = !item_exists("source = 'wantlist' AND release_id = ?", [$releaseId]);

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

    return $isNew;
}

/**
 * Flags the rows a listing no longer has. They are kept, not deleted: they
 * carry hand-typed notes, and a copy can drop out of a listing by being moved
 * between folders mid-sync. A later sync that finds one again clears the flag.
 */
function mark_missing(string $source, array $seenIds, string $column): int
{
    // An empty listing is far likelier a half-answered API call than an emptied shelf.
    if (!$seenIds) {
        return 0;
    }

    // A temp table rather than NOT IN (?, ?, …), which older SQLite caps at 999 variables.
    db()->exec('CREATE TEMP TABLE IF NOT EXISTS seen_ids (id INTEGER PRIMARY KEY)');
    db()->exec('DELETE FROM seen_ids');

    $insert = db()->prepare('INSERT OR IGNORE INTO seen_ids (id) VALUES (?)');
    db()->beginTransaction();
    foreach ($seenIds as $id) {
        $insert->execute([$id]);
    }
    db()->commit();

    $flag = db()->prepare("
        UPDATE items
           SET missing_since = COALESCE(missing_since, ?), updated_at = datetime('now')
         WHERE source = ? AND missing_since IS NULL
           AND $column IS NOT NULL
           AND $column NOT IN (SELECT id FROM seen_ids)
    ");
    $flag->execute([date('c'), $source]);
    $flagged = $flag->rowCount();

    db()->prepare("
        UPDATE items SET missing_since = NULL
         WHERE source = ? AND missing_since IS NOT NULL
           AND $column IN (SELECT id FROM seen_ids)
    ")->execute([$source]);

    db()->exec('DELETE FROM seen_ids');

    return $flagged;
}

/* ---------- Phase 3: each release's full detail ---------- */

const STALE_DETAIL_WHERE = "
    discogs_id IN (SELECT release_id FROM items WHERE release_id IS NOT NULL)
    AND (detail_fetched_at IS NULL OR detail_fetched_at < datetime('now', ?))
";

function detail_age_modifier(): string
{
    return '-' . detail_max_age_days() . ' days';
}

/** Releases on a shelf or a wantlist whose detail is missing or stale. */
function count_pending_details(): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM releases WHERE ' . STALE_DETAIL_WHERE);
    $stmt->execute([detail_age_modifier()]);

    return (int) $stmt->fetchColumn();
}

/** Fetches full release detail, never-fetched first, until the deadline. */
function sync_details(int $runId, DiscogsClient $client, float $deadline): int
{
    $stmt = db()->prepare('
        SELECT discogs_id FROM releases
         WHERE ' . STALE_DETAIL_WHERE . '
         ORDER BY detail_fetched_at IS NOT NULL, detail_fetched_at ASC
         LIMIT 500
    ');
    $stmt->execute([detail_age_modifier()]);

    $fetched = 0;
    foreach ($stmt->fetchAll() as $row) {
        if (microtime(true) >= $deadline) {
            break;
        }

        upsert_release_detail($client->release((int) $row['discogs_id']));
        $fetched++;

        db()->prepare('UPDATE sync_runs SET details_fetched = details_fetched + 1 WHERE id = ?')->execute([$runId]);
    }

    return $fetched;
}

function upsert_release_detail(array $full): void
{
    $identifiers = $full['identifiers'] ?? [];
    $formats = release_formats($full);

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
        json_store($full['artists'] ?? []),
        !empty($full['year']) ? (int) $full['year'] : null,
        json_store($full['labels'] ?? []),
        json_store($formats),
        formats_text($formats),
        json_store($full['genres'] ?? []),
        json_store($full['styles'] ?? []),
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
        json_store($full['community'] ?? []),
        json_store($full['images'] ?? []),
        json_store($full['tracklist'] ?? []),
        json_store($identifiers),
        json_store($full['companies'] ?? []),
        json_store($full['extraartists'] ?? []),
        json_store($full['videos'] ?? []),
        json_store($full['series'] ?? []),
        (string) ($full['date_changed'] ?? ''),
        (int) $full['id'],
    ]);
}

/* ---------- One record at a time ---------- */

/**
 * The per-record Sync button: the release's full detail and, for a copy on the
 * shelf, its own collection entry. One or two API calls.
 *
 * @return string what happened, for the flash message
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
            // A copy that has left the collection shouldn't stop the release refreshing.
            $note = " Its collection entry couldn't be refreshed ({$e->getMessage()})";
        }
    }

    // After the basics, so the fuller detail is what stays.
    upsert_release_detail($full);

    db()->prepare("UPDATE items SET media_kind = ?, updated_at = datetime('now') WHERE id = ? AND media_kind_locked = 0")
        ->execute([detect_media_kind(release_formats($full)), $item['id']]);

    // A changed title or credit can move it to another artist page or era.
    assign_items_to_artists_and_eras();

    return 'Synced with Discogs. What you typed here was left alone.' . $note;
}

/**
 * Starts a listing for sale from a Discogs release id: the release goes into
 * the cache and a hidden for_sale item points at it.
 *
 * @throws DiscogsException if Discogs has no such release
 */
function create_sale_item_from_discogs(int $releaseId): array
{
    $full = sync_client()->release($releaseId);

    // upsert_release_detail() only updates, so the row has to exist first.
    db()->prepare('INSERT OR IGNORE INTO releases (discogs_id) VALUES (?)')->execute([$releaseId]);
    upsert_release_detail($full);

    // The cover and thumb normally come from a listing's basic information,
    // which a listing started here never has: take them from the gallery.
    $images = $full['images'] ?? [];
    $primary = current(array_filter($images, fn ($i) => ($i['type'] ?? '') === 'primary')) ?: ($images[0] ?? null);
    if ($primary) {
        db()->prepare('UPDATE releases SET cover_image = ?, thumb = ? WHERE discogs_id = ?')
            ->execute([(string) ($primary['uri'] ?? ''), (string) ($primary['uri150'] ?? $primary['uri'] ?? ''), $releaseId]);
    }

    db()->prepare("
        INSERT INTO items (source, release_id, media_kind, sale_currency, is_visible)
        VALUES ('for_sale', ?, ?, 'AUD', 0)
    ")->execute([$releaseId, detect_media_kind(release_formats($full))]);

    return item_by_id((int) db()->lastInsertId());
}

/* ---------- Filing into artists and eras ---------- */

/**
 * Works out which artist page and era every item belongs on, from the credits
 * and the era rules. Recomputed on every sync, except where the admin chose by
 * hand (artist_locked, era_locked).
 */
function assign_items_to_artists_and_eras(): void
{
    $artistByName = [];
    foreach (db()->query('SELECT id, name, match_names FROM artists')->fetchAll() as $artist) {
        foreach ([...json_column($artist['match_names'], []), $artist['name']] as $name) {
            $name = mb_strtolower(clean_artist_name((string) $name));
            if ($name !== '') {
                $artistByName[$name] = (int) $artist['id'];
            }
        }
    }

    // [kind][discogs id] => [era id, rank]
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
        if ($row['artist_locked']) {
            $artistId = $row['artist_id'] !== null ? (int) $row['artist_id'] : null;
        } else {
            $artistId = credited_artist_id($row, $artistByName);
        }

        if ($row['era_locked']) {
            $eraId = $row['era_id'] !== null ? (int) $row['era_id'] : null;
            $rank = (int) $row['era_rank'];
        } else {
            [$eraId, $rank] = $rules['release'][(int) $row['discogs_id']]
                ?? $rules['master'][(int) $row['master_id']]
                ?? [null, 1000];
        }

        if ((int) $row['artist_id'] !== (int) $artistId
            || (int) $row['era_id'] !== (int) $eraId
            || (int) $row['era_rank'] !== $rank) {
            $update->execute([$artistId, $eraId, $rank, $row['id']]);
        }
    }
    db()->commit();
}

/** The first credited artist with a page ("Beyoncé & Jay-Z" still files under Beyoncé). */
function credited_artist_id(array $row, array $artistByName): ?int
{
    foreach (json_column($row['artists_json'], []) as $credit) {
        $name = mb_strtolower(clean_artist_name((string) ($credit['name'] ?? '')));
        if (isset($artistByName[$name])) {
            return $artistByName[$name];
        }
    }

    return $artistByName[mb_strtolower((string) $row['primary_artist'])] ?? null;
}
