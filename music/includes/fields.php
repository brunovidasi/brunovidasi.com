<?php

/**
 * What a record "has". The admin's edit form, the public drawer and the
 * /admin_fields screen all read this, so they agree with each other.
 *
 * A field is either Bruno's own (typed in the admin, stored on the item) or
 * Discogs' (read from the release cache). His win: a blank one falls back to
 * the Discogs value, so filling one in is an override and clearing it goes
 * back to what Discogs says.
 */

const MEDIA_KINDS = [
    'vinyl'  => 'Vinyl',
    'cd'     => 'CD',
    'dvd'    => 'DVD',
    'bluray' => 'Blu-ray',
    'other'  => 'Other',
];

const ITEM_TYPES = ['album', 'single', 'ep', 'promo', 'compilation', 'live', 'other'];

/** Offered for vinyl; free text is still accepted. */
const VINYL_SIZES = ['7"', '10"', '12"', '5"'];

/**
 * Discogs' facts a record can correct, stored on the item under the same name,
 * and how each is typed:
 *   lines  — one per line
 *   list   — separated by commas or new lines
 *   tracks — one track per line (see tracklist_from_text())
 */
const OVERRIDE_FIELDS = [
    'labels'         => 'lines',
    'catalog_number' => 'lines',
    'formats'        => 'lines',
    'genres'         => 'list',
    'styles'         => 'list',
    'tracklist'      => 'tracks',
];

/** The correction boxes on the edit forms, apart from the tracklist which has its own. */
const OVERRIDE_BOXES = [
    'labels'         => ['label' => 'Label',          'help' => 'One per line.'],
    'catalog_number' => ['label' => 'Catalogue no.',  'help' => 'One per line.'],
    'formats'        => ['label' => 'Format details', 'help' => 'What the list shows under Details, one per line: "LP", "Album", "Pink".'],
    'genres'         => ['label' => 'Genres',         'help' => 'Separated by commas.'],
    'styles'         => ['label' => 'Styles',         'help' => 'Separated by commas.'],
];

/** Without \R, which lacks /u and would also match the byte 0x85 inside characters like "★". */
const LINE_BREAK = '/\r\n|\r|\n/';

/** A track position simple enough to type as "1." or "A2)". */
const TRACK_POSITION = '[A-Za-z]?\\d{1,3}[A-Za-z]?';

function media_kind_label(string $kind): string
{
    return MEDIA_KINDS[$kind] ?? 'Other';
}

/**
 * Which shelf a release belongs on. A release in several formats is filed
 * under the one you'd reach for: vinyl over CD, CD over its bonus DVD.
 */
function detect_media_kind(array $formats): string
{
    $names = array_map(fn ($f) => (string) ($f['name'] ?? ''), $formats);

    return match (true) {
        in_array('Vinyl', $names, true)                  => 'vinyl',
        (bool) array_intersect(['CD', 'CDr'], $names)    => 'cd',
        (bool) array_intersect(['Blu-ray', 'Blu-ray-R'], $names) => 'bluray',
        (bool) array_intersect(['DVD', 'DVDr'], $names)  => 'dvd',
        default                                          => 'other',
    };
}

/** The Type box's choices, plus whatever the record already has so saving doesn't drop it. */
function item_type_options(?string $current): array
{
    $current = trim((string) $current);

    return $current !== '' && !in_array($current, ITEM_TYPES, true) ? [...ITEM_TYPES, $current] : ITEM_TYPES;
}

/**
 * Every field the drawer can show, in the order it shows them.
 *
 * group:   'mine' (stored on the item) or 'discogs' (from the release cache)
 * kinds:   the media kinds it applies to, or null for all
 * default: whether the drawer shows it until /admin_fields says otherwise
 * type:    how the drawer draws it
 */
function field_catalog(): array
{
    static $catalog = null;

    return $catalog ??= [
        'barcode'      => ['label' => 'Barcode',          'group' => 'mine', 'kinds' => null,                             'default' => true, 'type' => 'text'],
        'release_date' => ['label' => 'Release date',     'group' => 'mine', 'kinds' => null,                             'default' => true, 'type' => 'text'],
        'region'       => ['label' => 'Region',           'group' => 'mine', 'kinds' => null,                             'default' => true, 'type' => 'text'],
        'media'        => ['label' => 'Media',            'group' => 'mine', 'kinds' => ['cd', 'dvd', 'bluray', 'other'], 'default' => true, 'type' => 'text'],
        'vinyl_size'   => ['label' => 'Size',             'group' => 'mine', 'kinds' => ['vinyl'],                        'default' => true, 'type' => 'text'],
        'vinyl_color'  => ['label' => 'Colour / variant', 'group' => 'mine', 'kinds' => ['vinyl'],                        'default' => true, 'type' => 'text'],
        'item_type'    => ['label' => 'Type',             'group' => 'mine', 'kinds' => null,                             'default' => true, 'type' => 'text'],
        'notes'        => ['label' => 'Notes',            'group' => 'mine', 'kinds' => null,                             'default' => true, 'type' => 'html'],

        'artist'           => ['label' => 'Artist',         'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'text'],
        'year'             => ['label' => 'Year',           'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'text'],
        'country'          => ['label' => 'Country',        'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'text'],
        'labels'           => ['label' => 'Label',          'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'lines'],
        'catalog_number'   => ['label' => 'Catalogue no.',  'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'lines'],
        'formats'          => ['label' => 'Format',         'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'lines'],
        'genres'           => ['label' => 'Genres',         'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'tags'],
        'styles'           => ['label' => 'Styles',         'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'tags'],
        'date_added'       => ['label' => 'Added',          'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'text'],
        'my_rating'        => ['label' => 'My rating',      'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'text'],
        'estimated_weight' => ['label' => 'Weight',         'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'text'],
        'data_quality'     => ['label' => 'Data quality',   'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'text'],
        'release_id'       => ['label' => 'Release ID',     'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'text'],
        'community'        => ['label' => 'Community',      'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'lines'],
        'marketplace'      => ['label' => 'Marketplace',    'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'lines'],

        'gallery'       => ['label' => 'Image gallery', 'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'gallery'],
        'tracklist'     => ['label' => 'Tracklist',     'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'tracklist'],
        'credits'       => ['label' => 'Credits',       'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'credits'],
        'companies'     => ['label' => 'Companies',     'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'credits'],
        'identifiers'   => ['label' => 'Identifiers',   'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'credits'],
        'series'        => ['label' => 'Series',        'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'lines'],
        'release_notes' => ['label' => 'Discogs notes', 'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'html'],
        'videos'        => ['label' => 'Videos',        'group' => 'discogs', 'kinds' => null, 'default' => false, 'type' => 'links'],
        'links'         => ['label' => 'Discogs links', 'group' => 'discogs', 'kinds' => null, 'default' => true,  'type' => 'links'],
    ];
}

/** The fields that apply to one media kind, in catalog order. */
function fields_for_kind(string $kind): array
{
    return array_keys(array_filter(
        field_catalog(),
        fn ($def) => $def['kinds'] === null || in_array($kind, $def['kinds'], true)
    ));
}

/** Bruno's own fields for one media kind, the ones the edit form saves. */
function own_fields_for_kind(string $kind): array
{
    return array_values(array_filter(fields_for_kind($kind), fn ($key) => field_catalog()[$key]['group'] === 'mine'));
}

/** The boxes at the top of the edit form, in the order they are filled in. */
function primary_fields_for_kind(string $kind): array
{
    return $kind === 'vinyl'
        ? ['barcode', 'release_date', 'region', 'vinyl_size', 'vinyl_color', 'notes']
        : ['barcode', 'release_date', 'region', 'media', 'item_type', 'notes'];
}

/** The rest of the editable fields, under "More fields". */
function secondary_fields_for_kind(string $kind): array
{
    return array_values(array_diff(own_fields_for_kind($kind), primary_fields_for_kind($kind)));
}

/**
 * Which fields the drawer shows, as { kind: { field: bool } }. Anything not
 * saved yet uses the catalog's default, so a new field needs no migration.
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

/* ---------- Corrections to Discogs' values ---------- */

/** What was typed into a correction box, as the list of values the drawer draws. */
function override_value(string $key, string $text): array
{
    return match (OVERRIDE_FIELDS[$key]) {
        'tracks' => tracklist_from_text($text),
        'list'   => array_values(array_filter(array_map('trim', preg_split('/[,\n]/', $text)))),
        default  => array_values(array_filter(array_map('trim', preg_split(LINE_BREAK, $text)))),
    };
}

/** The record's own value for a correctable fact, or null where it has none. */
function item_override(array $row, string $key): ?array
{
    $text = trim((string) ($row[$key] ?? ''));

    return $text !== '' ? override_value($key, $text) : null;
}

/**
 * A typed tracklist, one track a line: "1. Poker Face 3:58". The position and
 * length are optional; an unusual position goes in brackets: "[Video] Beautiful".
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

/** Discogs' tracklist written the way tracklist_from_text() reads it, as a starting point. */
function tracklist_to_text(array $tracks): string
{
    $lines = [];

    foreach ($tracks as $track) {
        if (($track['type_'] ?? 'track') === 'heading') {
            continue;
        }
        // An index track's songs are its sub-tracks.
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

/* ---------- Reading Discogs' values ---------- */

/** Without Discogs' disambiguation suffix: "Anitta (2)" -> "Anitta". */
function clean_artist_name(string $name): string
{
    return trim(preg_replace('/\s\(\d+\)$/', '', $name));
}

/** A tracklist with Discogs' "(2)" taken off every track's artists and credits, sub-tracks included. */
function clean_track_names(array $tracks): array
{
    $clean = fn (array $people) => array_map(
        fn ($person) => is_array($person) && isset($person['name'])
            ? ['name' => clean_artist_name((string) $person['name'])] + $person
            : $person,
        $people
    );

    return array_map(function ($track) use ($clean) {
        if (!is_array($track)) {
            return $track;
        }
        foreach (['artists', 'extraartists'] as $key) {
            if (is_array($track[$key] ?? null)) {
                $track[$key] = $clean($track[$key]);
            }
        }
        if (is_array($track['sub_tracks'] ?? null)) {
            $track['sub_tracks'] = clean_track_names($track['sub_tracks']);
        }

        return $track;
    }, $tracks);
}

function artists_text(array $artists): string
{
    $names = array_map(fn ($a) => clean_artist_name((string) ($a['name'] ?? '')), $artists);
    $names = array_filter($names, fn ($n) => $n !== '');

    return $names ? implode(', ', $names) : 'Unknown artist';
}

/** "LP, Album, Limited Edition, Pink": every format detail on one line. */
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
    return array_map(fn ($l) => clean_artist_name((string) ($l['name'] ?? '')), $labels);
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

/** The barcode among a release's identifiers, as bare digits where it has at least eight. */
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
        $digits = preg_replace('/\D+/', '', $value);

        return strlen($digits) >= 8 ? $digits : $value;
    }

    return null;
}

function vinyl_formats(array $formats): array
{
    return array_filter($formats, fn ($format) => ($format['name'] ?? '') === 'Vinyl');
}

/** Vinyl size ('12"') from the format descriptions. */
function formats_vinyl_size(array $formats): ?string
{
    foreach (vinyl_formats($formats) as $format) {
        foreach (($format['descriptions'] ?? []) as $description) {
            if (preg_match('/^(\d+)"$/', trim($description), $m)) {
                return $m[1] . '"';
            }
        }
    }

    return null;
}

/** A pressing's colour as Discogs writes it: the format's free text, or a telling description. */
function formats_vinyl_color(array $formats): ?string
{
    foreach (vinyl_formats($formats) as $format) {
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

/** What goes in the Media box: "CD", "DVD-Video"… */
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

/** Album, single, promo… guessed from the format descriptions until one is picked in the admin. */
function formats_item_type(array $formats): ?string
{
    $descriptions = array_merge(...array_map(fn ($format) => $format['descriptions'] ?? [], array_values($formats)));

    return match (true) {
        (bool) array_intersect($descriptions, ['Promo', 'Promotional']) => 'promo',
        (bool) array_intersect($descriptions, ['Single', 'Maxi-Single']) => 'single',
        in_array('EP', $descriptions, true)                             => 'ep',
        in_array('Compilation', $descriptions, true)                    => 'compilation',
        in_array('Album', $descriptions, true)                          => 'album',
        default                                                         => null,
    };
}

/** "Name (number)", one line per series a release belongs to. */
function series_lines(array $series): array
{
    $lines = [];
    foreach ($series as $entry) {
        $name = trim((string) ($entry['name'] ?? ''));
        $number = trim((string) ($entry['catno'] ?? ''));
        if ($name !== '') {
            $lines[] = $name . ($number !== '' ? " ($number)" : '');
        }
    }

    return $lines;
}

/** Discogs markup ([a=Artist], [url=…]…[/url], [b]…) flattened to plain text. */
function clean_discogs_markup(?string $text): string
{
    return trim(preg_replace(
        ['/\[url=[^\]]*\]([\s\S]*?)\[\/url\]/', '/\[[almr]=([^\]]+)\]/', '/\[\/?[a-z]\]/'],
        ['$1', '$1', ''],
        (string) $text
    ));
}

/**
 * One field of one record: Bruno's value if he typed one, otherwise Discogs'.
 * Null when neither has anything. $release is null for a record that isn't
 * on Discogs.
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
        'barcode'          => $release['barcode'] ?: identifiers_barcode(json_column($release['identifiers_json'] ?? null)),
        'release_date'     => $release['released_formatted'] ?: ($release['released'] ?: ($release['year'] ?: null)),
        'region'           => $release['country'] ?: null,
        'media'            => formats_media($formats),
        'vinyl_size'       => formats_vinyl_size($formats),
        'vinyl_color'      => formats_vinyl_color($formats),
        'item_type'        => formats_item_type($formats),
        'artist'           => $release['artists_text'] ?: null,
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
        'series'           => series_lines(json_column($release['series_json'] ?? null)) ?: null,
        default            => null,
    };
}

/**
 * The Discogs value that shows through when a box is left empty, as text for
 * the hint under it. With $always it is given even when the box is filled in,
 * to compare a correction with what it corrects.
 */
function discogs_fallback_text(array $item, ?array $release, string $key, bool $always = false): string
{
    if ($release === null || (!$always && trim((string) ($item[$key] ?? '')) !== '')) {
        return '';
    }

    $value = item_field_value([...$item, $key => null], $release, $key);

    if ($value === null || $value === '' || $value === []) {
        return '';
    }

    return is_array($value) ? implode(', ', $value) : (string) $value;
}
