<?php

/**
 * The pieces of the header every public page shares: the title, the record and
 * its tonearm, the numbers under the intro, and the pills that link on to the
 * artist pages.
 *
 * Everything in it that is a choice rather than data is edited in the admin:
 * the wording and switches on the Settings page (see hero_options()), and each
 * artist's picture on their own edit form.
 *
 * hero_title() and hero_platter() need no database (they fall back to the
 * defaults without one), so the 404 and 500 pages can use them. The rest read
 * the shelf and are for the pages that do.
 */

/** The numbers under the intro, by key, with the label each starts with. */
const HERO_STATS = [
    'records' => 'Records',
    'vinyl'   => 'On vinyl',
    'discs'   => 'CDs & DVDs',
    'oldest'  => 'Oldest release',
];

/**
 * The header's settings, over their defaults. Never throws: a page that is
 * already reporting a failure must still be able to draw its header.
 *
 * @return array<string, mixed>
 */
function hero_options(): array
{
    static $options = null;

    if ($options !== null) {
        return $options;
    }

    $defaults = [
        'eyebrow'        => 'Private collection · Synced from Discogs',
        'wantlist_label' => '♡ Wantlist',
        'split_title'    => true,
        'rule'           => true,
        'platter'        => true,
        'tonearm'        => true,
        'covers'         => true,
        'counts'         => true,
        'animate'        => true,
        'stats'          => true,
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

            // The two per-number lists are stored whole; a key missing from one
            // (a number added since it was saved) keeps its default.
            $options[$key] = is_array($default) && is_array($stored) ? $stored + $default : $stored;
        }
    } catch (Throwable) {
        $options = $defaults;
    }

    return $options;
}

function hero_escape(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * The title with its weight split: the last word bold and gold, the words
 * before it light. "The Collection" reads as The **Collection**.
 *
 * The artist pages are named "Lady Gaga Collection", where the name is the
 * point and "Collection" is the label on it, so there the weight goes the
 * other way round.
 */
function hero_title(string $title): string
{
    if (!hero_options()['split_title']) {
        return hero_escape($title);
    }

    $words = preg_split('/\s+/', trim($title), -1, PREG_SPLIT_NO_EMPTY) ?: [];

    if (count($words) < 2) {
        return '<b>' . hero_escape($title) . '</b>';
    }

    $last = array_pop($words);
    $rest = hero_escape(implode(' ', $words));
    $last = hero_escape($last);

    if (strcasecmp($last, 'Collection') === 0 && strcasecmp($rest, 'The') !== 0) {
        return "<b>$rest</b> <span>$last</span>";
    }

    return "<span>$rest</span> <b>$last</b>";
}

/**
 * The big record beside the title, cropped by the edge of the page, with a
 * tonearm resting on it. The grooves turn under a light that stays put, so it
 * reads as spinning. `$accent` colours the label (the artist pages pass theirs);
 * the wantlist's `sleeve` is an empty one, since nothing on it is owned yet, and
 * so has no needle to drop.
 */
function hero_platter(string $kind = 'record', string $accent = '#C99A2E'): string
{
    $options = hero_options();

    if (!$options['platter']) {
        return '';
    }

    $accent = hero_escape($accent);
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
        // Drawn on the record's own 0-100 grid: the pivot stands just off the
        // rim and the needle lands on the grooves, about 25 out from the centre.
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

/** The line above the title, or nothing: an empty one in the admin hides it. */
function hero_eyebrow(): string
{
    $text = trim((string) hero_options()['eyebrow']);

    return $text === '' ? '' : '<div class="hero-eyebrow">' . hero_escape($text) . '</div>';
}

/** The small rule under the title. */
function hero_rule(): string
{
    return hero_options()['rule'] ? '<div class="hero-rule" aria-hidden="true"></div>' : '';
}

/** Added to the header's class list: `hero-still` switches its movement off. */
function hero_classes(): string
{
    return hero_options()['animate'] ? '' : ' hero-still';
}

/**
 * What is on the shelf, in numbers. `$artistId` narrows it to one artist's
 * records. The light query, not public_items(): the header needs a count and a
 * year per record, not every column of every release.
 *
 * @return array{records: int, vinyl: int, discs: int, oldest: int}
 */
function hero_figures(?int $artistId = null): array
{
    $sql = "
        SELECT i.media_kind, i.release_date, r.year
          FROM items i
          LEFT JOIN releases r ON r.discogs_id = i.release_id
         WHERE i.source = 'collection' AND i.is_visible = 1 AND i.missing_since IS NULL";
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

/** The numbers under the intro. The digits are the real ones; js/hero.js only counts up to them. */
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
        $counts = $key !== 'oldest';  // a year is not a count
        $html .= '<div class="hero-stat"><b' . ($counts ? ' data-n="' . $figures[$key] . '"' : '') . '>'
            . $figures[$key] . '</b><span>' . hero_escape($label) . '</span></div>';
    }

    return $html === '' ? '' : '<div class="hero-stats" role="group" aria-label="The shelf in numbers">' . $html . '</div>';
}

/**
 * The picture on an artist's pill: the one chosen in the admin, else the first
 * of their records that has a cover, in the order their page starts with.
 */
function hero_artist_cover(array $artist): string
{
    if (!empty($artist['hero_cover'])) {
        return (string) $artist['hero_cover'];
    }

    $stmt = db()->prepare(ITEM_SELECT . "
         WHERE i.source = 'collection' AND i.is_visible = 1 AND i.missing_since IS NULL AND i.artist_id = ?
           AND COALESCE(NULLIF(i.cover_url, ''), NULLIF(r.thumb, ''), NULLIF(r.cover_image, '')) IS NOT NULL
         ORDER BY i.sort_rank DESC, " . RELEASE_DATE_SQL . ', r.title COLLATE NOCASE
         LIMIT 1');
    $stmt->execute([$artist['id']]);
    $row = $stmt->fetch();

    return $row ? item_thumb($row) : '';
}

/**
 * The search box that opens the controls band on every public page. On a
 * desktop it is the bare input; on a phone it folds down to a magnifying glass
 * that opens the input across the bar (css/floor.css, js/controls.js).
 */
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
 * The pills to the artist pages: a round cover, the name, how many records.
 * `$exceptId` leaves out the artist whose page this is; `$wantlist` adds the
 * link to the wantlist, when it is public.
 *
 * A phone has no room for a row of pills, so the same links are drawn a second
 * time as a dropdown with the wantlist beside it (see .hero-pick in css/floor.css
 * and js/hero.js). The stylesheet shows one or the other.
 */
function hero_links(?int $exceptId = null, bool $wantlist = false, string $label = 'Artist pages'): string
{
    $options = hero_options();
    $artists = db()->query('SELECT id, slug, name, hero_cover FROM artists WHERE is_published = 1 ORDER BY position, name')->fetchAll();
    $artists = array_values(array_filter($artists, fn ($a) => (int) $a['id'] !== $exceptId));
    $wantlist = $wantlist && setting('show_wantlist', true);

    if (!$artists && !$wantlist) {
        return '';
    }

    $counts = [];
    if ($options['counts']) {
        $rows = db()->query("
            SELECT artist_id, COUNT(*) AS n FROM items
             WHERE source = 'collection' AND is_visible = 1 AND missing_since IS NULL AND artist_id IS NOT NULL
             GROUP BY artist_id")->fetchAll();
        foreach ($rows as $row) {
            $counts[(int) $row['artist_id']] = (int) $row['n'];
        }
    }

    $pills = '';
    foreach ($artists as $artist) {
        $thumb = $options['covers'] ? hero_artist_cover($artist) : '';

        $pills .= '<a href="' . e(url($artist['slug'])) . '">'
            . ($options['covers']
                ? '<span class="hero-cover">' . ($thumb !== '' ? '<img src="' . e($thumb) . '" alt="" width="28" height="28" loading="lazy">' : '') . '</span>'
                : '')
            . e($artist['name'])
            . (isset($counts[(int) $artist['id']]) ? '<i>' . $counts[(int) $artist['id']] . '</i>' : '') . '</a>';
    }

    $wanted = $wantlist
        ? '<a class="wanted" href="' . e(url('wantlist')) . '">' . e(trim((string) $options['wantlist_label']) ?: '♡ Wantlist') . '</a>'
        : '';

    $html = '<nav class="hero-links' . ($options['covers'] ? '' : ' no-covers') . '" aria-label="' . e($label) . '">'
        . $pills . $wanted . '</nav>';

    // The phone's version: the artists folded into a menu, the wantlist beside it.
    $menu = $artists
        ? '<div class="hero-menu dd"><button type="button" class="dd-button" aria-haspopup="true" aria-expanded="false">'
            . ($exceptId === null ? 'Collections' : 'More collections')
            . '</button><div class="dd-menu" hidden>' . $pills . '</div></div>'
        : '';

    return $html . '<nav class="hero-pick' . ($options['covers'] ? '' : ' no-covers') . '" aria-label="' . e($label) . '">'
        . $menu . $wanted . '</nav>';
}
