<?php
/**
 * Applies an artist's hand-kept lists to the database: the eras in the order the
 * lists have them, and for each copy the list matched — its own fields, its
 * notes, and its place inside its era.
 *
 *   php cron/apply_lists.php cron/lady-gaga-lists.json [--dry-run]
 *
 * The file is made by comparing the lists with the collection, and copies are
 * found by Discogs instance_id, which is the same in every database synced from
 * the same account (the row id is not). Safe to run again: it writes what is
 * missing and reports the rest.
 *
 * A list can also name "discs": CDs and DVDs from a box set that Discogs holds
 * only as the box. Each is made here as a record of its own (title, cover,
 * tracklist and the rest copied from the box's release into the fields the
 * admin overrides), filed in its era and tied to its box by parent_item_id. It
 * has no instance_id, which is also what keeps a sync from flagging it as gone,
 * and is found again on later runs by its box and barcode.
 *
 * Nothing already typed on a record is overwritten (a different value is listed
 * under "kept" so it can be looked at), and notes are added to, never replaced.
 * Every copy the file names is pinned to its era (era_locked), because a sync
 * would otherwise put the order back to album-then-singles.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/runtime.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/fields.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is for the command line.\n");
}

date_default_timezone_set(app_timezone());
configure_error_reporting();

$args = array_slice($argv, 1);
$dryRun = in_array('--dry-run', $args, true);
$path = current(array_filter($args, fn ($arg) => !str_starts_with($arg, '--')));

if (!$path || !is_file($path)) {
    fwrite(STDERR, "Usage: php cron/apply_lists.php <lists.json> [--dry-run]\n");
    exit(1);
}

$plan = json_decode((string) file_get_contents($path), true);
if (!is_array($plan) || empty($plan['artist']) || !isset($plan['eras'], $plan['items'])) {
    fwrite(STDERR, "That file is not a lists file.\n");
    exit(1);
}

$db = db();
$artist = $db->prepare('SELECT id, name FROM artists WHERE slug = ?');
$artist->execute([$plan['artist']]);
$artist = $artist->fetch();
if (!$artist) {
    fwrite(STDERR, "No artist page with the slug \"{$plan['artist']}\".\n");
    exit(1);
}
$artistId = (int) $artist['id'];

$db->beginTransaction();

/* ---------- Eras, in the order of the lists ---------- */

$findEra = $db->prepare('SELECT id FROM eras WHERE artist_id = ? AND slug = ?');
$eraIds = [];
$erasMade = $erasChanged = 0;

foreach ($plan['eras'] as $position => $era) {
    $findEra->execute([$artistId, $era['slug']]);
    $id = $findEra->fetchColumn();

    // An era that is really an older one under a new name keeps its row, so
    // everything already filed in it comes along.
    if (!$id && !empty($era['replaces'])) {
        $findEra->execute([$artistId, $era['replaces']]);
        $id = $findEra->fetchColumn();
    }

    if ($id) {
        $current = $db->query('SELECT slug, name, years, tagline, position FROM eras WHERE id = ' . (int) $id)->fetch();
        $next = ['slug' => $era['slug'], 'name' => $era['name'], 'years' => $era['years'], 'tagline' => $era['tagline'], 'position' => $position];
        if ($current != $next) {
            $db->prepare('UPDATE eras SET slug = ?, name = ?, years = ?, tagline = ?, position = ? WHERE id = ?')
               ->execute([...array_values($next), $id]);
            $erasChanged++;
        }
    } else {
        $db->prepare('INSERT INTO eras (artist_id, slug, name, years, tagline, position) VALUES (?, ?, ?, ?, ?, ?)')
           ->execute([$artistId, $era['slug'], $era['name'], $era['years'], $era['tagline'], $position]);
        $id = $db->lastInsertId();
        $erasMade++;
    }
    $eraIds[$era['slug']] = (int) $id;
}

/* ---------- Rules, so a later sync files new pressings the same way ---------- */

$rule = $db->prepare("
    INSERT INTO era_rules (era_id, kind, discogs_id, rank) VALUES (?, 'master', ?, ?)
    ON CONFLICT(kind, discogs_id) DO UPDATE SET era_id = excluded.era_id, rank = excluded.rank
");
foreach ($plan['rules'] ?? [] as $r) {
    $rule->execute([$eraIds[$r['era']], $r['master'], $r['rank']]);
}

/* ---------- The copies ---------- */

$find = $db->prepare('
    SELECT i.*, r.labels_json
      FROM items i LEFT JOIN releases r ON r.discogs_id = i.release_id
     WHERE i.instance_id = ?
');

$findBox = $db->prepare('SELECT id, date_added FROM items WHERE instance_id = ?');
$findDisc = $db->prepare('
    SELECT i.*, r.labels_json
      FROM items i LEFT JOIN releases r ON r.discogs_id = i.release_id
     WHERE i.parent_item_id = ? AND i.barcode = ?
');
$addDisc = $db->prepare("
    INSERT INTO items (source, media_kind, artist_id, artist_locked, date_added, parent_item_id, barcode)
    VALUES ('collection', ?, ?, 1, ?, ?, ?)
");

$updated = $unchanged = $discsMade = 0;
$missing = $kept = [];

foreach ([...$plan['items'], ...($plan['discs'] ?? [])] as $entry) {
    if (isset($entry['box'])) {
        $findBox->execute([$entry['box']]);
        $box = $findBox->fetch();
        $item = false;
        if ($box) {
            $findDisc->execute([$box['id'], $entry['fields']['barcode']]);
            $item = $findDisc->fetch();
            if (!$item) {
                $addDisc->execute([$entry['media_kind'], $artistId, $box['date_added'], $box['id'], $entry['fields']['barcode']]);
                $findDisc->execute([$box['id'], $entry['fields']['barcode']]);
                $item = $findDisc->fetch();
                $discsMade++;
            }
        }
    } else {
        $find->execute([$entry['instance_id']]);
        $item = $find->fetch();
    }
    if (!$item) {
        $missing[] = $entry['label'];
        continue;
    }

    $set = [
        'era_id' => $eraIds[$entry['era']],
        'era_locked' => 1,
        'era_rank' => (int) $entry['rank'],
    ];

    // Other artists' pages (a soundtrack credited to "Various") are filed by hand.
    if ((int) $item['artist_id'] !== $artistId) {
        $set['artist_id'] = $artistId;
        $set['artist_locked'] = 1;
    }

    foreach ($entry['fields'] as $key => $listed) {
        $typed = trim((string) ($item[$key] ?? ''));
        $write = $listed;
        $current = $typed;

        if ($key === 'catalog_number') {
            // The number on the disc leads; Discogs' own stay behind it, since
            // typing this box replaces them.
            $labels = json_column($item['labels_json'] ?? null);
            $write = implode("\n", array_unique([$listed, ...catalog_numbers($labels)]));
            $current = (string) strtok($typed, "\n");
            // A box disc's own catalogue number can follow the listed one.
            $listed = (string) strtok($listed, "\n");
        }

        if ($typed === '') {
            $set[$key] = $write;
        } elseif ($current !== $listed) {
            $kept[] = "{$entry['label']}: $key is \"$current\", the list says \"$listed\"";
        }
    }

    if (!empty($entry['notes'])) {
        $notes = trim((string) $item['notes']);
        if (!str_contains($notes, $entry['notes'])) {
            $set['notes'] = $notes === '' ? $entry['notes'] : $notes . "\n" . $entry['notes'];
        }
    }

    $changed = array_filter($set, fn ($value, $key) => (string) $item[$key] !== (string) $value, ARRAY_FILTER_USE_BOTH);
    if (!$changed) {
        $unchanged++;
        continue;
    }

    $columns = implode(', ', array_map(fn ($key) => "$key = ?", array_keys($changed)));
    $db->prepare("UPDATE items SET $columns, updated_at = datetime('now') WHERE id = ?")
       ->execute([...array_values($changed), $item['id']]);
    $updated++;
}

$dryRun ? $db->rollBack() : $db->commit();

echo ($dryRun ? "DRY RUN, nothing was written.\n" : '');
echo "Eras: $erasMade added, $erasChanged changed.\n";
echo "Copies: $updated updated, $unchanged already up to date, " . count($missing) . " not in this database" . ($discsMade ? ", $discsMade box discs added" : '') . ".\n";
foreach ($missing as $label) {
    echo "  not found: $label\n";
}
if ($kept) {
    echo 'Kept what was already typed on the site (' . count($kept) . "):\n";
    foreach ($kept as $line) {
        echo "  $line\n";
    }
}
