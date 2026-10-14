<?php

/**
 * Copies, between a games row and the shape the site reads (the objects
 * games.json held). FIELDS (includes/fields.php) says which key is which column.
 */

/** A games row as the site's object: the id first, then the fields in FIELDS' order, then any others. */
function game_from_row(array $row): array
{
    $game = ['id' => $row['id']];
    $extra = json_column($row['extra_json'] ?? null);

    foreach (FIELDS as $key => $f) {
        if (isset($f['col'])) {
            $value = decode_field_value($f, $row[$f['col']] ?? null);
        } else {
            $value = array_key_exists($key, $extra) ? $extra[$key] : null;
            unset($extra[$key]);
        }

        if ($value !== null || !empty($f['always'])) {
            $game[$key] = $value;
        }
    }

    // a field the site uses that FIELDS doesn't know yet is kept and handed on as it is
    foreach ($extra as $key => $value) {
        $game[$key] = $value;
    }

    return $game;
}

/** The site's object as a games row's columns (without position and the dates). */
function game_to_row(array $game): array
{
    $row = ['id' => (string) $game['id']];
    $extra = [];

    foreach (FIELDS as $key => $f) {
        $value = $game[$key] ?? null;
        if (isset($f['col'])) {
            $row[$f['col']] = encode_field_value($f, $value);
        } elseif ($value !== null) {
            $extra[$key] = $value;
        }
    }

    foreach ($game as $key => $value) {
        if ($key !== 'id' && !isset(FIELDS[$key]) && $value !== null) {
            $extra[$key] = $value;
        }
    }

    $row['extra_json'] = $extra ? to_json($extra) : null;

    return $row;
}

function decode_field_value(array $f, mixed $raw): mixed
{
    if ($raw === null) {
        return null;
    }

    return match ($f['type']) {
        'bool'   => (bool) $raw,
        'int'    => (int) $raw,
        'idlist' => str_starts_with((string) $raw, '[') ? json_decode($raw, true) : (string) $raw,
        'list', 'json', 'cover', 'colors' => json_decode($raw, true),
        default  => (string) $raw,
    };
}

function encode_field_value(array $f, mixed $value): mixed
{
    if ($value === null) {
        return null;
    }

    return match ($f['type']) {
        'bool'   => $value ? 1 : 0,
        'int'    => (int) $value,
        'idlist' => is_array($value) ? to_json(array_values($value)) : (string) $value,
        'list', 'json', 'cover', 'colors' => to_json($value),
        default  => (string) $value,
    };
}

/** A copy as the public API hands it out: without the fields only the admin sees (check, dateNote, privateNote). */
function public_game(array $game): array
{
    return array_diff_key($game, array_filter(FIELDS, fn ($f) => !empty($f['private'])));
}

/* ---------- reading ---------- */

/** Every copy, in the site's order, as the site's objects keyed by id. */
function all_games(): array
{
    $games = [];
    foreach (db()->query('SELECT * FROM games ORDER BY position, id') as $row) {
        $games[$row['id']] = game_from_row($row);
    }

    return $games;
}

function find_game(string $id): ?array
{
    $row = find_game_row($id);

    return $row ? game_from_row($row) : null;
}

function find_game_row(string $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM games WHERE id = ?');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

function game_exists(string $id): bool
{
    $stmt = db()->prepare('SELECT 1 FROM games WHERE id = ?');
    $stmt->execute([$id]);

    return (bool) $stmt->fetchColumn();
}

/** "console-title", made unique with $suffix ("-steam") as the sync tools did. */
function unique_game_id(string $base, string $suffix = '-2'): string
{
    $id = $base;
    while (game_exists($id)) {
        $id .= $suffix;
    }

    return $id;
}

/* ---------- writing ---------- */

/**
 * Inserts a new copy after the last one on its console (where the sync tools put
 * them in games.json), or at $position.
 */
function insert_game(array $game, ?int $position = null): void
{
    db_transaction(function (PDO $db) use ($game, $position) {
        if ($position === null) {
            $stmt = $db->prepare('SELECT MAX(position) FROM games WHERE console = ?');
            $stmt->execute([$game['console']]);
            $last = $stmt->fetchColumn();
            $position = $last === null || $last === false
                ? (int) $db->query('SELECT COALESCE(MAX(position), -1) + 1 FROM games')->fetchColumn()
                : (int) $last + 1;
            $db->prepare('UPDATE games SET position = position + 1 WHERE position >= ?')->execute([$position]);
        }

        $row = game_to_row($game) + ['position' => $position];
        $cols = array_keys($row);
        $db->prepare('INSERT INTO games (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')')
            ->execute(array_values($row));
    });
    touch_data();
}

/** Saves a copy that is already there; $oldId when its id changed (field_state follows by ON UPDATE CASCADE). */
function update_game(array $game, ?string $oldId = null): void
{
    $oldId ??= $game['id'];
    $row = game_to_row($game);
    $sets = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($row)));

    db_transaction(function (PDO $db) use ($row, $sets, $oldId, $game) {
        $db->prepare("UPDATE games SET $sets, updated_at = datetime('now') WHERE id = ?")
            ->execute([...array_values($row), $oldId]);

        if ($oldId !== $game['id']) {
            // a bundle that lists the copy by its old id, and the GTA checklist, follow it
            foreach ($db->query("SELECT id, contains_json FROM games WHERE contains_json LIKE " . $db->quote('%' . $oldId . '%'))->fetchAll() as $b) {
                $list = array_map(fn ($x) => $x === $oldId ? $game['id'] : $x, json_column($b['contains_json']));
                $db->prepare('UPDATE games SET contains_json = ? WHERE id = ?')->execute([to_json($list), $b['id']]);
            }
            foreach ($db->query("SELECT id, copies_json FROM gta_games WHERE copies_json LIKE " . $db->quote('%' . $oldId . '%'))->fetchAll() as $g) {
                $list = array_map(fn ($x) => $x === $oldId ? $game['id'] : $x, json_column($g['copies_json']));
                $db->prepare('UPDATE gta_games SET copies_json = ? WHERE id = ?')->execute([to_json($list), $g['id']]);
            }
        }
    });
    touch_data();
}

function delete_game(string $id): void
{
    db()->prepare('DELETE FROM games WHERE id = ?')->execute([$id]);
    touch_data();
}

/* ---------- field_state: which fields a sync wrote ---------- */

/** field => row, for one copy. */
function field_states(string $gameId): array
{
    $stmt = db()->prepare('SELECT * FROM field_state WHERE game_id = ?');
    $stmt->execute([$gameId]);

    return array_column($stmt->fetchAll(), null, 'field');
}

function set_field_state(string $gameId, string $field, string $source, mixed $syncedValue, ?bool $locked = null): void
{
    db()->prepare("
        INSERT INTO field_state (game_id, field, source, synced_value, synced_at, locked) VALUES (?, ?, ?, ?, datetime('now'), ?)
        ON CONFLICT(game_id, field) DO UPDATE SET source = excluded.source, synced_value = excluded.synced_value,
            synced_at = excluded.synced_at, locked = COALESCE(?, locked)
    ")->execute([$gameId, $field, $source, to_json($syncedValue), $locked === null ? 0 : (int) $locked, $locked === null ? null : (int) $locked]);
}

function lock_field(string $gameId, string $field, bool $locked): void
{
    db()->prepare('UPDATE field_state SET locked = ? WHERE game_id = ? AND field = ?')->execute([(int) $locked, $gameId, $field]);
}

/* ---------- counting, as the site counts ---------- */

/**
 * The collection's figures as the site shows them: a bundle is the packs in it,
 * not a game of its own; a copy on two consoles is one; hidden ones don't count.
 */
function collection_figures(array $games): array
{
    $visible = array_filter($games, fn ($g) => empty($g['hidden']));
    $inBundle = [];
    foreach ($visible as $g) {
        foreach ($g['contains'] ?? [] as $id) {
            $inBundle[$id] = true;
        }
    }

    $figures = ['games' => 0, 'physical' => 0, 'digital' => 0, 'consoles' => [], 'launchers' => [], 'families' => []];
    foreach ($visible as $g) {
        if (!empty($g['contains'])) {
            continue; // its packs are counted
        }
        $figures['games']++;
        $digital = ($g['format'] ?? '') === 'digital';
        $figures[$digital ? 'digital' : 'physical']++;
        $figures['consoles'][$g['console']] = ($figures['consoles'][$g['console']] ?? 0) + 1;
        $family = console_family($g['console']);
        $figures['families'][$family] = ($figures['families'][$family] ?? 0) + 1;
        if (!empty($g['launcher'])) {
            $figures['launchers'][$g['launcher']] = ($figures['launchers'][$g['launcher']] ?? 0) + 1;
        }
    }

    // in the site's console order
    $order = array_flip(array_keys(CONSOLES));
    uksort($figures['consoles'], fn ($a, $b) => ($order[$a] ?? 99) <=> ($order[$b] ?? 99));
    arsort($figures['launchers']);

    return $figures;
}
