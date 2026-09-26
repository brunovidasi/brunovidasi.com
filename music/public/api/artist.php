<?php

/**
 * One artist's page: their eras in order, then whatever isn't in an era yet,
 * then what is still wanted by them.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

/** Undated records go last. */
function release_order(array $row): string
{
    return item_sort_date($row) ?: '9999-99-99';
}

function artist_section(int $id, string $slug, string $name, string $years, string $tagline, array $items): array
{
    return ['id' => $id, 'slug' => $slug, 'name' => $name, 'years' => $years, 'tagline' => $tagline, 'items' => $items];
}

$artist = published_artist(query('slug'));

if ($artist === null) {
    json_response(['error' => 'No such artist page.'], 404);
}

$byArtist = ['i.artist_id = ?'];
$rows = public_items('collection', $byArtist, [$artist['id']]);

// Within an era: the order its rules were written (album, then singles), then release date, then title.
usort($rows, fn ($a, $b) =>
    [(int) $a['era_rank'], release_order($a), item_title($a)]
    <=> [(int) $b['era_rank'], release_order($b), item_title($b)]
);

$eras = eras_for_artist((int) $artist['id']);
$eraIds = array_column($eras, 'id');

// A record whose era belongs to another artist (its artist changed by hand) goes under "More".
$cardsByEra = [];
foreach ($rows as $row) {
    $cardsByEra[in_array($row['era_id'], $eraIds) ? $row['era_id'] : 0][] = item_card($row);
}

$sections = [];
foreach ($eras as $era) {
    if (!empty($cardsByEra[$era['id']])) {
        $sections[] = artist_section((int) $era['id'], $era['slug'], $era['name'], (string) $era['years'], (string) $era['tagline'], $cardsByEra[$era['id']]);
    }
}

// Records not in one of this artist's eras still show, at the end.
if (!empty($cardsByEra[0])) {
    $sections[] = artist_section(0, 'more', 'More ' . $artist['name'], '', 'Not sorted into an era yet', $cardsByEra[0]);
}

$wanted = [];
if (setting('show_wantlist', true)) {
    $missing = [
        ...public_items('wantlist', $byArtist, [$artist['id']]),
        ...public_items('searching', $byArtist, [$artist['id']]),
    ];
    usort($missing, fn ($a, $b) => [release_order($a), item_title($a)] <=> [release_order($b), item_title($b)]);
    $wanted = array_map('item_card', $missing);
}

json_cache_headers();

json_response([
    'artist' => [
        'slug'    => $artist['slug'],
        'name'    => $artist['name'],
        'tagline' => (string) $artist['tagline'],
        'intro'   => (string) $artist['intro'],
        'accent'  => (string) ($artist['accent'] ?: '#C99A2E'),
    ],
    'count'    => count($rows),
    'sections' => $sections,
    'wanted'   => $wanted,
    'updated'  => setting('last_successful_sync'),
]);
