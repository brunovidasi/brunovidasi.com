<?php
/**
 * One artist's page: their eras in order, each split by format.
 *
 * The split is done here rather than in the browser so the page can render an
 * era's headings and counts without walking the whole collection first, and so
 * "More <artist>" — everything not filed into an era yet — is a real section
 * with a position rather than a special case in the JavaScript.
 */

require_once __DIR__ . '/../../includes/bootstrap_api.php';
require_once __DIR__ . '/../../includes/helpers.php';

$artist = artist_by_slug(query('slug'));

if ($artist === null || !$artist['is_published']) {
    json_response(['error' => 'No such artist page.'], 404);
}

$rows = public_items('collection', ['i.artist_id = ?'], [$artist['id']]);

// Within an era: the order the era's rules were written (album, then singles),
// then oldest first by full release date, then by title — the order the
// hand-built page used, with the day and month now breaking a year's ties.
usort($rows, fn ($a, $b) =>
    [(int) $a['era_rank'], item_sort_date($a) ?: '9999-99-99', item_title($a)]
    <=> [(int) $b['era_rank'], item_sort_date($b) ?: '9999-99-99', item_title($b)]
);

$grouped = [];
foreach ($rows as $row) {
    $grouped[$row['era_id'] ?? 0][] = item_card($row);
}

$sections = [];
foreach (eras_for_artist((int) $artist['id']) as $era) {
    if (empty($grouped[$era['id']])) {
        continue;
    }
    $sections[] = [
        'id'      => (int) $era['id'],
        'slug'    => $era['slug'],
        'name'    => $era['name'],
        'years'   => (string) $era['years'],
        'tagline' => (string) $era['tagline'],
        'items'   => $grouped[$era['id']],
    ];
}

// Anything not mapped to an era still shows, at the end, so a new purchase is
// never invisible while waiting to be filed.
if (!empty($grouped[0])) {
    $sections[] = [
        'id'      => 0,
        'slug'    => 'more',
        'name'    => 'More ' . $artist['name'],
        'years'   => '',
        'tagline' => 'Not sorted into an era yet',
        'items'   => $grouped[0],
    ];
}

// What's still missing for this artist, for the end of the page: the Discogs
// wantlist and the records being hunted, together, oldest first. Left out
// entirely when the wantlist isn't public.
$wanted = [];
if (setting('show_wantlist', true)) {
    $missing = [...public_items('wantlist', ['i.artist_id = ?'], [$artist['id']]),
                ...public_items('searching', ['i.artist_id = ?'], [$artist['id']])];

    usort($missing, fn ($a, $b) =>
        [item_sort_date($a) ?: '9999-99-99', item_title($a)]
        <=> [item_sort_date($b) ?: '9999-99-99', item_title($b)]
    );

    $wanted = array_map('item_card', $missing);
}

json_cache_headers(300);

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
