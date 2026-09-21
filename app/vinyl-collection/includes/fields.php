<?php

/**
 * What a record "has", in one place.
 *
 * Three things read this file and must agree with each other:
 *   - the admin's edit form (which boxes to show, in which order),
 *   - the drawer on the public site (which facts to print),
 *   - /admin_fields, where Bruno ticks which of those facts the drawer shows.
 *
 * A field is either MINE (typed in the admin, stored on the items row) or
 * DISCOGS' (derived from the release cache, never stored twice). Mine win: a
 * blank one falls back to the Discogs value, so filling one in is an override
 * and clearing it goes back to whatever Discogs says.
 */

const MEDIA_KINDS = [
    'vinyl'  => 'Vinyl',
    'cd'     => 'CD',
    'dvd'    => 'DVD',
    'bluray' => 'Blu-ray',
    'other'  => 'Other',
];

function media_kind_label(string $kind): string
{
    return MEDIA_KINDS[$kind] ?? 'Other';
}

/**
 * Which shelf a release belongs on, from its Discogs formats.
 *
 * A release with more than one format (the "+ DVD" deluxe editions, the box
 * sets) is filed under the one you'd actually reach for, so the order of these
 * checks matters: vinyl over CD, CD over the bonus disc that came with it.
 */
function detect_media_kind(array $formats): string
{
    $names = array_map(fn ($f) => (string) ($f['name'] ?? ''), $formats);

    if (in_array('Vinyl', $names, true)) {
        return 'vinyl';
    }
    // CDr is nearly always a promo; it lives with the CDs.
    if (array_intersect(['CD', 'CDr'], $names)) {
        return 'cd';
    }
    if (in_array('Blu-ray', $names, true) || in_array('Blu-ray-R', $names, true)) {
        return 'bluray';
    }
    if (in_array('DVD', $names, true) || in_array('DVDr', $names, true)) {
        return 'dvd';
    }

    return 'other';
}

/** The item types offered in the admin's Type box. */
const ITEM_TYPES = ['album', 'single', 'ep', 'promo', 'compilation', 'live', 'other'];

/**
 * The choices for the Type box: the usual ones, plus whatever the record has
 * already ("remixes", "usb album" from a list), so saving the form doesn't drop it.
 */
function item_type_options(?string $current): array
{
    $current = trim((string) $current);

    return $current !== '' && !in_array($current, ITEM_TYPES, true) ? [...ITEM_TYPES, $current] : ITEM_TYPES;
}

/** Sizes offered for vinyl. Free text is still accepted — these are shortcuts. */
const VINYL_SIZES = ['7"', '10"', '12"', '5"'];

/**
 * Discogs' facts that a record can override, and the shape each is typed in.
 * The value is stored on the item under the same name, so item_field_value()
 * finds it with Bruno's other fields and a sync, which only writes the release
 * cache, never touches it. (Title and artist have their own boxes:
 * manual_title and manual_artist.)
 *
 *   lines  — one per line, the way the drawer prints them
 *   list   — separated by commas or new lines ("Pop, Rock")
 *   tracks — the tracklist, one track per line (see tracklist_from_text())
 */
const OVERRIDE_FIELDS = [
    'labels'         => 'lines',
    'catalog_number' => 'lines',
    'formats'        => 'lines',
    'genres'         => 'list',
    'styles'         => 'list',
    'tracklist'      => 'tracks',
];

/**
 * Every field the drawer can show, in the order it would show them.
 *
 * group:  'mine'    — stored on items, edited in the admin
 *         'discogs' — derived from the release cache
 * kinds:  null for all, or the media kinds it makes sense for
 * default:whether a fresh install shows it in the drawer
 * type:   how the value is rendered ('text', 'lines', 'tags', 'html', 'list',
 *         'tracklist', 'credits', 'links', 'gallery')
 */
function field_catalog(): array
{
    static $catalog = null;
    if ($catalog !== null) {
        return $catalog;
    }

    return $catalog = [
        // ---- Mine. These are the ones on top of the edit form. ----
        'barcode'      => ['label' => 'Barcode',        'group' => 'mine', 'kinds' => null,                 'default' => true,  'type' => 'text'],
        'release_date' => ['label' => 'Release date',   'group' => 'mine', 'kinds' => null,                 'default' => true,  'type' => 'text'],
        'region'       => ['label' => 'Region',         'group' => 'mine', 'kinds' => null,                 'default' => true,  'type' => 'text'],
        'media'        => ['label' => 'Media',          'group' => 'mine', 'kinds' => ['cd', 'dvd', 'bluray', 'other'], 'default' => true, 'type' => 'text'],
        'vinyl_size'   => ['label' => 'Size',           'group' => 'mine', 'kinds' => ['vinyl'],            'default' => true,  'type' => 'text'],
        'vinyl_color'  => ['label' => 'Colour / variant', 'group' => 'mine', 'kinds' => ['vinyl'],          'default' => true,  'type' => 'text'],
        'item_type'    => ['label' => 'Type',           'group' => 'mine', 'kinds' => null,                 'default' => true,  'type' => 'text'],
        'notes'        => ['label' => 'Notes',          'group' => 'mine', 'kinds' => null,                 'default' => true,  'type' => 'html'],

        // ---- Discogs'. Everything below here is refreshed by every sync. The
        //      ones in OVERRIDE_FIELDS can be corrected on a record, and what was
        //      typed is shown in place of Discogs' and kept through every sync. ----
        'artist'          => ['label' => 'Artist',          'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'text'],
        'year'            => ['label' => 'Year',            'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'text'],
        'country'         => ['label' => 'Country',         'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'text'],
        'labels'          => ['label' => 'Label',           'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'lines'],
        'catalog_number'  => ['label' => 'Catalogue no.',   'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'lines'],
        'formats'         => ['label' => 'Format',          'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'lines'],
        'genres'          => ['label' => 'Genres',          'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'tags'],
        'styles'          => ['label' => 'Styles',          'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'tags'],
        'date_added'      => ['label' => 'Added',           'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'text'],
        'my_rating'       => ['label' => 'My rating',       'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'text'],
        'estimated_weight'=> ['label' => 'Weight',          'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'text'],
        'data_quality'    => ['label' => 'Data quality',    'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'text'],
        'release_id'      => ['label' => 'Release ID',      'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'text'],
        'community'       => ['label' => 'Community',       'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'lines'],
        'marketplace'     => ['label' => 'Marketplace',     'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'lines'],

        // Sections rather than one-line facts.
        'gallery'       => ['label' => 'Image gallery',   'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'gallery'],
        'tracklist'     => ['label' => 'Tracklist',       'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'tracklist'],
        'credits'       => ['label' => 'Credits',         'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'credits'],
        'companies'     => ['label' => 'Companies',       'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'credits'],
        'identifiers'   => ['label' => 'Identifiers',     'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'credits'],
        'series'        => ['label' => 'Series',          'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'lines'],
        'release_notes' => ['label' => 'Discogs notes',   'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'html'],
        'videos'        => ['label' => 'Videos',          'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'links'],
        'links'         => ['label' => 'Discogs links',   'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'links'],
    ];
}

/** The fields that apply to one media kind, keys only, in catalog order. */
function fields_for_kind(string $kind): array
{
    $keys = [];
    foreach (field_catalog() as $key => $def) {
        if ($def['kinds'] === null || in_array($kind, $def['kinds'], true)) {
            $keys[] = $key;
        }
    }
    return $keys;
}

/**
 * The boxes at the top of the edit form — the ones Bruno actually fills in —
 * per media kind, in the order he listed them. Everything else on the form sits
 * below, under "More".
 */
function primary_fields_for_kind(string $kind): array
{
    return $kind === 'vinyl'
        ? ['barcode', 'release_date', 'region', 'vinyl_size', 'vinyl_color', 'notes']
        : ['barcode', 'release_date', 'region', 'media', 'item_type', 'notes'];
}

/** The rest of the editable fields for a kind: shown below the primary ones. */
function secondary_fields_for_kind(string $kind): array
{
    $primary = primary_fields_for_kind($kind);
    return array_values(array_filter(
        fields_for_kind($kind),
        fn ($key) => field_catalog()[$key]['group'] === 'mine' && !in_array($key, $primary, true)
    ));
}

/**
 * Which fields the drawer shows, per media kind — the /admin_fields screen.
 * Stored as { kind: { field: bool } }; anything unsaved falls back to the
 * catalog's default, so a newly added field appears without a migration.
 */
function drawer_field_config(): array
{
    $saved = setting('drawer_fields', []);
    $config = [];

    foreach (array_keys(MEDIA_KINDS) as $kind) {
        foreach (fields_for_kind($kind) as $key) {
            $config[$kind][$key] = array_key_exists($key, $saved[$kind] ?? [])
                ? (bool) $saved[$kind][$key]
                : field_catalog()[$key]['default'];
        }
    }

    return $config;
}

function drawer_shows(string $kind, string $key): bool
{
    static $config = null;
    $config ??= drawer_field_config();

    return $config[$kind][$key] ?? false;
}

/* ---------- Overriding Discogs' values ---------- */

/**
 * A line break in typed text. Not \R: without the /u flag that also matches the
 * single byte 0x85, which ends characters like "★" and many Japanese ones, and
 * would cut a title in half.
 */
const LINE_BREAK = '/\r\n|\r|\n/';

/** What was typed into an override box, as the list of values the drawer draws. */
function override_value(string $key, string $text): array
{
    return match (OVERRIDE_FIELDS[$key]) {
        'tracks' => tracklist_from_text($text),
        'list'   => array_values(array_filter(array_map('trim', preg_split('/[,\n]/', $text)))),
        default  => array_values(array_filter(array_map('trim', preg_split(LINE_BREAK, $text)))),
    };
}

/** A position simple enough to type as "1." or "A2)": 7, 12, A1, 3b. */
const TRACK_POSITION = '[A-Za-z]?\\d{1,3}[A-Za-z]?';

/** A record's own value for an overridable fact, or null where it hasn't set one. */
function item_override(array $row, string $key): ?array
{
    $text = trim((string) ($row[$key] ?? ''));

    return $text !== '' ? override_value($key, $text) : null;
}

/**
 * The tracklist as typed: one track a line, "1. Poker Face 3:58". The leading
 * position and the trailing length are optional; a line with neither is just a
 * title, numbered by where it falls. A plain position is written "1." or "A2)";
 * anything Discogs might have ("Video", "10.1", "CD1-3") goes in brackets:
 * "[Video] Beautiful".
 */
function tracklist_from_text(string $text): array
{
    $tracks = [];

    foreach (preg_split(LINE_BREAK, $text) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $position = '';
        if (preg_match('/^(?:\[([^\]]{1,20})\]|(' . TRACK_POSITION . ')[.)])\s+(.+)$/', $line, $m)) {
            [$position, $line] = [$m[1] !== '' ? $m[1] : $m[2], $m[3]];
        }

        $duration = '';
        if (preg_match('/^(.*\S)\s+(\d{1,2}:\d{2})$/', $line, $m)) {
            [$line, $duration] = [$m[1], $m[2]];
        }

        $tracks[] = [
            'position' => $position !== '' ? $position : (string) (count($tracks) + 1),
            'type_'    => 'track',
            'title'    => $line,
            'duration' => $duration,
        ];
    }

    return $tracks;
}

/** Discogs' tracklist in the same shape, as a starting point to correct. */
function tracklist_to_text(array $tracks): string
{
    $lines = [];

    foreach ($tracks as $track) {
        if (($track['type_'] ?? 'track') === 'heading') {
            continue;
        }
        // An index track carries its parts as sub-tracks; those are the songs.
        foreach (($track['sub_tracks'] ?? null) ?: [$track] as $part) {
            $title = trim((string) ($part['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $position = trim((string) ($part['position'] ?? ''));
            $duration = trim((string) ($part['duration'] ?? ''));
            $prefix = match (true) {
                $position === '' => '',
                (bool) preg_match('/^' . TRACK_POSITION . '$/', $position) => "$position. ",
                default => "[$position] ",
            };
            $lines[] = $prefix . $title . ($duration !== '' ? " $duration" : '');
        }
    }

    return implode("\n", $lines);
}

/* ---------- Deriving Discogs values ---------- */

function clean_artist_name(string $name): string
{
    // Discogs disambiguates same-named artists with a numeric suffix: "Anitta (2)".
    return trim(preg_replace('/\s\(\d+\)$/', '', $name));
}

function artists_text(array $artists): string
{
    $names = array_map(fn ($a) => clean_artist_name((string) ($a['name'] ?? '')), $artists);
    $names = array_filter($names, fn ($n) => $n !== '');

    return $names ? implode(', ', $names) : 'Unknown artist';
}

/** "LP, Album, Limited Edition, Pink" — one flat line of every format detail. */
function formats_text(array $formats): string
{
    $parts = [];
    foreach ($formats as $format) {
        $qty = (int) ($format['qty'] ?? 1);
        $name = (string) ($format['name'] ?? '');
        $parts[] = $qty > 1 ? "$qty × $name" : $name;
        foreach (($format['descriptions'] ?? []) as $description) {
            $parts[] = $description;
        }
        if (trim((string) ($format['text'] ?? '')) !== '') {
            $parts[] = trim($format['text']);
        }
    }

    return implode(', ', array_unique(array_filter($parts)));
}

/** One line per format, the way the drawer prints them. */
function formats_lines(array $formats): array
{
    $lines = [];
    foreach ($formats as $format) {
        $qty = (int) ($format['qty'] ?? 1) > 1 ? ((int) $format['qty']) . ' × ' : '';
        $extras = array_filter(array_merge(
            [trim((string) ($format['text'] ?? ''))],
            $format['descriptions'] ?? []
        ));
        $lines[] = $qty . ($format['name'] ?? '') . ($extras ? ' (' . implode(', ', $extras) . ')' : '');
    }
    return $lines;
}

function labels_lines(array $labels): array
{
    return array_map(
        fn ($l) => clean_artist_name((string) ($l['name'] ?? '')),
        $labels
    );
}

function catalog_numbers(array $labels): array
{
    $numbers = [];
    foreach ($labels as $label) {
        $catno = trim((string) ($label['catno'] ?? ''));
        if ($catno !== '' && strtolower($catno) !== 'none') {
            $numbers[] = $catno;
        }
    }
    return array_values(array_unique($numbers));
}

/** The barcode off a release's identifiers, digits only where there are any. */
function identifiers_barcode(array $identifiers): ?string
{
    foreach ($identifiers as $identifier) {
        if (strcasecmp((string) ($identifier['type'] ?? ''), 'Barcode') !== 0) {
            continue;
        }
        $value = trim((string) ($identifier['value'] ?? ''));
        if ($value === '') {
            continue;
        }
        // Discogs stores both "6 02537 51737 4" and "602537517374"; the spaced
        // form is how it's printed on the sleeve, the bare digits are what you
        // would search for. Prefer the digits when they're there.
        $digits = preg_replace('/\D+/', '', $value);
        return strlen($digits) >= 8 ? $digits : $value;
    }

    return null;
}

/** Vinyl size ("12\"") out of the format descriptions. */
function formats_vinyl_size(array $formats): ?string
{
    foreach ($formats as $format) {
        if (($format['name'] ?? '') !== 'Vinyl') {
            continue;
        }
        foreach (($format['descriptions'] ?? []) as $description) {
            if (preg_match('/^(\d+)"$/', trim($description), $m)) {
                return $m[1] . '"';
            }
        }
    }
    return null;
}

/**
 * The colour of a pressing, as Discogs records it: free text on the format
 * ("Pink", "Clear w/ Powder Fill"), sometimes only in the descriptions
 * ("Picture Disc").
 */
function formats_vinyl_color(array $formats): ?string
{
    foreach ($formats as $format) {
        if (($format['name'] ?? '') !== 'Vinyl') {
            continue;
        }
        $text = trim((string) ($format['text'] ?? ''));
        if ($text !== '') {
            return $text;
        }
        foreach (($format['descriptions'] ?? []) as $description) {
            if (in_array($description, ['Picture Disc', 'Etched', 'Flexi-disc'], true)) {
                return $description;
            }
        }
    }
    return null;
}

/** The format name to put in the Media box for a disc: "CD", "DVD-Video"… */
function formats_media(array $formats): ?string
{
    foreach ($formats as $format) {
        $name = (string) ($format['name'] ?? '');
        if ($name === '' || $name === 'Box Set') {
            continue;
        }
        $descriptions = array_values(array_filter(
            $format['descriptions'] ?? [],
            fn ($d) => in_array($d, ['DVD-Video', 'DVD-Audio', 'CD-ROM', 'Enhanced', 'Mini', 'Maxi-Single', 'Single', 'Album', 'EP'], true)
        ));
        return $descriptions && in_array($descriptions[0], ['DVD-Video', 'DVD-Audio', 'CD-ROM'], true)
            ? $descriptions[0]
            : $name;
    }
    return null;
}

/**
 * Album / single / promo, guessed from the Discogs format descriptions. Only
 * ever a default: the moment Bruno picks one in the admin, his choice is stored
 * on the item and this is not consulted again.
 */
function formats_item_type(array $formats): ?string
{
    $descriptions = [];
    foreach ($formats as $format) {
        $descriptions = array_merge($descriptions, $format['descriptions'] ?? []);
    }

    if (array_intersect($descriptions, ['Promo', 'Promotional'])) {
        return 'promo';
    }
    if (array_intersect($descriptions, ['Single', 'Maxi-Single'])) {
        return 'single';
    }
    if (in_array('EP', $descriptions, true)) {
        return 'ep';
    }
    if (in_array('Compilation', $descriptions, true)) {
        return 'compilation';
    }
    if (in_array('Album', $descriptions, true)) {
        return 'album';
    }

    return null;
}

/**
 * Release notes on Discogs are written in its own markup ([a=Artist],
 * [url=…]text[/url], [b]…[/b]). Flatten it to plain text — the drawer escapes
 * whatever comes out, so none of it can smuggle in HTML.
 */
function clean_discogs_markup(?string $text): string
{
    return trim(preg_replace(
        ['/\[url=[^\]]*\]([\s\S]*?)\[\/url\]/', '/\[[almr]=([^\]]+)\]/', '/\[\/?[a-z]\]/'],
        ['$1', '$1', ''],
        (string) $text
    ));
}

/**
 * The value of one field for one item: Bruno's if he typed one, otherwise
 * whatever Discogs says. Returns null when neither has anything, so the caller
 * can leave the row out entirely rather than print an empty one.
 *
 * $item is an items row, $release a releases row (or null for a 'searching'
 * entry that isn't on Discogs at all).
 */
function item_field_value(array $item, ?array $release, string $key): mixed
{
    // The year follows a release date typed in the admin, so the two never disagree.
    if ($key === 'year') {
        return item_year($item) ?: null;
    }

    $mine = trim((string) ($item[$key] ?? ''));
    if ($mine !== '') {
        return isset(OVERRIDE_FIELDS[$key]) ? override_value($key, $mine) : $mine;
    }

    if ($release === null) {
        return null;
    }

    $formats = json_column($release['formats_json'] ?? null);
    $labels = json_column($release['labels_json'] ?? null);

    return match ($key) {
        // Mine, falling back to Discogs.
        'barcode'      => $release['barcode'] ?: identifiers_barcode(json_column($release['identifiers_json'] ?? null)),
        'release_date' => $release['released_formatted'] ?: ($release['released'] ?: ($release['year'] ?: null)),
        'region'       => $release['country'] ?: null,
        'media'        => formats_media($formats),
        'vinyl_size'   => formats_vinyl_size($formats),
        'vinyl_color'  => formats_vinyl_color($formats),
        'item_type'    => formats_item_type($formats),
        'notes'        => null,   // notes are Bruno's alone; Discogs' live under release_notes

        // Discogs only.
        'artist'           => $release['artists_text'] ?: null,
        'year'             => $release['year'] ?: null,
        'country'          => $release['country'] ?: null,
        'labels'           => labels_lines($labels) ?: null,
        'catalog_number'   => catalog_numbers($labels) ?: null,
        'formats'          => formats_lines($formats) ?: null,
        'genres'           => json_column($release['genres_json'] ?? null) ?: null,
        'styles'           => json_column($release['styles_json'] ?? null) ?: null,
        'date_added'       => $item['date_added'] ?: null,
        'my_rating'        => $item['rating'] ? (int) $item['rating'] : null,
        'estimated_weight' => $release['estimated_weight'] ? $release['estimated_weight'] . ' g' : null,
        'data_quality'     => $release['data_quality'] ?: null,
        'release_id'       => $release['discogs_id'] ?? null,
        'release_notes'    => clean_discogs_markup($release['release_notes'] ?? '') ?: null,
        default            => null,
    };
}
