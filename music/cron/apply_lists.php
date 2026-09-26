<?php

/**
 * Applies an artist's hand-kept lists: the eras in the lists' order, and for
 * each copy its own fields, notes and place in its era.
 *
 *   php cron/apply_lists.php data/lists/lady-gaga-lists.json [--dry-run]
 *
 * Copies are found by Discogs instance_id, the same in every database synced
 * from one account. A list's "discs" are the CDs and DVDs of a box set Discogs
 * only knows as the box: each becomes a record of its own, tied to its box by
 * parent_item_id and found again by its box and barcode.
 *
 * Safe to run again. Nothing typed on a record is overwritten (a different
 * value is reported under "kept"), notes are only added to, and every copy is
 * pinned to its era so a sync doesn't reorder it.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is for the command line.\n");
}

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
$artist = artist_by_slug($plan['artist']);
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

    // An old era under a new name keeps its row, and everything filed in it.
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
        // Text saved from the admin's boxes has \r\n line endings; the lists have \n.
        $typed = trim(preg_replace('/\r\n?/', "\n", (string) ($item[$key] ?? '')));
        $write = $listed;
        $current = $typed;

        if ($key === 'catalog_number') {
            // The number on the disc leads; Discogs' own follow, since typing the box replaces them.
            $labels = json_column($item['labels_json'] ?? null);
            $write = implode("\n", array_unique([$listed, ...catalog_numbers($labels)]));
            $current = strtok($typed, "\n");
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
