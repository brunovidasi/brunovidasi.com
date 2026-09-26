<?php

/** Saving records from the admin's edit pages. */

/** Runs a posted Delete or Sync on a record's edit page; either ends the request. */
function handle_record_action(array $item, string $afterDelete, string $selfPage): void
{
    if (post('action') === 'delete') {
        delete_item($item);
        redirect($afterDelete);
    }

    if (post('action') === 'sync' && $item['id']) {
        // A 429 makes the client wait out a whole rate-limit window.
        set_time_limit(150);
        try {
            flash(sync_one_item($item));
        } catch (DiscogsException $e) {
            flash('Sync failed: ' . $e->getMessage(), 'error');
        } catch (Throwable $e) {
            error_log('Single-item sync failed: ' . $e);
            flash('Sync failed' . (is_debug() ? ': ' . $e->getMessage() : '. Check the log.'), 'error');
        }
        redirect($selfPage . '?id=' . $item['id']);
    }
}

/** A box set goes with the discs built from it, which mean nothing without it. */
function delete_item(array $item): void
{
    $discs = db()->prepare('DELETE FROM items WHERE parent_item_id = ?');
    $discs->execute([$item['id']]);
    db()->prepare('DELETE FROM items WHERE id = ?')->execute([$item['id']]);

    $count = $discs->rowCount();
    flash('Deleted "' . item_title($item) . '"' . ($count ? ' and its ' . $count . ' ' . plural($count, 'disc') : '') . '.');
}

/**
 * UPDATE items SET … for one record. $values are bound column => value;
 * $expressions are raw SQL assignments with their own parameters.
 */
function update_item(int $id, array $values, array $expressions = []): void
{
    $sets = array_map(fn ($column) => "$column = ?", array_keys($values));
    $params = array_values($values);

    foreach ($expressions as $sql => $expressionParams) {
        $sets[] = $sql;
        array_push($params, ...$expressionParams);
    }
    $sets[] = "updated_at = datetime('now')";

    db()->prepare('UPDATE items SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute([...$params, $id]);
}

/** The posted corrections to Discogs' facts; an empty box goes back to Discogs'. */
function posted_overrides(): array
{
    $values = [];
    foreach (array_keys(OVERRIDE_FIELDS) as $key) {
        $values[$key] = nullable(post($key));
    }

    return $values;
}

/**
 * A posted box that starts out filled with Discogs' value: left as Discogs has
 * it, it is stored empty, so the record keeps following Discogs.
 */
function posted_own_text(string $key, string $discogs): ?string
{
    $normalise = fn (string $text) => implode("\n", array_map('trim', preg_split(LINE_BREAK, trim($text))));
    $value = nullable(post($key));

    return $value !== null && $normalise($value) === $normalise($discogs) ? null : $value;
}

/** The selling form's boxes that start from Discogs' text, as posted: [column => own value or null]. */
function posted_sale_overrides(array $item): array
{
    $release = item_release($item);
    $discogs = [
        'manual_title'  => (string) $item['title'],
        'manual_artist' => (string) $item['artists_text'],
        'tracklist'     => tracklist_to_text(json_column($item['tracklist_json'] ?? null)),
    ];
    foreach (array_keys(OVERRIDE_BOXES) as $key) {
        $discogs[$key] = discogs_fallback_text($item, $release, $key, true);
    }

    $values = [];
    foreach ($discogs as $key => $text) {
        $values[$key] = posted_own_text($key, $text);
    }

    return $values;
}

/** The posted Format, falling back to $fallback for anything that isn't one. */
function posted_media_kind(string $fallback): string
{
    return isset(MEDIA_KINDS[post('media_kind')]) ? post('media_kind') : $fallback;
}

/** A format changed by hand is kept through a sync even without the box ticked. */
function posted_media_kind_locked(string $kind, string $was): int
{
    return post('media_kind_locked') !== '' || $kind !== $was ? 1 : 0;
}

function posted_flag(string $name): int
{
    return post($name) !== '' ? 1 : 0;
}

/**
 * The Discs section as posted: how many, and each disc's picture and colour.
 * A disc taken away takes its picture and colour with it. Only a vinyl's form
 * has colours, so any other kind keeps what is stored.
 *
 * @return array{config: ?string, first_art: ?string}
 */
function posted_disc_config(array $item, string $kind): array
{
    $countChoice = post('disc_count');
    $count = ctype_digit($countChoice) ? min(MAX_DISCS, max(1, (int) $countChoice)) : null;

    $art = [];
    $postedArt = is_array($_POST['disc_art'] ?? null) ? $_POST['disc_art'] : [];
    for ($k = 0; $k < MAX_DISCS; $k++) {
        $url = is_string($postedArt[$k] ?? null) ? trim($postedArt[$k]) : '';
        $art[] = safe_http_url($url) ? $url : '';
    }

    if ($kind === 'vinyl') {
        $postedHex = is_array($_POST['vinyl_hex'] ?? null) ? $_POST['vinyl_hex'] : [];
        $postedTr = is_array($_POST['vinyl_translucent'] ?? null) ? $_POST['vinyl_translucent'] : [];
        $hex = $tr = [];
        for ($k = 0; $k < MAX_DISCS; $k++) {
            $hex[] = vinyl_hex(is_string($postedHex[$k] ?? null) ? $postedHex[$k] : null);
            $tr[] = is_string($postedTr[$k] ?? null) ? optional_bool($postedTr[$k]) : null;
        }
    } else {
        ['hex' => $hex, 'tr' => $tr] = disc_colours($item);
    }

    if ($count !== null) {
        $art = array_pad(array_slice($art, 0, $count), MAX_DISCS, '');
        $hex = array_pad(array_slice($hex, 0, $count), MAX_DISCS, null);
        $tr = array_pad(array_slice($tr, 0, $count), MAX_DISCS, null);
    }

    $unset = $count === null && !array_filter($art) && !array_filter($hex) && !array_filter($tr, 'is_bool');

    return [
        'config'    => $unset ? null : json_encode(['count' => $count, 'art' => $art, 'hex' => $hex, 'tr' => $tr], JSON_UNESCAPED_SLASHES),
        'first_art' => array_values(array_filter($art))[0] ?? null,
    ];
}

/** Files every pressing of a record's album (its master, else the release) into an era. */
function file_album_into_era(array $item, int $eraId): void
{
    $useMaster = !empty($item['master_id']);
    save_era_rule(
        $eraId,
        $useMaster ? 'master' : 'release',
        $useMaster ? (int) $item['master_id'] : (int) $item['discogs_id'],
        $useMaster ? 500 : 1000
    );
    assign_items_to_artists_and_eras();
}
