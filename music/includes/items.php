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

/** The colour a vinyl is drawn in when no word of its colour text matches (the CSS default too). */
const VINYL_FALLBACK_COLOR = '#131313';

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

/** A colour picked in the admin as "#rrggbb", lower-cased, or null if it isn't one. */
function vinyl_hex(?string $text): ?string
{
    $text = strtolower(trim((string) $text));

    return preg_match('/^#[0-9a-f]{6}$/', $text) ? $text : null;
}

/** The most discs one sleeve fans out on the shelf; more than that stops reading as a stack. */
const MAX_DISCS = 3;

/**
 * What the admin has said about a record's discs: how many there are (null = as
 * Discogs' formats say), the picture on each ("" for a plain disc), and the
 * colour picked for each vinyl (`hex`, null = not picked) with whether it is
 * translucent (`tr`, null = as the colour text says). `set` is false for a
 * record the admin has never configured, which still has the single disc_url of
 * before, shown on every disc.
 *
 * @return array{set: bool, count: ?int, art: string[], hex: (?string)[], tr: (?bool)[]}
 */
function disc_config(array $row): array
{
    $raw = json_column($row['disc_config'] ?? null);
    if (!$raw) {
        return [
            'set' => false, 'count' => null, 'art' => array_fill(0, MAX_DISCS, ''),
            'hex' => array_fill(0, MAX_DISCS, null), 'tr' => array_fill(0, MAX_DISCS, null),
        ];
    }

    $count = isset($raw['count']) && is_numeric($raw['count']) ? min(MAX_DISCS, max(1, (int) $raw['count'])) : null;
    $art = $hex = $tr = [];
    for ($k = 0; $k < MAX_DISCS; $k++) {
        $url = trim((string) (($raw['art'] ?? [])[$k] ?? ''));
        $art[] = safe_http_url($url) ? $url : '';
        $hex[] = vinyl_hex(is_string($raw['hex'][$k] ?? null) ? $raw['hex'][$k] : null);
        $tr[] = match ($raw['tr'][$k] ?? null) {
            1, '1', true => true,
            0, '0', false => false,
            default => null,
        };
    }

    return ['set' => true, 'count' => $count, 'art' => $art, 'hex' => $hex, 'tr' => $tr];
}

/**
 * The colour picked for each disc, falling back to the one picked for the whole
 * record before colours were set disc by disc (the vinyl_hex and
 * vinyl_translucent columns). Only what the admin chose: a disc left on
 * automatic has null here, and follows the colour text.
 *
 * @return array{hex: (?string)[], tr: (?bool)[]}
 */
function disc_colours(array $row): array
{
    $config = disc_config($row);
    $hex = vinyl_hex($row['vinyl_hex'] ?? null);
    $tr = match ($row['vinyl_translucent'] ?? null) {
        1, '1' => true,
        0, '0' => false,
        default => null,
    };

    foreach (array_keys($config['hex']) as $k) {
        $config['hex'][$k] ??= $hex;
        $config['tr'][$k] ??= $tr;
    }

    return ['hex' => $config['hex'], 'tr' => $config['tr']];
}

/**
 * The physical discs inside a sleeve, at most three (more than that and the
 * fan-out stops reading as a stack). A colour typed into the admin wins over
 * the one Discogs guessed at, and one picked there for a disc wins over both,
 * as does translucent or opaque, so a variant the keywords get wrong can be
 * corrected by hand, disc by disc (see disc_colours()); so do the disc count
 * and each disc's picture (see disc_config()).
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
    $config = disc_config($row);
    $colours = disc_colours($row);
    // A single disc picture from before discs were set one by one makes every
    // vinyl a picture disc, whatever Discogs says. Once discs are configured the
    // picture is per disc, and applied at the end.
    $picture = !$config['set'] && trim((string) ($row['disc_url'] ?? '')) !== '';
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
                    + ['pic' => $picture || in_array('Picture Disc', $descriptions, true), 'sz' => (int) $size];
            } elseif ($name === 'CD' || $name === 'CDr') {
                $discs[] = ['t' => 'cd'];
            } elseif ($name === 'DVD' || $name === 'DVDr') {
                $discs[] = ['t' => 'dvd'];
            } elseif ($name === 'Blu-ray' || $name === 'Blu-ray-R') {
                $discs[] = ['t' => 'bd'];
            }
        }
    }

    // A disc that isn't tied to a Discogs release has no formats to read; its
    // Media box says what is in the sleeve ("CD + DVD", "2 CD").
    if (!$discs && !$formats) {
        foreach (explode('+', (string) ($row['media'] ?? '')) as $part) {
            if (preg_match('/^\s*(?:(\d+)\s*[x×]?\s*)?(CD|DVD|Blu-?ray)\s*$/i', $part, $m)) {
                $type = ['cd' => 'cd', 'dvd' => 'dvd'][strtolower($m[2])] ?? 'bd';
                array_push($discs, ...array_fill(0, min(3, max(1, (int) $m[1])), ['t' => $type]));
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

    $discs = array_slice($discs, 0, MAX_DISCS);

    // A count set in the admin: fewer discs than Discogs lists are dropped from
    // the back; more are copies of the last one (a second CD is another CD).
    if ($config['count'] !== null) {
        $last = end($discs);
        while (count($discs) < $config['count']) {
            $discs[] = $last;
        }
        $discs = array_slice($discs, 0, $config['count']);
    }

    // A colour picked for one vinyl is that disc's alone; a disc left on
    // automatic keeps what the colour text says.
    foreach ($discs as $k => $disc) {
        if ($disc['t'] !== 'v') {
            continue;
        }
        if ($colours['hex'][$k] !== null) {
            $discs[$k]['c'] = $colours['hex'][$k];
        }
        if ($colours['tr'][$k] !== null) {
            $discs[$k]['tr'] = $colours['tr'][$k];
        }
    }

    // A picture chosen for one disc is that disc's alone, and makes a vinyl one
    // a picture disc.
    foreach ($discs as $k => $disc) {
        if (($config['art'][$k] ?? '') !== '') {
            $discs[$k]['art'] = $config['art'][$k];
            if ($disc['t'] === 'v') {
                $discs[$k]['pic'] = true;
            }
        }
    }

    return $discs;
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

/**
 * The release date the way it reads in a tooltip: "23 November 2009", or
 * "November 2009" / "2009" where only that much is known. Empty when the record
 * has no date at all. Built from item_sort_date() so the label always agrees
 * with the year beside it and with how the list sorts.
 */
function release_date_label(array $row): string
{
    $date = item_sort_date($row);
    if ($date === '') {
        return '';
    }

    [$year, $month, $day] = array_map('intval', explode('-', $date));
    if ($month < 1) {
        return (string) $year;
    }

    $when = DateTimeImmutable::createFromFormat('!Y-n-j', "$year-$month-" . max(1, $day));

    return $when ? $when->format($day > 0 ? 'j F Y' : 'F Y') : (string) $year;
}

/**
 * The year as the admin lists show it, with the full release date in a
 * tooltip. Where Discogs (and the admin) only know the year, the tooltip says so
 * instead of repeating it, so a missing date reads as missing rather than as a
 * tooltip that failed.
 */
function year_cell(array $row): string
{
    $year = item_year($row);
    if (!$year) {
        return '<span class="none">—</span>';
    }

    $full = release_date_label($row);
    $tip = $full !== '' && $full !== (string) $year ? $full : 'Only the year is known';

    return '<span class="hover-date" tabindex="0" data-tip="' . e($tip) . '" aria-label="' . e($year . ', ' . $tip) . '">' . $year . '</span>';
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
 * the format line, the tracklist (titles and the artists and credits on each
 * track), the release's own credits, and Bruno's own notes and colour. One
 * string per record, so a query like "Interscope", "house", a song title or a
 * featured artist finds it without opening every drawer. The browser adds the
 * columns it already has.
 */
function item_search_text(array $row): string
{
    $labels = json_column($row['labels_json'] ?? null);
    $tracklist = item_override($row, 'tracklist') ?? json_column($row['tracklist_json'] ?? null);
    $credits = json_column($row['extraartists_json'] ?? null);

    $parts = [
        ...(item_override($row, 'formats') ?? [$row['formats_text'] ?? '']),
        ...(item_override($row, 'labels') ?? labels_lines($labels)),
        ...(item_override($row, 'catalog_number') ?? catalog_numbers($labels)),
        ...(item_override($row, 'genres') ?? json_column($row['genres_json'] ?? null)),
        ...(item_override($row, 'styles') ?? json_column($row['styles_json'] ?? null)),
        ...tracklist_search_terms($tracklist),
        ...array_map(fn ($c) => clean_artist_name((string) ($c['name'] ?? '')), $credits),
        $row['vinyl_color'] ?? '',
        $row['vinyl_size'] ?? '',
        $row['media'] ?? '',
        $row['notes'] ?? '',
    ];

    $parts = array_map(fn ($part) => trim((string) $part), array_filter($parts, 'is_scalar'));

    return implode(' | ', array_unique(array_filter($parts, fn ($part) => $part !== '')));
}

/**
 * Every title and artist name in a tracklist, sub-tracks included — a medley
 * or a suite lists its parts there, and a feature ("Lady Gaga") often lives on
 * the track's own artists or extraartists rather than the release's.
 */
function tracklist_search_terms(array $tracks): array
{
    $terms = [];

    foreach ($tracks as $track) {
        $terms[] = (string) ($track['title'] ?? '');
        foreach ([...($track['artists'] ?? []), ...($track['extraartists'] ?? [])] as $artist) {
            $terms[] = clean_artist_name((string) ($artist['name'] ?? ''));
        }
        if (!empty($track['sub_tracks'])) {
            array_push($terms, ...tracklist_search_terms($track['sub_tracks']));
        }
    }

    return $terms;
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
        // The one picture for every disc, for records not yet set disc by disc;
        // a configured record carries its pictures on each entry of `discs`.
        'disc'    => disc_config($row)['set'] ? '' : (string) ($row['disc_url'] ?? ''),
        'added'   => (string) ($row['date_added'] ?? ''),
        'kind'    => (string) $row['media_kind'],
        // 'bd' in the view model, 'bluray' in the database: the CSS and the
        // floor view were written against the short form.
        'k'       => $row['media_kind'] === 'bluray' ? 'bd' : (string) $row['media_kind'],
        // The case drawn on the shelf, where it differs from the format: a DVD
        // that actually shipped in a CD-sized jewel case. Null everywhere else.
        'case'    => $row['media_kind'] === 'dvd' && $row['case_kind'] === 'cd' ? 'cd' : null,
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

    array_push($mine, ...drawer_box_facts($row));

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

/**
 * Where a disc from a box set belongs, and what is in a box: the box names its
 * discs, and each disc names its box. Derived, not typed, so they are not
 * marked as Bruno's own.
 */
function drawer_box_facts(array $row): array
{
    $facts = [];

    if (!empty($row['parent_item_id'])) {
        $box = item_by_id((int) $row['parent_item_id']);
        $facts[] = $box ? drawer_fact('in_box', 'In the box', item_title($box), 'text') : null;
    }

    $discs = db()->prepare('
        SELECT manual_title, media FROM items
         WHERE parent_item_id = ? AND is_visible = 1 AND missing_since IS NULL
         ORDER BY id
    ');
    $discs->execute([$row['id']]);
    $lines = array_map(
        fn ($disc) => implode(' · ', array_filter([$disc['manual_title'], $disc['media']])),
        $discs->fetchAll()
    );
    $facts[] = drawer_fact('inside_box', 'Inside the box', $lines, 'lines');

    return array_map(fn ($fact) => $fact + ['mine' => false], array_filter($facts));
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
