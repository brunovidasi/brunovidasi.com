<?php

/**
 * The discs inside a sleeve, as the shelf draws them sliding out: how many,
 * what kind, and each one's colour and picture.
 */

/** Matched against Discogs' free-text colours ("Red Translucent"), in this order. */
const VINYL_COLORS = [
    ['red', '#c8322b'], ['pink', '#f06aa8'], ['blue', '#2f6fdd'], ['aqua', '#3cc6d4'],
    ['turquoise', '#2bc0b4'], ['green', '#2f9e58'], ['yellow', '#f0c828'], ['orange', '#ee7d1e'],
    ['purple', '#7a3fb5'], ['violet', '#7a3fb5'], ['gold', '#d4a836'], ['silver', '#b8bcc2'],
    ['grey', '#8a8d92'], ['gray', '#8a8d92'], ['smoky', '#6c6f74'], ['white', '#f1f1ec'],
    ['brown', '#7a4b2a'], ['clear', '#dfe8ec'], ['black', '#131313'],
];

/** How a vinyl is drawn when nothing in its colour text matches (the CSS default too). */
const VINYL_FALLBACK_COLOR = '#131313';

/** The most discs one sleeve fans out; more stops reading as a stack. */
const MAX_DISCS = 3;

/** ['c' => colour or null, 'tr' => translucent] from a colour text. */
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

/** A colour picked in the admin as lower-case "#rrggbb", or null if it isn't one. */
function vinyl_hex(?string $text): ?string
{
    $text = strtolower(trim((string) $text));

    return preg_match('/^#[0-9a-f]{6}$/', $text) ? $text : null;
}

/** true, false, or null for "not set", from a stored or posted 1 / 0. */
function optional_bool(mixed $value): ?bool
{
    return match ($value) {
        1, '1', true => true,
        0, '0', false => false,
        default => null,
    };
}

/**
 * What the admin has said about a record's discs: how many (null = as Discogs
 * says), the picture on each ('' for none), and each vinyl's colour and
 * translucency (null = automatic). `set` is false for a record never
 * configured, which may still have one disc_url for every disc.
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
        $tr[] = optional_bool($raw['tr'][$k] ?? null);
    }

    return ['set' => true, 'count' => $count, 'art' => $art, 'hex' => $hex, 'tr' => $tr];
}

/**
 * The colour picked for each disc, falling back to the one picked for the
 * whole record before colours were set disc by disc. Null for a disc on automatic.
 *
 * @return array{hex: (?string)[], tr: (?bool)[]}
 */
function disc_colours(array $row): array
{
    $config = disc_config($row);
    $hex = vinyl_hex($row['vinyl_hex'] ?? null);
    $tr = optional_bool($row['vinyl_translucent'] ?? null);

    foreach (array_keys($config['hex']) as $k) {
        $config['hex'][$k] ??= $hex;
        $config['tr'][$k] ??= $tr;
    }

    return ['hex' => $config['hex'], 'tr' => $config['tr']];
}

/**
 * The discs inside a sleeve. Discogs' formats say what they are; the admin's
 * disc count, colours and pictures override that disc by disc. Something filed
 * as "other" (a cassette, a t-shirt) has no disc at all.
 */
function item_discs(array $row, array $formats): array
{
    if ($row['media_kind'] === 'other') {
        return [];
    }

    $override = trim((string) ($row['vinyl_color'] ?? ''));
    $config = disc_config($row);
    $colours = disc_colours($row);
    // An old single disc picture, from before discs were set one by one, makes every vinyl a picture disc.
    $picture = !$config['set'] && trim((string) ($row['disc_url'] ?? '')) !== '';
    $discs = [];

    foreach ($formats as $format) {
        $name = (string) ($format['name'] ?? '');
        $descriptions = $format['descriptions'] ?? [];
        $count = min(MAX_DISCS, max(1, (int) ($format['qty'] ?? 1)));

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

    // A disc with no Discogs release: its Media box says what is in the sleeve ("CD + DVD", "2 CD").
    if (!$discs && !$formats) {
        foreach (explode('+', (string) ($row['media'] ?? '')) as $part) {
            if (preg_match('/^\s*(?:(\d+)\s*[x×]?\s*)?(CD|DVD|Blu-?ray)\s*$/i', $part, $m)) {
                $type = ['cd' => 'cd', 'dvd' => 'dvd'][strtolower($m[2])] ?? 'bd';
                array_push($discs, ...array_fill(0, min(MAX_DISCS, max(1, (int) $m[1])), ['t' => $type]));
            }
        }
    }

    if (!$discs) {
        $discs[] = ['t' => match ($row['media_kind']) {
            'vinyl'  => 'v',
            'dvd'    => 'dvd',
            'bluray' => 'bd',
            default  => 'cd',
        }];
    }

    $discs = array_slice($discs, 0, MAX_DISCS);

    // A count set by hand drops discs from the back, or repeats the last one.
    if ($config['count'] !== null) {
        $last = end($discs);
        while (count($discs) < $config['count']) {
            $discs[] = $last;
        }
        $discs = array_slice($discs, 0, $config['count']);
    }

    foreach ($discs as $k => $disc) {
        if ($disc['t'] === 'v') {
            if ($colours['hex'][$k] !== null) {
                $discs[$k]['c'] = $colours['hex'][$k];
            }
            if ($colours['tr'][$k] !== null) {
                $discs[$k]['tr'] = $colours['tr'][$k];
            }
        }
        if (($config['art'][$k] ?? '') !== '') {
            $discs[$k]['art'] = $config['art'][$k];
            if ($disc['t'] === 'v') {
                $discs[$k]['pic'] = true;
            }
        }
    }

    return $discs;
}

function item_formats(array $row): array
{
    return json_column($row['formats_json'] ?? null);
}

/** The first vinyl disc of a record as the shelf draws it, or null if it has none. */
function first_vinyl_disc(array $row): ?array
{
    foreach (item_discs($row, item_formats($row)) as $disc) {
        if ($disc['t'] === 'v') {
            return $disc;
        }
    }

    return null;
}
