<?php

/**
 * Everything one record's drawer shows, filtered by what /admin_fields says
 * its media kind should show. Bruno's own fields lead, apart from Discogs'.
 */

/** Drawer keys drawn as sections of their own rather than one-line facts. */
const DRAWER_SECTION_TYPES = ['gallery', 'tracklist', 'credits', 'links'];

/** Marks a gallery nobody has curated yet, as against one curated down to nothing. */
const UNCURATED = ['__uncurated__'];

function item_drawer(array $row): array
{
    $kind = (string) $row['media_kind'];
    $catalog = field_catalog();
    $release = item_release($row);

    $mine = [];
    $facts = [];
    $sections = [];

    foreach (fields_for_kind($kind) as $key) {
        if (!drawer_shows($kind, $key)) {
            continue;
        }

        $def = $catalog[$key];

        if (in_array($def['type'], DRAWER_SECTION_TYPES, true)) {
            $section = drawer_section($row, $key);
            if ($section !== null) {
                $sections[$key] = $section;
            }
            continue;
        }

        $value = item_field_value($row, $release, $key);
        if ($key === 'item_type' && is_string($value)) {
            $value = ucfirst($value);
        }
        $fact = drawer_fact($key, $def['label'], $value, $def['type']);
        if ($fact === null) {
            continue;
        }

        // Bruno's own notes, and facts he corrected, are marked as his.
        $fact['mine'] = ($def['group'] === 'mine' || isset(OVERRIDE_FIELDS[$key])) && trim((string) ($row[$key] ?? '')) !== '';

        if ($def['group'] === 'mine') {
            $mine[] = $fact;
        } else {
            $facts[] = $fact;
        }
    }

    // A listing leads with its price and condition: that is what a buyer opened it for.
    if ($row['source'] === 'for_sale') {
        $price = $row['sale_price'] !== null ? sale_price_label((float) $row['sale_price'], $row['sale_currency']) : null;
        $priceFact = drawer_fact('price', 'Price', $price, 'price');
        if ($priceFact !== null) {
            array_unshift($mine, $priceFact + ['mine' => true]);
        }
        $conditionFact = drawer_fact('condition', 'Condition', sale_condition_label($row), 'text');
        if ($conditionFact !== null) {
            $mine[] = $conditionFact + ['mine' => true];
        }
    }

    array_push($mine, ...drawer_box_facts($row));

    foreach (['community' => 'drawer_community_lines', 'marketplace' => 'drawer_marketplace_lines'] as $key => $lines) {
        if (!drawer_shows($kind, $key)) {
            continue;
        }
        $fact = drawer_fact($key, $catalog[$key]['label'], $lines($row), 'lines');
        if ($fact !== null) {
            $facts[] = $fact;
        }
    }

    return [
        'id'        => (int) $row['id'],
        'title'     => item_title($row),
        'artist'    => item_artist($row),
        'year'      => item_year($row),
        'kind'      => $kind,
        'kindLabel' => media_kind_label($kind),
        'cover'     => item_cover($row),
        'disc'      => (string) ($row['disc_url'] ?? ''),
        'source'    => (string) $row['source'],
        'pending'   => $row['detail_fetched_at'] === null,
        'mine'      => $mine,
        'facts'     => $facts,
        // An object, so an empty one is {} in the JSON.
        'sections'  => (object) $sections,
    ];
}

function drawer_fact(string $key, string $label, mixed $value, string $type): ?array
{
    if ($value === null || $value === '' || $value === []) {
        return null;
    }

    return ['key' => $key, 'label' => $label, 'value' => $value, 'type' => $type];
}

/** The box a disc belongs in, and the discs inside a box. */
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

/** Every picture a drawer could show: Discogs' gallery, then photos pasted in by hand. */
function drawer_gallery_all(array $row): array
{
    return array_merge(
        array_map(
            fn ($image) => [
                'full'  => (string) ($image['uri'] ?? ''),
                'thumb' => (string) ($image['uri150'] ?? $image['uri'] ?? ''),
                'type'  => (string) ($image['type'] ?? 'secondary'),
            ],
            array_filter(json_column($row['images_json'] ?? null), fn ($i) => !empty($i['uri']))
        ),
        array_map(
            fn ($url) => ['full' => $url, 'thumb' => $url, 'type' => 'yours'],
            array_filter(json_column($row['extra_photos_json'] ?? null), 'safe_http_url')
        )
    );
}

/** The pictures picked for the gallery, in order, or null when nobody has picked yet. */
function gallery_choice(array $row): ?array
{
    $chosen = json_column($row['gallery_json'] ?? null, UNCURATED);

    return $chosen === UNCURATED ? null : $chosen;
}

/** What the drawer's gallery shows: the picked pictures, or all of them until some are picked. */
function drawer_gallery(array $row): array
{
    $all = drawer_gallery_all($row);
    $chosen = gallery_choice($row);
    if ($chosen === null) {
        return $all;
    }

    $byUrl = array_column($all, null, 'full');

    return array_values(array_filter(array_map(fn ($url) => $byUrl[$url] ?? null, $chosen)));
}

function drawer_section(array $row, string $key): mixed
{
    return match ($key) {
        'gallery'     => drawer_gallery($row) ?: null,
        'tracklist'   => (item_override($row, 'tracklist') ?? json_column($row['tracklist_json'] ?? null)) ?: null,
        'credits'     => drawer_credits(json_column($row['extraartists_json'] ?? null)) ?: null,
        'companies'   => drawer_companies(json_column($row['companies_json'] ?? null)) ?: null,
        'identifiers' => drawer_identifiers(json_column($row['identifiers_json'] ?? null)) ?: null,
        'videos'      => drawer_videos(json_column($row['videos_json'] ?? null)) ?: null,
        'links'       => drawer_links($row) ?: null,
        default       => null,
    };
}

/**
 * Names grouped under a heading, the way a sleeve prints credits:
 * [['role' => 'Producer', 'text' => 'RedOne, Space Cowboy (A1)'], …]
 */
function grouped_names(array $entries, string $groupKey, string $fallback, callable $name, string $suffixKey): array
{
    $groups = [];
    foreach ($entries as $entry) {
        $text = $name($entry);
        if ($text === '') {
            continue;
        }
        $groups[(string) ($entry[$groupKey] ?? $fallback)][] = $text . (!empty($entry[$suffixKey]) ? ' (' . $entry[$suffixKey] . ')' : '');
    }

    return array_map(
        fn ($group, $names) => ['role' => $group, 'text' => implode(', ', $names)],
        array_keys($groups),
        $groups
    );
}

function drawer_credits(array $credits): array
{
    return grouped_names($credits, 'role', 'Credit', fn ($credit) => clean_artist_name((string) ($credit['name'] ?? '')), 'tracks');
}

function drawer_companies(array $companies): array
{
    return grouped_names($companies, 'entity_type_name', 'Company', fn ($company) => (string) ($company['name'] ?? ''), 'catno');
}

function drawer_identifiers(array $identifiers): array
{
    return array_values(array_map(
        fn ($i) => [
            'role' => (string) ($i['type'] ?? ''),
            'text' => trim((string) ($i['value'] ?? '') . (!empty($i['description']) ? ' (' . $i['description'] . ')' : '')),
        ],
        $identifiers
    ));
}

function drawer_videos(array $videos): array
{
    return array_values(array_filter(array_map(
        fn ($v) => safe_http_url((string) ($v['uri'] ?? '')) ? [
            'label' => (string) ($v['title'] ?? 'Video'),
            'url'   => (string) $v['uri'],
        ] : null,
        $videos
    )));
}

function drawer_links(array $row): array
{
    $links = [];

    if (safe_http_url((string) ($row['uri'] ?? ''))) {
        $links[] = ['label' => 'Release on Discogs', 'url' => (string) $row['uri']];
    } elseif (!empty($row['discogs_id'])) {
        $links[] = ['label' => 'Release on Discogs', 'url' => discogs_release_url((int) $row['discogs_id'])];
    }
    if (!empty($row['master_id'])) {
        $links[] = ['label' => 'Master release', 'url' => 'https://www.discogs.com/master/' . (int) $row['master_id']];
    }

    return $links;
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
