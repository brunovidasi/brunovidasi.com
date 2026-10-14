<?php

/**
 * The sync, the same for every platform:
 *
 *   1. fetch      ask the platform (its client, includes/sync/<platform>.php), with
 *                 the sign-in kept in credentials, renewed as it goes
 *   2. snapshot   keep what it answered, gzipped (sync_snapshots)
 *   3. normalize  turn the answer into the records the site shows (the shape
 *                 data/<platform>.json had: what tools/<platform>-sync.mjs's fromExport() made)
 *   4. merge      tie copies to the platform's ids, add a copy for a game bought
 *                 in the platform's store, fill fields that are still empty
 *   5. commit     write it all in one transaction, every difference noted in
 *                 sync_changes, every record it replaced in platform_record_history
 *
 * What a sync never does: delete anything, or change a field you typed. A field
 * it filled stays its own (field_state) until you change it, and then it is
 * yours: the sync only notes what it would have written.
 */

require_once __DIR__ . '/steam.php';
require_once __DIR__ . '/psn.php';
require_once __DIR__ . '/xbox.php';
require_once __DIR__ . '/nintendo.php';
require_once __DIR__ . '/ea.php';
require_once __DIR__ . '/rockstar.php';

/** A sign-in that has stopped working: the connection shows it, and the fix is to connect again. */
final class AuthError extends RuntimeException
{
}

abstract class PlatformSync
{
    public function __construct(public readonly string $platform)
    {
    }

    /** Whether what the sync needs has been saved (Settings → Connections). */
    abstract public function connected(): bool;

    /** Everything the platform says, as one array: the snapshot of the run. */
    abstract public function fetch(SyncRun $run): array;

    /**
     * The fetched answer as the site keeps it:
     * ['account' => [...the platform file's other keys], 'collections' => [name => [ext_id => record]]]
     */
    abstract public function normalize(array $raw, SyncRun $run): array;

    /** Ties, adds and fills copies in $run->games. */
    abstract public function merge(array $data, SyncRun $run): void;
}

function platform_sync(string $platform): PlatformSync
{
    return match ($platform) {
        'steam'    => new SteamSync('steam'),
        'psn'      => new PsnSync('psn'),
        'xbox'     => new XboxSync('xbox'),
        'nintendo' => new NintendoSync('nintendo'),
        'ea'       => new EaSync('ea'),
        'rockstar' => new RockstarSync('rockstar'),
        default    => throw new InvalidArgumentException("No such platform: $platform"),
    };
}

/** One sync of one platform, from start to commit. */
final class SyncRun
{
    /** id => the site's object, the copies as the sync changes them */
    public array $games = [];
    /** id => the copies as they were */
    private array $original = [];
    /** game_id => field => field_state row */
    private array $states = [];
    private array $stateWrites = [];
    private array $added = [];
    private array $changes = [];
    private array $unmatched = [];
    private array $logLines = [];
    private int $startCalls;
    /** What the platform account is called, when the client found out. */
    public ?string $account = null;

    public function __construct(public readonly int $id, public readonly string $platform, bool $loadGames = true)
    {
        $this->startCalls = Http::$calls;
        if ($loadGames) {
            $this->games = all_games();
            $this->original = $this->games;
            foreach (db()->query('SELECT * FROM field_state') as $row) {
                $this->states[$row['game_id']][$row['field']] = $row;
            }
        }
    }

    public static function start(string $platform, string $trigger): self
    {
        // a run that died without finishing (a timeout, a closed laptop) is marked so
        db()->prepare("UPDATE sync_runs SET status = 'error', finished_at = datetime('now'), message = COALESCE(message, 'Stopped before it finished.')
                       WHERE platform = ? AND status = 'running' AND started_at < datetime('now', '-30 minutes')")->execute([$platform]);

        db()->prepare('INSERT INTO sync_runs (platform, trigger_source) VALUES (?, ?)')->execute([$platform, $trigger]);

        return new self((int) db()->lastInsertId(), $platform);
    }

    public function log(string $message): void
    {
        $this->logLines[] = '[' . date('H:i:s') . "] $message";
    }

    public function calls(): int
    {
        return Http::$calls - $this->startCalls;
    }

    /* ---------- what a merge may do ---------- */

    /** Ties a copy to a platform id ($field: steamAppId, psnTitleId…). */
    public function link(string $gameId, string $field, string|int $extId, string $label): void
    {
        $old = $this->games[$gameId][$field] ?? null;
        $this->games[$gameId][$field] = $extId;
        $this->change('linked', $gameId, $field, $old, $extId, $label);
    }

    /**
     * A new copy for a game the platform has and the shelf hasn't. Its fields are
     * the sync's (field_state), so a later sync may still fill them in.
     */
    public function add(array $game, string $suffix, string $label): string
    {
        $id = $game['id'];
        while (isset($this->games[$id])) {
            $id .= $suffix;
        }
        $game['id'] = $id;
        $this->games[$id] = $game;
        $this->added[$id] = true;
        foreach ($game as $field => $value) {
            if ($value !== null && isset(FIELDS[$field]) && !isset(FIELDS[$field]['platform'])) {
                $this->stateWrites[$id][$field] = ['value' => $value, 'locked' => null];
                $this->states[$id][$field] = ['source' => $this->platform, 'locked' => 0, 'synced_value' => to_json($value)];
            }
        }
        $this->change('added', $id, null, null, $game, $label);

        return $id;
    }

    /**
     * Offers a value for a copy's field. It is written when the field is empty,
     * or when this platform wrote it before and it hasn't been changed by hand
     * since. A field you changed keeps your value; the sync only notes its own.
     *
     * Returns whether the copy changed.
     */
    public function fill(string $gameId, string $field, mixed $value): bool
    {
        if ($value === null || $value === '' || !isset($this->games[$gameId])) {
            return false;
        }

        $current = $this->games[$gameId][$field] ?? null;
        $state = $this->states[$gameId][$field] ?? null;
        $label = $this->games[$gameId]['title'];

        $empty = $current === null || $current === '' || $current === [];
        $ours = $state && $state['source'] === $this->platform && !$state['locked'];

        if ($empty || ($ours && $current !== $value)) {
            $this->games[$gameId][$field] = $value;
            $this->stateWrites[$gameId][$field] = ['value' => $value, 'locked' => null];
            if (!isset($this->added[$gameId])) {
                $this->change('filled', $gameId, $field, $current, $value, $label);
            }
            return true;
        }

        if ($state && $state['locked'] && from_json($state['synced_value']) !== $value) {
            // yours stays; what the platform says now is kept beside it
            $this->stateWrites[$gameId][$field] = ['value' => $value, 'locked' => true];
            if ($current !== $value) {
                $this->change('kept', $gameId, $field, $current, $value, $label, 'Your value was kept.');
            }
        }

        return false;
    }

    /** A platform game the sync could not tie to one copy: none on the shelf, or several it could be. */
    public function report(string $reason, string $collection, string $extId, string $name, array $candidates = [], ?string $note = null): void
    {
        $this->unmatched[$collection . "\0" . $extId] = compact('reason', 'collection', 'extId', 'name', 'candidates');
        $this->change($reason, null, null, null, $candidates ?: null, $name, $note, $collection, $extId);
    }

    /** A note in the history that isn't a change (a copy whose game left the platform's list…). */
    public function change(string $kind, ?string $gameId, ?string $field, mixed $old, mixed $new, ?string $label, ?string $note = null, ?string $collection = null, ?string $extId = null): void
    {
        $this->changes[] = [$kind, $gameId, $collection, $extId, $label, $field, to_json($old), to_json($new), $note];
    }

    /* ---------- commit ---------- */

    /** Writes the copies, the records and the run, all or nothing. */
    public function commit(array $data, string $raw): array
    {
        return db_transaction(function (PDO $db) use ($data, $raw) {
            // the copies
            $changedGames = 0;
            foreach ($this->games as $id => $game) {
                if (isset($this->added[$id])) {
                    insert_game($game);
                } elseif (($this->original[$id] ?? null) !== $game) {
                    update_game($game);
                    $changedGames++;
                }
            }
            foreach ($this->stateWrites as $gameId => $fields) {
                foreach ($fields as $field => $w) {
                    set_field_state($gameId, $field, $this->platform, $w['value'], $w['locked']);
                }
            }

            // the records, each one it replaced kept in the history
            $recordsChanged = $this->commitRecords($db, $data);

            // the account's figures
            $oldAccount = platform_account($this->platform);
            $db->prepare('INSERT INTO platform_record_history (run_id, platform, collection, ext_id, data_json) VALUES (?, ?, ?, ?, ?)')
                ->execute([$this->id, $this->platform, '_account', '', $oldAccount === null ? null : to_json($oldAccount)]);
            set_platform_account($this->platform, $data['account']);

            // what still needs tying by hand
            $this->commitUnmatched($db);

            // what was answered, for the last few runs
            $stmt = $db->prepare('INSERT OR REPLACE INTO sync_snapshots (run_id, platform, raw_gz, bytes) VALUES (?, ?, ?, ?)');
            $stmt->bindValue(1, $this->id, PDO::PARAM_INT);
            $stmt->bindValue(2, $this->platform);
            $stmt->bindValue(3, gzencode($raw, 6), PDO::PARAM_LOB);
            $stmt->bindValue(4, strlen($raw), PDO::PARAM_INT);
            $stmt->execute();
            $keep = max(1, (int) (setting('sync', [])['keep_snapshots'] ?? 10));
            $db->prepare('DELETE FROM sync_snapshots WHERE platform = ? AND run_id NOT IN (SELECT run_id FROM sync_snapshots WHERE platform = ? ORDER BY run_id DESC LIMIT ?)')
                ->execute([$this->platform, $this->platform, $keep]);

            // every difference
            $insert = $db->prepare('INSERT INTO sync_changes (run_id, kind, game_id, collection, ext_id, label, field, old_json, new_json, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            foreach ($this->changes as $c) {
                $insert->execute([$this->id, ...$c]);
            }

            $count = fn (string $kind) => count(array_filter($this->changes, fn ($c) => $c[0] === $kind));
            $summary = [
                'records_seen'    => array_sum(array_map('count', $data['collections'])),
                'games_added'     => count($this->added),
                'games_linked'    => $count('linked'),
                'games_changed'   => $changedGames,
                'records_changed' => $recordsChanged,
                'unmatched'       => count($this->unmatched),
                'api_calls'       => $this->calls(),
            ];
            $message = $this->summaryMessage($summary);
            $this->log($message);
            $db->prepare("UPDATE sync_runs SET status = 'ok', finished_at = datetime('now'), records_seen = ?, games_added = ?, games_linked = ?,
                          games_changed = ?, records_changed = ?, unmatched = ?, api_calls = ?, message = ?, log = ? WHERE id = ?")
                ->execute([...array_values($summary), $message, implode("\n", $this->logLines), $this->id]);

            touch_data();

            return $summary + ['message' => $message];
        });
    }

    private function commitRecords(PDO $db, array $data): int
    {
        $changed = 0;
        $fields = PLATFORM_FIELDS[$this->platform] ?? [];
        $overrides = platform_overrides($this->platform);
        $history = $db->prepare('INSERT INTO platform_record_history (run_id, platform, collection, ext_id, data_json, missing_since) VALUES (?, ?, ?, ?, ?, ?)');
        $upsert = $db->prepare("INSERT INTO platform_records (platform, collection, ext_id, position, data_json, run_id) VALUES (?, ?, ?, ?, ?, ?)
            ON CONFLICT(platform, collection, ext_id) DO UPDATE SET position = excluded.position, data_json = excluded.data_json,
                synced_at = datetime('now'), run_id = excluded.run_id, missing_since = NULL");

        foreach ($data['collections'] as $collection => $records) {
            $stmt = $db->prepare('SELECT ext_id, data_json, missing_since FROM platform_records WHERE platform = ? AND collection = ?');
            $stmt->execute([$this->platform, $collection]);
            $old = array_column($stmt->fetchAll(), null, 'ext_id');

            $position = 0;
            foreach ($records as $extId => $record) {
                $extId = (string) $extId;
                $json = to_json($record);
                $before = $old[$extId] ?? null;
                $label = $record['name'] ?? $extId;

                if ($before === null) {
                    $history->execute([$this->id, $this->platform, $collection, $extId, null, null]);
                    $this->change('new_record', null, null, null, null, $label, null, $collection, $extId);
                    $changed++;
                } elseif (!same_json($before['data_json'], $record) || $before['missing_since'] !== null) {
                    $history->execute([$this->id, $this->platform, $collection, $extId, $before['data_json'], $before['missing_since']]);
                    $prev = json_decode($before['data_json'], true);
                    $noted = false;
                    foreach ($fields[$collection] ?? [] as $path => [$name]) {
                        $a = path_get($prev, $path);
                        $b = path_get($record, $path);
                        if ($a != $b) {
                            $this->change('record', null, $path, $a, $b, $label, null, $collection, $extId);
                            $noted = true;
                        }
                    }
                    if (!$noted) {
                        $this->change('record', null, null, null, null, $label, $before['missing_since'] !== null ? 'Back in the platform\'s list.' : 'Other details changed.', $collection, $extId);
                    }
                    $changed++;
                } else {
                    // the same as before: kept exactly as it was stored
                    $json = $before['data_json'];
                }

                $upsert->execute([$this->platform, $collection, $extId, $position++, $json, $this->id]);

                // a value corrected "until the next sync changes it" gives way once it has
                foreach ($overrides[$collection . "\0" . $extId] ?? [] as $path => $o) {
                    if ($o['mode'] === 'until_sync' && from_json($o['base_json']) != path_get($record, $path)) {
                        $db->prepare('DELETE FROM platform_overrides WHERE platform = ? AND collection = ? AND ext_id = ? AND path = ?')
                            ->execute([$this->platform, $collection, $extId, $path]);
                        $this->change('record', null, $path, from_json($o['value_json']), path_get($record, $path), $label, 'Your value gave way to the synced one.', $collection, $extId);
                    }
                }
                unset($old[$extId]);
            }

            // what the platform no longer lists stays, marked, as the copy tied to it does
            foreach ($old as $extId => $row) {
                if ($row['missing_since'] === null) {
                    $history->execute([$this->id, $this->platform, $collection, (string) $extId, $row['data_json'], null]);
                    $db->prepare("UPDATE platform_records SET missing_since = datetime('now'), run_id = ? WHERE platform = ? AND collection = ? AND ext_id = ?")
                        ->execute([$this->id, $this->platform, $collection, (string) $extId]);
                    $rec = json_decode($row['data_json'], true);
                    $this->change('missing', null, null, null, null, $rec['name'] ?? (string) $extId, 'No longer in the platform\'s list; kept.', $collection, (string) $extId);
                    $changed++;
                }
            }
        }

        return $changed;
    }

    private function commitUnmatched(PDO $db): void
    {
        $upsert = $db->prepare("INSERT INTO sync_unmatched (platform, collection, ext_id, name, reason, candidates_json) VALUES (?, ?, ?, ?, ?, ?)
            ON CONFLICT(platform, collection, ext_id) DO UPDATE SET name = excluded.name, reason = excluded.reason,
                candidates_json = excluded.candidates_json, last_seen_at = datetime('now')");
        foreach ($this->unmatched as $u) {
            $upsert->execute([$this->platform, $u['collection'], $u['extId'], $u['name'], $u['reason'], to_json($u['candidates'] ?: null)]);
        }

        // one that wasn't reported this time has been tied, or has gone
        $stmt = $db->prepare('SELECT collection, ext_id FROM sync_unmatched WHERE platform = ?');
        $stmt->execute([$this->platform]);
        foreach ($stmt->fetchAll() as $row) {
            if (!isset($this->unmatched[$row['collection'] . "\0" . $row['ext_id']])) {
                $db->prepare('DELETE FROM sync_unmatched WHERE platform = ? AND collection = ? AND ext_id = ?')
                    ->execute([$this->platform, $row['collection'], $row['ext_id']]);
            }
        }
    }

    private function summaryMessage(array $s): string
    {
        $parts = [$s['records_seen'] . ' ' . plural($s['records_seen'], 'game') . ' seen'];
        if ($s['games_added']) {
            $parts[] = $s['games_added'] . ' ' . plural($s['games_added'], 'copy', 'copies') . ' added';
        }
        if ($s['games_linked']) {
            $parts[] = $s['games_linked'] . ' tied';
        }
        if ($s['records_changed']) {
            $parts[] = $s['records_changed'] . ' changed';
        }
        if ($s['unmatched']) {
            $parts[] = $s['unmatched'] . ' to tie by hand';
        }

        return ucfirst(implode(', ', $parts)) . '.';
    }

    public function fail(Throwable $e): void
    {
        $this->log('Stopped: ' . $e->getMessage());
        db()->prepare("UPDATE sync_runs SET status = 'error', finished_at = datetime('now'), api_calls = ?, message = ?, log = ? WHERE id = ?")
            ->execute([$this->calls(), $e->getMessage(), implode("\n", $this->logLines), $this->id]);
    }
}

/** Whether a stored record and a fresh one say the same ({} and [] alike, the order of keys aside). */
function same_json(string $stored, array $fresh): bool
{
    $canon = function (mixed $v) use (&$canon): mixed {
        if (!is_array($v)) {
            return is_float($v) && floor($v) == $v ? (int) $v : $v;
        }
        if (!array_is_list($v)) {
            ksort($v);
        }
        return array_map($canon, $v);
    };

    return $canon(json_decode($stored, true)) === $canon($fresh);
}

/* ---------- running ---------- */

/**
 * Syncs one platform. Returns the run's summary; never throws, a failure is a
 * run with status 'error' and its message.
 */
function run_sync(string $platform, string $trigger = 'admin'): array
{
    $client = platform_sync($platform);
    if (!$client->connected()) {
        return ['status' => 'skipped', 'message' => platform_name($platform) . ' is not connected yet (Settings → Connections).', 'run_id' => null];
    }

    @set_time_limit(600);
    $run = SyncRun::start($platform, $trigger);
    try {
        $run->log('Asking ' . platform_name($platform) . '…');
        $raw = $client->fetch($run);
        $run->log('Answered in ' . $run->calls() . ' ' . plural($run->calls(), 'request') . '.');
        $data = $client->normalize($raw, $run);
        $client->merge($data, $run);
        $summary = $run->commit($data, json_encode($raw, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}');
        set_connection_status($platform, 'ok', null, $run->account);

        return ['status' => 'ok', 'run_id' => $run->id] + $summary;
    } catch (Throwable $e) {
        error_log("Sync of $platform failed: $e");
        $run->fail($e);
        set_connection_status($platform, $e instanceof AuthError ? 'error' : connection_info($platform)['status'], $e->getMessage());

        return ['status' => 'error', 'run_id' => $run->id, 'message' => $e->getMessage()];
    }
}

/** Every connected platform, one after the other. */
function run_all_syncs(string $trigger, ?array $platforms = null): array
{
    $out = [];
    foreach ($platforms ?? array_keys(PLATFORMS) as $platform) {
        $out[$platform] = run_sync($platform, $trigger);
    }

    return $out;
}

/* ---------- connections ---------- */

function connection_info(string $platform): array
{
    $stmt = db()->prepare('SELECT * FROM connections WHERE platform = ?');
    $stmt->execute([$platform]);

    return $stmt->fetch() ?: ['platform' => $platform, 'status' => 'unset', 'message' => null, 'account' => null, 'checked_at' => null, 'connected_at' => null];
}

function set_connection_status(string $platform, string $status, ?string $message = null, ?string $account = null, bool $connected = false): void
{
    db()->prepare("
        INSERT INTO connections (platform, status, message, account, checked_at, connected_at) VALUES (?, ?, ?, ?, datetime('now'), ?)
        ON CONFLICT(platform) DO UPDATE SET status = excluded.status, message = excluded.message,
            account = COALESCE(excluded.account, account), checked_at = excluded.checked_at,
            connected_at = COALESCE(excluded.connected_at, connected_at)
    ")->execute([$platform, $status, $message, $account, $connected ? gmdate('Y-m-d H:i:s') : null]);
}

function forget_connection(string $platform): void
{
    forget_credentials($platform);
    db()->prepare('DELETE FROM connections WHERE platform = ?')->execute([$platform]);
}

/* ---------- history ---------- */

function latest_sync_run(?string $platform = null): ?array
{
    $sql = 'SELECT * FROM sync_runs' . ($platform ? ' WHERE platform = ?' : '') . ' ORDER BY id DESC LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute($platform ? [$platform] : []);

    return $stmt->fetch() ?: null;
}

function latest_ok_sync_run(string $platform): ?array
{
    $stmt = db()->prepare("SELECT * FROM sync_runs WHERE platform = ? AND status = 'ok' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$platform]);

    return $stmt->fetch() ?: null;
}

/** Whether a run can be undone: its platform's latest good run, not undone yet. */
function can_revert_run(array $run): bool
{
    $latest = latest_ok_sync_run($run['platform']);

    return $run['status'] === 'ok' && $latest && (int) $latest['id'] === (int) $run['id'];
}

/**
 * Undoes a run: the copies it added go, the fields it tied and filled go back to
 * what they were (where nobody has changed them since), and every record it
 * replaced is put back. Only a platform's latest run can be undone, so nothing
 * newer was built on it.
 */
function revert_sync_run(int $runId): string
{
    $stmt = db()->prepare('SELECT * FROM sync_runs WHERE id = ?');
    $stmt->execute([$runId]);
    $run = $stmt->fetch();
    if (!$run || !can_revert_run($run)) {
        throw new RuntimeException('Only the latest sync of a platform can be undone, once.');
    }

    return db_transaction(function (PDO $db) use ($run) {
        $changes = $db->prepare('SELECT * FROM sync_changes WHERE run_id = ? ORDER BY id DESC');
        $changes->execute([$run['id']]);
        $removed = 0;
        $restored = 0;
        $skipped = 0;

        foreach ($changes->fetchAll() as $c) {
            if ($c['kind'] === 'added' && $c['game_id'] && game_exists($c['game_id'])) {
                delete_game($c['game_id']);
                $removed++;
            } elseif (in_array($c['kind'], ['linked', 'filled'], true) && $c['game_id'] && ($game = find_game($c['game_id']))) {
                // only where it still holds what the sync wrote
                if (($game[$c['field']] ?? null) == from_json($c['new_json'])) {
                    $game[$c['field']] = from_json($c['old_json']);
                    update_game($game);
                    if ($c['kind'] === 'filled') {
                        $db->prepare('DELETE FROM field_state WHERE game_id = ? AND field = ? AND locked = 0')->execute([$c['game_id'], $c['field']]);
                    }
                    $restored++;
                } else {
                    $skipped++;
                }
            }
        }

        // the records and the account's figures, as they were before the run
        $history = $db->prepare('SELECT * FROM platform_record_history WHERE run_id = ? ORDER BY id DESC');
        $history->execute([$run['id']]);
        foreach ($history->fetchAll() as $h) {
            if ($h['collection'] === '_account') {
                if ($h['data_json'] === null) {
                    $db->prepare('DELETE FROM platform_accounts WHERE platform = ?')->execute([$run['platform']]);
                } else {
                    set_platform_account($run['platform'], json_decode($h['data_json'], true));
                }
                continue;
            }
            if ($h['data_json'] === null) {
                $db->prepare('DELETE FROM platform_records WHERE platform = ? AND collection = ? AND ext_id = ?')
                    ->execute([$h['platform'], $h['collection'], $h['ext_id']]);
            } else {
                $db->prepare('UPDATE platform_records SET data_json = ?, missing_since = ? WHERE platform = ? AND collection = ? AND ext_id = ?')
                    ->execute([$h['data_json'], $h['missing_since'], $h['platform'], $h['collection'], $h['ext_id']]);
            }
            $restored++;
        }

        $db->prepare("UPDATE sync_runs SET status = 'reverted', reverted_at = datetime('now') WHERE id = ?")->execute([$run['id']]);
        touch_data();

        $msg = "Undone: $restored " . plural($restored, 'value') . ' put back';
        if ($removed) {
            $msg .= ", $removed added " . plural($removed, 'copy', 'copies') . ' removed';
        }
        if ($skipped) {
            $msg .= ", $skipped left as they are (changed since)";
        }

        return $msg . '.';
    });
}

/* ---------- shared by the clients ---------- */

/** "Grand Theft Auto™ V®" → "Grand Theft Auto V" */
function clean_name(?string $s): string
{
    return trim(preg_replace('/\s+/u', ' ', preg_replace('/[™®©]/u', '', (string) $s)));
}

/** The latest of two ISO dates, either of which may be missing. */
function latest_date(?string $a, ?string $b): ?string
{
    return (!$a || ($b && $b > $a)) ? $b : $a;
}

/** A Unix time, or a date string, as JavaScript's toISOString() writes it (UTC, with milliseconds). */
function iso_date(mixed $t): ?string
{
    if ($t === null || $t === '' || $t === 0 || $t === false) {
        return null;
    }
    if (is_int($t) || ctype_digit((string) $t)) {
        return gmdate('Y-m-d\TH:i:s', (int) $t) . '.000Z';
    }
    try {
        $d = new DateTimeImmutable((string) $t);
    } catch (Exception) {
        return null;
    }

    return $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.v\Z');
}

/** A copy's names, tidied for comparing: its title and what it is listed as. */
function names_of(array $game, callable $norm): array
{
    return array_map($norm, array_filter([$game['title'] ?? null, $game['listedAs'] ?? null]));
}

/** The ids of a list of copies, for a report. */
function ids_of(array $games): array
{
    return array_values(array_map(fn ($g) => $g['id'], $games));
}
