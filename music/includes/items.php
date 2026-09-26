<?php

/**
 * Records as the pages draw them. item_card() is the small shape the shelves
 * render by the hundred; the drawer's full detail is in drawer.php.
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
 * Discogs' release date as sortable SQL text ("2009-11-23", "2006-08-00", or
 * just the year). A date typed in the admin is free text SQL can't read, so
 * rows already in PHP sort by item_sort_date() instead.
 */
const RELEASE_DATE_SQL = "COALESCE(NULLIF(r.released, ''), CASE WHEN r.year > 0 THEN printf('%04d', r.year) END)";

/** What a visitor may see: visible, and still on Discogs. */
const PUBLIC_ITEM_WHERE = 'i.is_visible = 1 AND i.missing_since IS NULL';

const SALE_CONDITIONS = ['new' => 'New', 'used' => 'Used'];

const CURRENCY_SYMBOLS = ['AUD' => 'A$', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'BRL' => 'R$'];

function public_items(string $source = 'collection', array $where = [], array $params = []): array
{
    $sql = ITEM_SELECT . ' WHERE i.source = ? AND ' . PUBLIC_ITEM_WHERE;
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

function items_query(string $sql): array
{
    return db()->query(ITEM_SELECT . ' ' . $sql)->fetchAll();
}

/** The release half of a row, or null for a record that isn't on Discogs. */
function item_release(array $row): ?array
{
    return $row['discogs_id'] !== null ? $row : null;
}

function item_title(array $row): string
{
    return (string) ($row['manual_title'] ?: $row['title'] ?: 'Untitled');
}

function item_artist(array $row): string
{
    return (string) ($row['manual_artist'] ?: $row['artists_text'] ?: 'Unknown artist');
}

/** The year of a release date typed in the admin, else Discogs' year, else 0. */
function item_year(array $row): int
{
    $own = parse_release_date((string) ($row['release_date'] ?? ''));

    return $own !== null ? (int) substr($own, 0, 4) : (int) ($row['year'] ?? 0);
}

function item_cover(array $row): string
{
    return (string) ($row['cover_url'] ?: $row['cover_image'] ?: $row['thumb'] ?: '');
}

function item_thumb(array $row): string
{
    return (string) ($row['cover_url'] ?: $row['thumb'] ?: $row['cover_image'] ?: '');
}

/** "Artist · 2009", the line under a record's title in the admin. */
function item_byline(array $row): string
{
    return item_artist($row) . ($row['year'] ? ' · ' . $row['year'] : '');
}

/* ---------- Release dates ---------- */

/**
 * A release date as "YYYY-MM-DD", with 00 for an unknown month or day, or
 * null if it isn't a date. Reads Discogs' forms and prose typed in the admin
 * ("19 August 2008"). The zeros sort a year-only release ahead of dated ones.
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
        // date_parse() makes "Aug 2006" the 1st; the day isn't known.
        if (preg_match('/^[[:alpha:]]+\.?,? \d{4}$/', $text)) {
            $day = 0;
        }
    }

    return $year > 0 ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
}

/** What every release-date ordering sorts by: Bruno's date, else Discogs' date, else the year. '' if none. */
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

/** "23 November 2009", "November 2009" or "2009", as much as is known. */
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

/* ---------- The card ---------- */

/** The first of these that isn't blank or '0', as text. */
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
 * What the search box looks through beyond the columns the browser already
 * has: labels, catalogue numbers, genres, the format line, the tracklist and
 * its credits, and Bruno's own notes and colour.
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

/** Every title and artist in a tracklist, sub-tracks included. */
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

/** One sleeve on a shelf: only what drawing it needs. */
function item_card(array $row): array
{
    $formats = item_formats($row);
    $region = card_text($row['region'] ?? null, $row['country'] ?? null);
    $barcode = card_text($row['barcode'] ?? null, $row['release_barcode'] ?? null);
    $first = $formats[0] ?? [];
    $extras = item_override($row, 'formats') ?? array_filter(array_merge(
        [(string) ($first['descriptions'][0] ?? '')],
        [trim((string) ($first['text'] ?? ''))]
    ));

    return [
        'id'         => (int) $row['id'],
        'title'      => item_title($row),
        'artist'     => item_artist($row),
        'year'       => item_year($row),
        'date'       => item_sort_date($row),
        'released'   => card_text($row['release_date'] ?? null, $row['released_formatted'] ?? null, $row['released'] ?? null, $row['year'] ?? null),
        // Discogs writes "none" for a release without one.
        'barcode'    => strcasecmp($barcode, 'none') === 0 ? '' : $barcode,
        'region'     => region_for_list((string) ($row['region'] ?? ''), (string) ($row['country'] ?? '')),
        'regionName' => $region,
        'search'     => item_search_text($row),
        'cover'      => item_cover($row),
        'thumb'      => item_thumb($row),
        // One picture for every disc, on records not yet set disc by disc.
        'disc'       => disc_config($row)['set'] ? '' : (string) ($row['disc_url'] ?? ''),
        'added'      => (string) ($row['date_added'] ?? ''),
        'kind'       => (string) $row['media_kind'],
        'k'          => $row['media_kind'] === 'bluray' ? 'bd' : (string) $row['media_kind'],
        // A DVD that came in a CD-sized jewel case is drawn in that case.
        'case'       => $row['media_kind'] === 'dvd' && $row['case_kind'] === 'cd' ? 'cd' : null,
        'discs'      => item_discs($row, $formats),
        'box'        => (bool) array_filter($formats, fn ($f) => ($f['name'] ?? '') === 'Box Set'),
        'fmt'        => implode(' · ', array_filter([$first['name'] ?? '', ...$extras])),
        'fmtRest'    => implode(' · ', $extras),
        'type'       => (string) (item_field_value($row, $row, 'item_type') ?? ''),
        'era'        => $row['era_id'] !== null ? (int) $row['era_id'] : null,
        'rank'       => (int) $row['era_rank'],
        'release'    => $row['discogs_id'] !== null ? (int) $row['discogs_id'] : null,
    ];
}

/* ---------- Selling ---------- */

/** A listing's card: the sleeve's card plus what selling it needs. */
function sale_card(array $row): array
{
    $price = sale_price($row);

    return item_card($row) + [
        'price'          => $price,
        'currency'       => (string) ($row['sale_currency'] ?: 'AUD'),
        'priceLabel'     => sale_price_label($price, $row['sale_currency'] ?? null),
        'ebay'           => safe_http_url((string) ($row['ebay_url'] ?? '')) ? $row['ebay_url'] : '',
        'condition'      => isset(SALE_CONDITIONS[$row['sale_condition'] ?? '']) ? $row['sale_condition'] : null,
        'conditionLabel' => sale_condition_label($row) ?? '',
        // Discogs' date_added is empty on a listing, so the shop sorts by when it was listed.
        'listed'         => (string) ($row['created_at'] ?? ''),
    ];
}

function sale_price(array $row): ?float
{
    return $row['sale_price'] !== null && $row['sale_price'] !== '' ? (float) $row['sale_price'] : null;
}

/** "A$45.00", "€30.00", "120.00 BRL" */
function sale_price_label(?float $amount, ?string $currency): string
{
    if ($amount === null) {
        return '';
    }

    $code = strtoupper(trim((string) $currency)) ?: 'AUD';
    $symbol = CURRENCY_SYMBOLS[$code] ?? "$code ";

    return $symbol . number_format($amount, 2);
}

function sale_condition_label(array $row): ?string
{
    return SALE_CONDITIONS[$row['sale_condition'] ?? ''] ?? null;
}

/** An empty row shaped like one from ITEM_SELECT, for the form that adds a record by hand. */
function blank_item_row(): array
{
    $row = [];
    foreach (db()->query('PRAGMA table_info(items)')->fetchAll() as $column) {
        $row[$column['name']] = $column['dflt_value'] !== null ? trim($column['dflt_value'], "'") : null;
    }

    foreach (array_column(db()->query('PRAGMA table_info(releases)')->fetchAll(), 'name') as $name) {
        // barcode is on both tables; ITEM_SELECT aliases the release's.
        $row[$name === 'barcode' ? 'release_barcode' : $name] = $row[$name] ?? null;
    }

    return array_merge($row, [
        'id'           => 0,
        'source'       => 'searching',
        'media_kind'   => 'other',
        'is_visible'   => 1,
        'sort_rank'    => 0,
        'discogs_id'   => null,
        'title'        => null,
        'artists_text' => null,
    ]);
}
