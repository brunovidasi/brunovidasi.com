<?php

/**
 * The header every public page shares: the title, the spinning record, the
 * numbers under the intro, and the pills linking to the artist pages. Its
 * options are edited on the admin's Settings page.
 *
 * hero_title() and hero_platter() work without a database, for the error pages.
 */

const DEFAULT_SITE_TITLE = 'The Collection';
const DEFAULT_SITE_INTRO = 'Every record, CD and disc Bruno owns, straight from the Discogs shelf.';

const HERO_STATS = [
    'records' => 'Records',
    'vinyl'   => 'On vinyl',
    'discs'   => 'CDs & DVDs',
    'oldest'  => 'Oldest release',
];

const HERO_SWITCHES = ['split_title', 'rule', 'platter', 'tonearm', 'covers', 'counts', 'animate', 'stats'];

const HERO_DEFAULT_WANTLIST_LABEL = '♡ Wantlist';
const HERO_DEFAULT_SELLING_LABEL = '🛍 Selling';

/**
 * The header's settings over their defaults. Never throws, so a page already
 * reporting a failure can still draw its header.
 */
function hero_options(): array
{
    static $options = null;

    if ($options !== null) {
        return $options;
    }

    $defaults = [
        'eyebrow'        => 'Private collection · Synced from Discogs',
        'wantlist_label' => HERO_DEFAULT_WANTLIST_LABEL,
        'selling_label'  => HERO_DEFAULT_SELLING_LABEL,
        ...array_fill_keys(HERO_SWITCHES, true),
        'stat_show'      => array_fill_keys(array_keys(HERO_STATS), true),
        'stat_label'     => HERO_STATS,
    ];
    $options = $defaults;

    if (!function_exists('setting')) {
        return $options;
    }

    try {
        foreach ($defaults as $key => $default) {
            $stored = setting('hero_' . $key, $default);
            // A number added since the list was saved keeps its default.
            $options[$key] = is_array($default) && is_array($stored) ? $stored + $default : $stored;
        }
    } catch (Throwable) {
        $options = $defaults;
    }

    return $options;
}

/**
 * The title with its weight split: the last word bold and gold, the rest
 * light. On an artist page ("Lady Gaga Collection") it goes the other way.
 */
function hero_title(string $title): string
{
    if (!hero_options()['split_title']) {
        return e($title);
    }

    $words = preg_split('/\s+/', trim($title), -1, PREG_SPLIT_NO_EMPTY) ?: [];

    if (count($words) < 2) {
        return '<b>' . e($title) . '</b>';
    }

    $last = e(array_pop($words));
    $rest = e(implode(' ', $words));

    if (strcasecmp($last, 'Collection') === 0 && strcasecmp($rest, 'The') !== 0) {
        return "<b>$rest</b> <span>$last</span>";
    }

    return "<span>$rest</span> <b>$last</b>";
}

/**
 * The big record beside the title, with its tonearm. `$accent` colours the
 * label; a 'sleeve' is an empty one, with no needle to drop.
 */
function hero_platter(string $kind = 'record', string $accent = '#C99A2E'): string
{
    $options = hero_options();

    if (!$options['platter']) {
        return '';
    }

    $accent = e($accent);
    $sleeve = $kind === 'sleeve';

    if ($sleeve) {
        $disc = '
        <circle cx="50" cy="50" r="49.5" fill="#0B0A08" stroke="#3a352c" stroke-width="0.8"/>
        <circle cx="50" cy="50" r="40" fill="none" stroke="#2a2620" stroke-width="0.6" stroke-dasharray="4 3"/>
        <circle cx="50" cy="50" r="28" fill="none" stroke="#2a2620" stroke-width="0.6" stroke-dasharray="4 3"/>
        <circle cx="50" cy="50" r="16" fill="none" stroke="#C99A2E" stroke-width="1.6" stroke-dasharray="3 3"/>
        <circle cx="50" cy="50" r="2.6" fill="#0B0A08" stroke="#3a352c" stroke-width="0.8"/>';
        $arm = '';
    } else {
        $grooves = '';
        foreach ([46, 43, 40, 37, 34, 31, 28, 25, 22] as $r) {
            $grooves .= "<circle cx=\"50\" cy=\"50\" r=\"$r\" fill=\"none\" stroke=\"#2a2620\" stroke-width=\"0.5\"/>";
        }
        $disc = '
        <circle cx="50" cy="50" r="49.5" fill="#0B0A08" stroke="#3a352c" stroke-width="0.8"/>
        <path d="M50 50 L50 1 A49 49 0 0 1 89 20.5 Z" fill="#F2EAD8" opacity="0.06"/>
        <path d="M50 50 L50 99 A49 49 0 0 1 11 79.5 Z" fill="#F2EAD8" opacity="0.04"/>
        ' . $grooves . '
        <circle class="accent" cx="50" cy="50" r="16" fill="' . $accent . '"/>
        <path d="M50 37.5 A12.5 12.5 0 0 1 60.8 43.75" fill="none" stroke="#0B0A08" stroke-width="1.6" stroke-linecap="round" opacity="0.45"/>
        <circle cx="50" cy="50" r="2.6" fill="#0B0A08"/>';
        // On the record's own 0–100 grid: the pivot just off the rim, the needle on the grooves.
        $arm = '
    <svg class="hero-arm" viewBox="0 0 100 100" aria-hidden="true">
      <line x1="78" y1="0" x2="85" y2="-9" stroke="#7b7362" stroke-width="4.4" stroke-linecap="round"/>
      <path d="M78 0 L64 15 L58 22" fill="none" stroke="#d8cfba" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
      <path d="M78 0 L64 15 L58 22" fill="none" stroke="rgba(255,255,255,.5)" stroke-width=".5" stroke-linecap="round" transform="translate(-.5,-.5)"/>
      <g transform="translate(58 22) rotate(133)">
        <rect x="-1" y="-1.9" width="6.4" height="3.8" rx=".8" fill="#2a2620" stroke="#5a5342" stroke-width=".4"/>
        <rect x="3.6" y="-1" width="2.6" height="2" fill="#C99A2E"/>
      </g>
      <circle cx="78" cy="0" r="6" fill="#26211a" stroke="#5a5342" stroke-width=".7"/>
      <circle cx="78" cy="0" r="2.4" fill="#C99A2E"/>
    </svg>';
    }

    if (!$options['tonearm']) {
        $arm = '';
    }

    return '<div class="hero-platter' . ($sleeve ? ' is-sleeve' : '') . '" aria-hidden="true">
    <div class="hero-spin"><svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">' . $disc . '
    </svg></div>' . $arm . '
  </div>';
}

/** The line above the title; an empty one in the admin hides it. */
function hero_eyebrow(): string
{
    $text = trim((string) hero_options()['eyebrow']);

    return $text === '' ? '' : '<div class="hero-eyebrow">' . e($text) . '</div>';
}

function hero_rule(): string
{
    return hero_options()['rule'] ? '<div class="hero-rule" aria-hidden="true"></div>' : '';
}

/** Extra classes for the header: 'hero-still' switches its movement off. */
function hero_classes(): string
{
    return hero_options()['animate'] ? '' : ' hero-still';
}

/**
 * The header settles in only on the first view of a page in a tab; a reload,
 * the back button or a page opened in the background shows it already there
 * ('hero-settled'), with the record still turning. Goes first inside
 * <header>, so it runs before any of it is drawn.
 */
function hero_intro(): string
{
    if (!hero_options()['animate']) {
        return '';
    }

    return "<script>(function (h) { try { var k = 'hero-seen:' + location.pathname + location.search;"
        . " if (document.visibilityState === 'hidden' || sessionStorage.getItem(k)) h.classList.add('hero-settled');"
        . " sessionStorage.setItem(k, '1'); } catch (e) {} })(document.currentScript.parentNode);</script>";
}

/**
 * The shelf in numbers, optionally for one artist.
 *
 * @return array{records: int, vinyl: int, discs: int, oldest: int}
 */
function hero_figures(?int $artistId = null): array
{
    $sql = "
        SELECT i.media_kind, i.release_date, r.year
          FROM items i
          LEFT JOIN releases r ON r.discogs_id = i.release_id
         WHERE i.source = 'collection' AND " . PUBLIC_ITEM_WHERE;
    $params = [];

    if ($artistId !== null) {
        $sql .= ' AND i.artist_id = ?';
        $params[] = $artistId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    $figures = ['records' => 0, 'vinyl' => 0, 'discs' => 0, 'oldest' => 0];

    foreach ($stmt->fetchAll() as $row) {
        $figures['records']++;

        if ($row['media_kind'] === 'vinyl') {
            $figures['vinyl']++;
        } elseif (in_array($row['media_kind'], ['cd', 'dvd', 'bluray'], true)) {
            $figures['discs']++;
        }

        $year = item_year($row);
        if ($year > 1900 && ($figures['oldest'] === 0 || $year < $figures['oldest'])) {
            $figures['oldest'] = $year;
        }
    }

    return $figures;
}

/** The numbers under the intro. js/hero.js counts up to them. */
function hero_stats(array $figures): string
{
    $options = hero_options();

    if (!$options['stats'] || $figures['records'] < 1) {
        return '';
    }

    $html = '';
    foreach (HERO_STATS as $key => $default) {
        if (!$options['stat_show'][$key] || $figures[$key] < 1) {
            continue;
        }
        $label = trim((string) $options['stat_label'][$key]) ?: $default;
        $isCount = $key !== 'oldest';
        $html .= '<div class="hero-stat"><b' . ($isCount ? ' data-n="' . $figures[$key] . '"' : '') . '>'
            . $figures[$key] . '</b><span>' . e($label) . '</span></div>';
    }

    return $html === '' ? '' : '<div class="hero-stats" role="group" aria-label="The shelf in numbers">' . $html . '</div>';
}

/** The picture on an artist's pill: the one picked in the admin, else their first record with a cover. */
function hero_artist_cover(array $artist): string
{
    if (!empty($artist['hero_cover'])) {
        return (string) $artist['hero_cover'];
    }

    $stmt = db()->prepare(ITEM_SELECT . "
         WHERE i.source = 'collection' AND " . PUBLIC_ITEM_WHERE . " AND i.artist_id = ?
           AND COALESCE(NULLIF(i.cover_url, ''), NULLIF(r.thumb, ''), NULLIF(r.cover_image, '')) IS NOT NULL
         ORDER BY i.sort_rank DESC, " . RELEASE_DATE_SQL . ', r.title COLLATE NOCASE
         LIMIT 1');
    $stmt->execute([$artist['id']]);
    $row = $stmt->fetch();

    return $row ? item_thumb($row) : '';
}

/** The search box: the bare input on a desktop, a magnifying glass that opens it on a phone. */
function hero_search(string $placeholder, string $label): string
{
    return '<div class="search" id="searchBox">
      <button type="button" class="search-open" aria-label="Search" aria-expanded="false" aria-controls="search">
        <svg viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="8.5" cy="8.5" r="5.5"/><path d="M13 13l4.5 4.5"/></svg>
      </button>
      <input type="search" id="search" placeholder="' . e($placeholder) . '" aria-label="' . e($label) . '">
      <button type="button" class="search-close" aria-label="Clear search and close">✕</button>
    </div>';
}

/**
 * The pills to the artist pages, leaving out `$exceptId`'s own, with the
 * wantlist and selling links when those are public. A phone gets the same
 * links again folded into a menu; the stylesheet shows one or the other.
 */
function hero_links(?int $exceptId = null, bool $wantlist = false, string $label = 'Artist pages', bool $selling = false): string
{
    $options = hero_options();
    $artists = array_values(array_filter(published_artists(), fn ($a) => (int) $a['id'] !== $exceptId));
    $wantlist = $wantlist && setting('show_wantlist', true);
    $selling = $selling && setting('show_selling', true);

    if (!$artists && !$wantlist && !$selling) {
        return '';
    }

    $counts = $options['counts'] ? artist_record_counts(visibleOnly: true) : [];

    $pills = '';
    foreach ($artists as $artist) {
        $cover = '';
        if ($options['covers']) {
            $thumb = hero_artist_cover($artist);
            $cover = '<span class="hero-cover">' . ($thumb !== '' ? '<img src="' . e($thumb) . '" alt="" width="28" height="28" loading="lazy">' : '') . '</span>';
        }
        $count = isset($counts[(int) $artist['id']]) ? '<i>' . $counts[(int) $artist['id']] . '</i>' : '';

        $pills .= '<a href="' . e(url($artist['slug'])) . '">' . $cover . e($artist['name']) . $count . '</a>';
    }

    $wanted = $wantlist ? hero_special_link('wanted', 'wantlist', $options['wantlist_label'], HERO_DEFAULT_WANTLIST_LABEL) : '';
    $sellingLink = $selling ? hero_special_link('selling', 'selling', $options['selling_label'], HERO_DEFAULT_SELLING_LABEL) : '';
    $noCovers = $options['covers'] ? '' : ' no-covers';

    $menu = $artists
        ? '<div class="hero-menu dd"><button type="button" class="dd-button" aria-haspopup="true" aria-expanded="false">'
            . ($exceptId === null ? 'Collections' : 'More collections')
            . '</button><div class="dd-menu" hidden>' . $pills . '</div></div>'
        : '';

    return '<nav class="hero-links' . $noCovers . '" aria-label="' . e($label) . '">' . $pills . $sellingLink . $wanted . '</nav>'
        . '<nav class="hero-pick' . $noCovers . '" aria-label="' . e($label) . '">' . $menu . $sellingLink . $wanted . '</nav>';
}

function hero_special_link(string $class, string $page, mixed $label, string $default): string
{
    return '<a class="' . $class . '" href="' . e(url($page)) . '">' . e(trim((string) $label) ?: $default) . '</a>';
}
