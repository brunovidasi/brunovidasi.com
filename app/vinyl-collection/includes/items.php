<?php

/**
 * Turning rows into what the pages actually draw.
 *
 * Two shapes come out of here. item_card() is the small one the shelf, the grid
 * and the artist pages render hundreds of — it carries only what a sleeve needs
 * to be drawn. item_drawer() is the big one, built for a single record when its
 * drawer opens, and it honours the per-kind field list from /admin_fields:
 * whatever is switched off there simply isn't in the payload.
 *
 * The disc descriptors (colour, size, picture disc) are worked out here rather
 * than in JavaScript because the browser no longer sees Discogs' raw formats —
 * it sees this. The rules are the ones the floor view was already using.
 */

const ITEM_SELECT = '
    SELECT i.*,
           r.discogs_id, r.master_id, r.title, r.artists_text, r.primary_artist, r.artists_json,
           r.year, r.labels_json, r.formats_json, r.formats_text, r.genres_json, r.styles_json,
           r.thumb, r.cover_image, r.country, r.released, r.released_formatted, r.uri,
           r.data_quality, r.release_notes, r.estimated_weight, r.num_for_sale, r.lowest_price,
           r.barcode AS release_barcode, r.community_json, r.images_json, r.tracklist_json,
           r.identifiers_json, r.companies_json, r.extraartists_json, r.videos_json, r.series_json,
           r.date_changed, r.detail_fetched_at
      FROM items i
      LEFT JOIN releases r ON r.discogs_id = i.release_id
';

/**
 * When a release came out, as SQL, for ORDER BY: Discogs' full date where the
 * per-release fetch has brought it in ("2009-11-23", or "2006-08-00" when only
 * the month is known), else the year from the collection listing. It sorts as
 * text, which is why the parts are zero-padded. Blank sorts first, as NULL does.
 *
 * Only Discogs' side: a date typed into the admin is free text SQL can't read,
 * so item_sort_date() below is the one to use wherever a row is in PHP.
 */
const RELEASE_DATE_SQL = "COALESCE(NULLIF(r.released, ''), CASE WHEN r.year > 0 THEN printf('%04d', r.year) END)";

/**
 * The items a visitor may see: on the shelf, not hidden, still on Discogs.
 * Everything the public API reads goes through this.
 */
function public_items(string $source = 'collection', array $where = [], array $params = []): array
{
    $sql = ITEM_SELECT . " WHERE i.source = ? AND i.is_visible = 1 AND i.missing_since IS NULL";
    foreach ($where as $clause) {
        $sql .= " AND $clause";
    }
    $sql .= ' ORDER BY i.sort_rank DESC, r.primary_artist COLLATE NOCASE, ' . RELEASE_DATE_SQL . ', r.title COLLATE NOCASE';

    $stmt = db()->prepare($sql);
    $stmt->execute(array_merge([$source], $params));

    return $stmt->fetchAll();
}

function item_by_id(int $id): ?array
{
    $stmt = db()->prepare(ITEM_SELECT . ' WHERE i.id = ?');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

/**
 * The record's title and artist: what was typed on the record if anything was
 * (a correction of Discogs', or the whole of it for something not on Discogs),
 * else Discogs'.
 */
function item_title(array $row): string
{
    return (string) ($row['manual_title'] ?: $row['title'] ?: 'Untitled');
}

function item_artist(array $row): string
{
    return (string) ($row['manual_artist'] ?: $row['artists_text'] ?: 'Unknown artist');
}

/** The year a record came out: the one in a release date typed in the admin, else Discogs' (0 if neither). */
function item_year(array $row): int
{
    $own = parse_release_date((string) ($row['release_date'] ?? ''));

    return $own !== null ? (int) substr($own, 0, 4) : (int) ($row['year'] ?? 0);
}

/** The cover: the one chosen in the admin, else what Discogs leads with. */
function item_cover(array $row): string
{
    return (string) ($row['cover_url'] ?: $row['cover_image'] ?: $row['thumb'] ?: '');
}

function item_thumb(array $row): string
{
    return (string) ($row['cover_url'] ?: $row['thumb'] ?: $row['cover_image'] ?: '');
}

/**
 * Colours Discogs records as free text ("Red Translucent", "Clear w/ Powder
 * Fill"), so the disc that slides out of the sleeve is matched by keyword. The
 * list and its order are the ones the floor view shipped with.
 */
const VINYL_COLORS = [
    ['red', '#c8322b'], ['pink', '#f06aa8'], ['blue', '#2f6fdd'], ['aqua', '#3cc6d4'],
    ['turquoise', '#2bc0b4'], ['green', '#2f9e58'], ['yellow', '#f0c828'], ['orange', '#ee7d1e'],
    ['purple', '#7a3fb5'], ['violet', '#7a3fb5'], ['gold', '#d4a836'], ['silver', '#b8bcc2'],
    ['grey', '#8a8d92'], ['gray', '#8a8d92'], ['smoky', '#6c6f74'], ['white', '#f1f1ec'],
    ['brown', '#7a4b2a'], ['clear', '#dfe8ec'], ['black', '#131313'],
];

function vinyl_color(string $text): array
{
    $lower = mb_strtolower($text);
    foreach (VINYL_COLORS as [$word, $color]) {
        if (str_contains($lower, $word)) {
            return ['c' => $color, 'tr' => (bool) preg_match('/transl|transp|clear|smoky/', $lower)];
        }
    }

    return ['c' => null, 'tr' => false];
}

/**
 * The physical discs inside a sleeve, at most three (more than that and the
 * fan-out stops reading as a stack). A colour typed into the admin wins over
 * the one Discogs guessed at, so a variant can be corrected by hand.
 *
 * Something filed as "other" (a cassette, a t-shirt, a book) has no disc to
 * show, so it gets none rather than a made-up CD.
 */
function item_discs(array $row, array $formats): array
{
    if ($row['media_kind'] === 'other') {
        return [];
    }

    $override = trim((string) ($row['vinyl_color'] ?? ''));
    $discs = [];

    foreach ($formats as $format) {
        $name = (string) ($format['name'] ?? '');
        $descriptions = $format['descriptions'] ?? [];
        $count = min(3, max(1, (int) ($format['qty'] ?? 1)));

        for ($i = 0; $i < $count; $i++) {
            if ($name === 'Vinyl') {
                $text = $override !== '' ? $override : (string) ($format['text'] ?? '');
                $size = trim((string) ($row['vinyl_size'] ?? '')) ?: (formats_vinyl_size($formats) ?? '12"');
                $discs[] = ['t' => 'v']
                    + vinyl_color($text)
                    + ['pic' => in_array('Picture Disc', $descriptions, true), 'sz' => (int) $size];
            } elseif ($name === 'CD' || $name === 'CDr') {
                $discs[] = ['t' => 'cd'];
            } elseif ($name === 'DVD' || $name === 'DVDr') {
                $discs[] = ['t' => 'dvd'];
            } elseif ($name === 'Blu-ray' || $name === 'Blu-ray-R') {
                $discs[] = ['t' => 'bd'];
            }
        }
    }

    if (!$discs) {
        $discs[] = ['t' => match ($row['media_kind']) {
            'vinyl' => 'v',
            'dvd' => 'dvd',
            'bluray' => 'bd',
            default => 'cd',
        }];
    }

    return array_slice($discs, 0, 3);
}

/**
 * A release date as "YYYY-MM-DD" with 00 for a part that isn't known, or null
 * if it isn't a date at all. Reads Discogs' forms ("2009", "2009-11",
 * "2009-11-23", "2006-08-00") and the prose typed into the admin ("19 August
 * 2008"). Zero-padding means a plain string comparison puts a year-only release
 * ahead of the dated ones in the same year, and a month ahead of its days.
 */
function parse_release_date(string $text): ?string
{
    $text = trim($text);

    if (preg_match('/^(\d{4})(?:-(\d{2})(?:-(\d{2}))?)?$/', $text, $m)) {
        [$year, $month, $day] = [(int) $m[1], (int) ($m[2] ?? 0), (int) ($m[3] ?? 0)];
    } else {
        $parsed = date_parse($text);
        if ($text === '' || $parsed['error_count'] > 0 || !is_int($parsed['year'])) {
            return null;
        }
        [$year, $month, $day] = [$parsed['year'], (int) $parsed['month'], (int) $parsed['day']];
        // date_parse() fills in the 1st for "Aug 2006"; the day isn't known.
        if (preg_match('/^[[:alpha:]]+\.?,? \d{4}$/', $text)) {
            $day = 0;
        }
    }

    return $year > 0 ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
}

/**
 * The key every release-date ordering on the site sorts by: Bruno's own date if
 * he typed one, else Discogs' full date, else just its year. Empty when nothing
 * is known, and the callers put those last.
 */
function item_sort_date(array $row): string
{
    foreach ([(string) ($row['release_date'] ?? ''), (string) ($row['released'] ?? ''), (string) ($row['year'] ?? '')] as $text) {
        $date = parse_release_date($text);
        if ($date !== null) {
            return $date;
        }
    }

    return '';
}

/** The first of these that isn't blank, as text; '' if none is. */
function card_text(mixed ...$candidates): string
{
    foreach ($candidates as $candidate) {
        $text = trim((string) $candidate);
        if ($text !== '' && $text !== '0') {
            return $text;
        }
    }

    return '';
}

/**
 * What the search box looks through beyond what the list already prints: the
 * details the drawer opens on — labels and catalogue numbers, genres and styles,
 * the format line, the tracklist, and Bruno's own notes and colour. One string
 * per record, so a query like "Interscope", "house" or a song title finds it
 * without opening every drawer. The browser adds the columns it already has.
 */
function item_search_text(array $row): string
{
    $labels = json_column($row['labels_json'] ?? null);

    $parts = [
        ...(item_override($row, 'formats') ?? [$row['formats_text'] ?? '']),
        ...(item_override($row, 'labels') ?? labels_lines($labels)),
        ...(item_override($row, 'catalog_number') ?? catalog_numbers($labels)),
        ...(item_override($row, 'genres') ?? json_column($row['genres_json'] ?? null)),
        ...(item_override($row, 'styles') ?? json_column($row['styles_json'] ?? null)),
        ...array_column(item_override($row, 'tracklist') ?? json_column($row['tracklist_json'] ?? null), 'title'),
        $row['vinyl_color'] ?? '',
        $row['vinyl_size'] ?? '',
        $row['media'] ?? '',
        $row['notes'] ?? '',
    ];

    $parts = array_map(fn ($part) => trim((string) $part), array_filter($parts, 'is_scalar'));

    return implode(' | ', array_unique(array_filter($parts, fn ($part) => $part !== '')));
}

/** The small payload: one sleeve on the shelf. */
function item_card(array $row): array
{
    $formats = json_column($row['formats_json'] ?? null);
    $region = card_text($row['region'] ?? null, $row['country'] ?? null);
    // Discogs writes "none" where a release has no barcode.
    $barcode = card_text($row['barcode'] ?? null, $row['release_barcode'] ?? null);
    $first = $formats[0] ?? [];
    // The format line typed on the record replaces Discogs' description of it.
    $extras = item_override($row, 'formats') ?? array_filter(array_merge(
        [(string) ($first['descriptions'][0] ?? '')],
        [trim((string) ($first['text'] ?? ''))]
    ));

    return [
        'id'      => (int) $row['id'],
        'title'   => item_title($row),
        'artist'  => item_artist($row),
        // The year shown is the one in the release date if one was typed in the
        // admin, so the column and its hover never disagree.
        'year'    => item_year($row),
        'date'    => item_sort_date($row),
        // The list's Year column shows the year and puts this on hover. The
        // release's barcode comes from its own column, not the identifiers
        // JSON item_field_value() would parse, because a shelf is hundreds of cards.
        'released' => card_text($row['release_date'] ?? null, $row['released_formatted'] ?? null, $row['released'] ?? null, $row['year'] ?? null),
        'barcode' => strcasecmp($barcode, 'none') === 0 ? '' : $barcode,
        'region'  => region_for_list((string) ($row['region'] ?? ''), (string) ($row['country'] ?? '')),
        'regionName' => $region,
        'search'  => item_search_text($row),
        'cover'   => item_cover($row),
        'thumb'   => item_thumb($row),
        'disc'    => (string) ($row['disc_url'] ?? ''),
        'added'   => (string) ($row['date_added'] ?? ''),
        'kind'    => (string) $row['media_kind'],
        // 'bd' in the view model, 'bluray' in the database: the CSS and the
        // floor view were written against the short form.
        'k'       => $row['media_kind'] === 'bluray' ? 'bd' : (string) $row['media_kind'],
        'discs'   => item_discs($row, $formats),
        'box'     => (bool) array_filter($formats, fn ($f) => ($f['name'] ?? '') === 'Box Set'),
        'fmt'     => implode(' · ', array_filter([$first['name'] ?? '', ...$extras])),
        'fmtRest' => implode(' · ', $extras),
        'type'    => (string) (item_field_value($row, $row, 'item_type') ?? ''),
        'era'     => $row['era_id'] !== null ? (int) $row['era_id'] : null,
        'rank'    => (int) $row['era_rank'],
        'release' => $row['discogs_id'] !== null ? (int) $row['discogs_id'] : null,
    ];
}

/* ---------- The drawer ---------- */

function drawer_fact(string $key, string $label, mixed $value, string $type): ?array
{
    if ($value === null || $value === '' || $value === []) {
        return null;
    }

    return ['key' => $key, 'label' => $label, 'value' => $value, 'type' => $type];
}

/**
 * Everything one record's drawer shows, filtered by what /admin_fields says
 * this media kind should show.
 *
 * Bruno's own fields come first and are grouped separately, because those are
 * the ones the drawer leads with; Discogs' facts follow in catalog order.
 */
function item_drawer(array $row): array
{
    $kind = (string) $row['media_kind'];
    $catalog = field_catalog();
    $release = $row['discogs_id'] !== null ? $row : null;

    $mine = [];
    $facts = [];
    $sections = [];

    foreach (fields_for_kind($kind) as $key) {
        if (!drawer_shows($kind, $key)) {
            continue;
        }

        $def = $catalog[$key];

        // The section-shaped fields are built from their own JSON columns.
        if (in_array($def['type'], ['gallery', 'tracklist', 'credits', 'links'], true)) {
            $section = drawer_section($row, $key, $def);
            if ($section !== null) {
                $sections[$key] = $section;
            }
            continue;
        }

        $value = item_field_value($row, $release, $key);
        // 'album', 'promo' are stored lowercase (they are a fixed list, not
        // prose); the drawer is prose.
        if ($key === 'item_type' && is_string($value)) {
            $value = ucfirst($value);
        }
        $fact = drawer_fact($key, $def['label'], $value, $def['type']);
        if ($fact === null) {
            continue;
        }

        // Whose value is this? The drawer marks Bruno's own notes, and a fact
        // he corrected, differently from one Discogs supplied.
        $fact['mine'] = ($def['group'] === 'mine' || isset(OVERRIDE_FIELDS[$key])) && trim((string) ($row[$key] ?? '')) !== '';

        if ($def['group'] === 'mine') {
            $mine[] = $fact;
        } else {
            $facts[] = $fact;
        }
    }

    // 'community' and 'marketplace' read several columns at once, so they are
    // assembled rather than derived one value at a time.
    foreach (['community', 'marketplace'] as $key) {
        if (!drawer_shows($kind, $key)) {
            continue;
        }
        $lines = $key === 'community' ? drawer_community_lines($row) : drawer_marketplace_lines($row);
        $fact = drawer_fact($key, $catalog[$key]['label'], $lines, 'lines');
        if ($fact !== null) {
            $facts[] = $fact;
        }
    }

    return [
        'id'       => (int) $row['id'],
        'title'    => item_title($row),
        'artist'   => item_artist($row),
        'year'     => item_year($row),
        'kind'     => $kind,
        'kindLabel'=> media_kind_label($kind),
        'cover'    => item_cover($row),
        'disc'     => (string) ($row['disc_url'] ?? ''),
        'source'   => (string) $row['source'],
        'pending'  => $row['detail_fetched_at'] === null,
        'mine'     => $mine,
        'facts'    => $facts,
        // Cast so an empty one is JSON's {} rather than [] — the drawer walks
        // it with Object.entries either way, but a list of sections is a lie.
        'sections' => (object) $sections,
    ];
}

function drawer_section(array $row, string $key, array $def): mixed
{
    return match ($key) {
        'gallery' => array_values(array_map(
            fn ($image) => [
                'full'  => (string) ($image['uri'] ?? ''),
                'thumb' => (string) ($image['uri150'] ?? $image['uri'] ?? ''),
                'type'  => (string) ($image['type'] ?? 'secondary'),
            ],
            array_filter(json_column($row['images_json'] ?? null), fn ($i) => !empty($i['uri']))
        )) ?: null,

        'tracklist' => (item_override($row, 'tracklist') ?? json_column($row['tracklist_json'] ?? null)) ?: null,

        'credits' => drawer_credits(json_column($row['extraartists_json'] ?? null)) ?: null,

        'companies' => drawer_companies(json_column($row['companies_json'] ?? null)) ?: null,

        'identifiers' => array_values(array_map(
            fn ($i) => [
                'role' => (string) ($i['type'] ?? ''),
                'text' => trim((string) ($i['value'] ?? '') . (!empty($i['description']) ? ' (' . $i['description'] . ')' : '')),
            ],
            json_column($row['identifiers_json'] ?? null)
        )) ?: null,

        'videos' => array_values(array_filter(array_map(
            fn ($v) => safe_http_url((string) ($v['uri'] ?? '')) ? [
                'label' => (string) ($v['title'] ?? 'Video'),
                'url'   => (string) $v['uri'],
            ] : null,
            json_column($row['videos_json'] ?? null)
        ))) ?: null,

        'links' => drawer_links($row) ?: null,

        default => null,
    };
}

/** Credits grouped by role, the way a sleeve prints them. */
function drawer_credits(array $credits): array
{
    $groups = [];
    foreach ($credits as $credit) {
        $role = (string) ($credit['role'] ?? 'Credit');
        $name = clean_artist_name((string) ($credit['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $groups[$role][] = $name . (!empty($credit['tracks']) ? ' (' . $credit['tracks'] . ')' : '');
    }

    return array_map(
        fn ($role, $names) => ['role' => $role, 'text' => implode(', ', $names)],
        array_keys($groups),
        $groups
    );
}

function drawer_companies(array $companies): array
{
    $groups = [];
    foreach ($companies as $company) {
        $type = (string) ($company['entity_type_name'] ?? 'Company');
        $name = (string) ($company['name'] ?? '');
        if ($name === '') {
            continue;
        }
        $groups[$type][] = $name . (!empty($company['catno']) ? ' (' . $company['catno'] . ')' : '');
    }

    return array_map(
        fn ($type, $names) => ['role' => $type, 'text' => implode(', ', $names)],
        array_keys($groups),
        $groups
    );
}

function drawer_community_lines(array $row): array
{
    $community = json_column($row['community_json'] ?? null);
    $lines = [];

    if (isset($community['have'])) {
        $lines[] = $community['have'] . ' have it';
    }
    if (isset($community['want'])) {
        $lines[] = $community['want'] . ' want it';
    }
    if (!empty($community['rating']['count'])) {
        $lines[] = sprintf('%s / 5 from %d ratings', $community['rating']['average'], $community['rating']['count']);
    }

    return $lines;
}

function drawer_marketplace_lines(array $row): array
{
    $lines = [];

    if ($row['num_for_sale'] !== null) {
        $lines[] = $row['num_for_sale'] . ' for sale';
    }
    if ($row['lowest_price'] !== null) {
        $lines[] = 'from ' . number_format((float) $row['lowest_price'], 2) . ' (Discogs lowest)';
    }

    return $lines;
}

function drawer_links(array $row): array
{
    $links = [];

    if (safe_http_url((string) ($row['uri'] ?? ''))) {
        $links[] = ['label' => 'Release on Discogs', 'url' => (string) $row['uri']];
    } elseif (!empty($row['discogs_id'])) {
        $links[] = ['label' => 'Release on Discogs', 'url' => 'https://www.discogs.com/release/' . (int) $row['discogs_id']];
    }
    if (!empty($row['master_id'])) {
        $links[] = ['label' => 'Master release', 'url' => 'https://www.discogs.com/master/' . (int) $row['master_id']];
    }

    return $links;
}

/** Only ever hand a browser an http(s) link built from API data. */
function safe_http_url(string $url): bool
{
    return (bool) preg_match('#^https?://#i', $url);
}

/**
 * An empty row shaped like one from ITEM_SELECT, for the "add something you're
 * hunting" form — which has to render every box before any row exists.
 */
function blank_item_row(): array
{
    $row = [];
    foreach (db()->query('PRAGMA table_info(items)')->fetchAll() as $column) {
        $row[$column['name']] = $column['dflt_value'] !== null ? trim($column['dflt_value'], "'") : null;
    }

    $releaseColumns = array_column(db()->query('PRAGMA table_info(releases)')->fetchAll(), 'name');
    foreach ($releaseColumns as $name) {
        // barcode exists on both tables; ITEM_SELECT aliases the release's one.
        $row[$name === 'barcode' ? 'release_barcode' : $name] = $row[$name] ?? null;
    }

    return array_merge($row, [
        'id'          => 0,
        'source'      => 'searching',
        'media_kind'  => 'other',
        'is_visible'  => 1,
        'sort_rank'   => 0,
        'discogs_id'  => null,
        'title'       => null,
        'artists_text' => null,
    ]);
}
