<?php

/** The collection: every copy, searched and filtered, each a link to its edit page. */

require_once __DIR__ . '/../includes/admin.php';

require_login();

$all = all_games();
$updated = array_column(db()->query('SELECT id, updated_at FROM games')->fetchAll(), 'updated_at', 'id');

$f = [
    'q'        => query('q'),
    'console'  => query('console'),
    'format'   => query('format'),
    'launcher' => query('launcher'),
    'region'   => query('region'),
    'series'   => query('series'),
    'pack'     => query('pack'),
    'platform' => query('platform'),
    'flag'     => query('flag'),
    'sort'     => query('sort', 'shelf'),
];

$words = array_filter(explode(' ', norm_name($f['q'])));
$games = array_filter($all, function ($g) use ($f, $words) {
    if ($words) {
        $hay = norm_name(implode(' ', array_filter([$g['id'], $g['title'], $g['listedAs'] ?? null, $g['edition'] ?? null, $g['publisher'] ?? null,
            $g['developer'] ?? null, $g['genre'] ?? null, $g['series'] ?? null, $g['released'] ?? null, $g['note'] ?? null, console_name($g['console'])])));
        foreach ($words as $w) {
            if (!str_contains($hay, $w)) {
                return false;
            }
        }
    }
    // a console, or a whole family of them ("@playstation")
    if ($f['console'] !== '' && (str_starts_with($f['console'], '@') ? console_family($g['console']) !== substr($f['console'], 1) : $g['console'] !== $f['console'])) {
        return false;
    }
    $format = $g['format'] ?? '';
    if ($f['format'] === 'physical' && $format === 'digital') {
        return false;
    }
    if ($f['format'] !== '' && $f['format'] !== 'physical' && $format !== $f['format']) {
        return false;
    }
    if ($f['launcher'] !== '' && ($f['launcher'] === 'none' ? !empty($g['launcher']) : ($g['launcher'] ?? '') !== $f['launcher'])) {
        return false;
    }
    if ($f['region'] !== '' && ($g['region'] ?? '') !== $f['region']) {
        return false;
    }
    if ($f['series'] !== '' && ($g['series'] ?? '') !== $f['series']) {
        return false;
    }
    if ($f['pack'] !== '' && ($g['pack'] ?? '') !== $f['pack']) {
        return false;
    }
    if ($f['platform'] !== '' && !isset(game_platform_links($g)[$f['platform']])) {
        return false;
    }

    return match ($f['flag']) {
        'fav'     => !empty($g['fav']),
        'hidden'  => !empty($g['hidden']),
        'onsite'  => empty($g['hidden']),
        'nocover' => empty($g['cover']['file']),
        'check'   => !empty($g['check']),
        'bundle'  => !empty($g['contains']),
        'digitaltoo' => !empty($g['alsoDigital']),
        default   => true,
    };
});

$order = array_flip(array_keys(CONSOLES));
$sortTitle = fn ($g) => mb_strtolower(preg_replace('/^(the|a|an)\s+/i', '', $g['sortAs'] ?? $g['title']));
uasort($games, match ($f['sort']) {
    'title'    => fn ($a, $b) => strnatcasecmp($sortTitle($a), $sortTitle($b)),
    'released' => fn ($a, $b) => strcmp($b['released'] ?? '', $a['released'] ?? ''),
    'updated'  => fn ($a, $b) => strcmp($updated[$b['id']] ?? '', $updated[$a['id']] ?? ''),
    default    => fn ($a, $b) => [$order[$a['console']] ?? 99, $sortTitle($a)] <=> [$order[$b['console']] ?? 99, $sortTitle($b)],
});

// what the selects offer: only what the collection has
$present = fn (string $key) => array_values(array_unique(array_filter(array_map(fn ($g) => $g[$key] ?? null, $all))));
$series = $present('series');
sort($series);
$consoles = array_intersect_key(CONSOLES, array_flip($present('console')));
$regions = array_intersect_key(REGIONS, array_flip($present('region')));
$packs = array_values(array_intersect(PACKS, $present('pack')));
$filtering = array_filter(array_diff_key($f, ['sort' => 1])) !== [];

admin_header('Collection', count($all) . ' copies in the database. Pick one to change it.', '<a class="button primary" href="' . e(url('admin_game')) . '">+ Add a game</a>');
?>

<form class="filters card" method="get" action="<?= e(url('admin_games')) ?>" data-autosubmit>
  <div class="search">
    <svg viewBox="0 0 20 20" width="16" height="16" aria-hidden="true"><circle cx="8.5" cy="8.5" r="5.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M13 13l4.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
    <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Search title, id, publisher, year…" aria-label="Search">
  </div>
  <select name="console" aria-label="Console">
    <option value="">Every console</option>
    <optgroup label="Families"><?= options_html(['@playstation' => 'All PlayStation', '@nintendo' => 'All Nintendo', '@pc' => 'All PC', '@xbox' => 'All Xbox'], $f['console']) ?></optgroup>
    <optgroup label="Consoles"><?= options_html($consoles, $f['console']) ?></optgroup>
  </select>
  <select name="format" aria-label="Format"><?= options_html(['physical' => 'Physical (all)', 'digital' => 'Digital'] + array_diff_key(FORMATS, ['digital' => 1]), $f['format'], 'Physical & digital') ?></select>
  <select name="launcher" aria-label="Launcher"><?= options_html(['steam' => 'Steam', 'ea' => 'EA app', 'rockstar' => 'Rockstar Games', 'none' => 'No launcher'] + array_diff_key(LAUNCHERS, ['steam' => 1, 'ea' => 1, 'rockstar' => 1, 'none' => 1]), $f['launcher'], 'Any launcher') ?></select>
  <select name="platform" aria-label="Synced with"><?= options_html(array_map(fn ($p) => 'Tied to ' . $p['name'], PLATFORMS), $f['platform'], 'Any sync') ?></select>
  <select name="region" aria-label="Region"><?= options_html($regions, $f['region'], 'Any region') ?></select>
  <select name="series" aria-label="Series"><?= options_html(array_combine($series, $series), $f['series'], 'Any series') ?></select>
  <select name="pack" aria-label="Pack"><?= options_html(array_combine($packs, $packs), $f['pack'], 'Any pack') ?></select>
  <select name="flag" aria-label="Only"><?= options_html(['onsite' => 'On the site (not hidden)', 'fav' => 'Favourites', 'hidden' => 'Hidden', 'nocover' => 'No cover', 'check' => 'Still to check', 'bundle' => 'Bundles', 'digitaltoo' => 'Also in a launcher'], $f['flag'], 'Everything') ?></select>
  <select name="sort" aria-label="Sort"><?= options_html(['shelf' => 'By console', 'title' => 'Title A–Z', 'released' => 'Newest release', 'updated' => 'Last changed'], $f['sort']) ?></select>
  <noscript><button type="submit">Filter</button></noscript>
  <?php if ($filtering): ?><a class="clear" href="<?= e(url('admin_games')) ?>">Clear</a><?php endif; ?>
</form>

<?php $siteCount = collection_figures($games)['games']; ?>
<p class="count-line"><?= count($games) ?> of <?= count($all) ?> <?= plural(count($all), 'copy', 'copies') ?><?php if ($siteCount !== count($games)): ?> · <span title="As the site and the dashboard count them: hidden copies left out, a bundle counted as the packs in it"><?= $siteCount ?> <?= plural($siteCount, 'game') ?> as the site counts them</span><?php endif; ?></p>

<div class="card flush">
  <?php if (!$games): ?>
    <p class="empty">Nothing matches. <a href="<?= e(url('admin_games')) ?>">Show everything</a></p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table games">
      <thead>
        <tr><th></th><th>Title</th><th>Console</th><th>Copy</th><th>Region</th><th>Released</th><th>Synced</th></tr>
      </thead>
      <tbody>
      <?php foreach ($games as $g): $links = game_platform_links($g); ?>
        <tr<?= !empty($g['hidden']) ? ' class="is-hidden"' : '' ?>>
          <td class="c-thumb"><a href="<?= e(url('admin_game?id=' . rawurlencode($g['id']))) ?>" tabindex="-1"><?= cover_thumb($g) ?></a></td>
          <td class="c-title">
            <a href="<?= e(url('admin_game?id=' . rawurlencode($g['id']))) ?>"><?= e($g['title']) ?></a>
            <?php if (!empty($g['fav'])): ?><span class="star" title="Favourite">★</span><?php endif; ?>
            <?php if (!empty($g['hidden'])): ?><span class="pill">Hidden</span><?php endif; ?>
            <?php if (!empty($g['check'])): ?><span class="pill warn" title="<?= e($g['check']) ?>">Check</span><?php endif; ?>
            <div class="muted small"><?= e(implode(' · ', array_filter([$g['edition'] ?? null, $g['pack'] ?? null, !empty($g['contains']) ? count($g['contains']) . ' packs inside' : null]))) ?: '<code>' . e($g['id']) . '</code>' ?></div>
          </td>
          <td><?= e(console_name($g['console'])) ?></td>
          <td><?= e(FORMATS[$g['format'] ?? ''] ?? ($g['format'] ?? '—')) ?><?php if (!empty($g['launcher'])): ?><div class="muted small"><?= e(LAUNCHERS[$g['launcher']] ?? $g['launcher']) ?></div><?php endif; ?></td>
          <td><?= e($g['region'] ?? '—') ?></td>
          <td><?= e($g['released'] ?? '—') ?></td>
          <td class="c-dots"><?php foreach (array_keys($links) as $p): ?><span class="dot <?= e($p) ?>" title="<?= e(PLATFORMS[$p]['name']) ?>"></span><?php endforeach; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php admin_footer(); ?>
