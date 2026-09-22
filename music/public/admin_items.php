<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$source = in_array(query('source'), ['collection', 'wantlist', 'searching'], true) ? query('source') : 'collection';
$perPage = 50;

/*
 * The filters, the sort and the page are remembered per list, in the settings table, so
 * they survive a reload, a trip to an edit page and back, and the next visit.
 * A request that names any of them (the Filter button, a column header, a page
 * link) is taken as the new state and saved; a bare visit — the nav tab, the
 * "Back to the list" of an edit page — gets the saved state back. Clear forgets it.
 */
const LIST_KEYS = ['q', 'kind', 'artist', 'era', 'state', 'sort', 'dir', 'page'];

$saved = setting('admin_list_filters', []);

if (query('reset') !== '') {
    unset($saved[$source]);
    set_setting('admin_list_filters', $saved);
    redirect('admin_items?source=' . $source);
}

if (isset($_GET['f']) || array_intersect_key($_GET, array_flip(LIST_KEYS))) {
    $f = array_filter(array_combine(LIST_KEYS, array_map('query', LIST_KEYS)), fn ($v) => $v !== '');
    if ($f !== ($saved[$source] ?? [])) {
        $saved[$source] = $f;
        set_setting('admin_list_filters', $saved);
    }
} else {
    $f = $saved[$source] ?? [];
}

// Not $page: the layout below uses that name for the current admin page.
$pageNo = max(1, (int) ($f['page'] ?? 1));
$search = $f['q'] ?? '';
$kind = $f['kind'] ?? '';
$artistId = (int) ($f['artist'] ?? 0);
$eraId = $f['era'] ?? '';
$state = $f['state'] ?? '';

// The columns that can be sorted on, keyed as they appear in the URL.
const LIST_SORTS = ['album', 'artist', 'barcode', 'kind', 'type', 'year', 'era'];
// What the Show box offers, grouped. Anything else (or nothing) is the shelf itself.
const LIST_STATES = [
    'Discs and pictures' => ['multi' => 'More than 1 disc', 'pic' => 'With a picture disc', 'nopic' => 'Without a picture disc', 'cover' => 'With a cover I picked', 'nocover' => 'No cover picked yet', 'nocolor' => 'Vinyl with no colour recognised'],
    'Still to do'      => ['noera' => 'Missing an era', 'blank' => 'Nothing filled in yet'],
    'Not on the shelf' => ['hidden' => 'Hidden from the site', 'missing' => 'No longer on Discogs'],
];
$stateLabels = array_merge(...array_values(LIST_STATES));

$sort = in_array($f['sort'] ?? '', LIST_SORTS, true) ? $f['sort'] : '';
$dir = ($f['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

$artists = all_artists();

// The Era box always exists, so the bar never changes shape when an artist is
// picked: every era while none is (grouped by artist), only that artist's once
// one is.
$eras = db()->query('
    SELECT e.id, e.name, e.artist_id, a.name AS artist
      FROM eras e
      JOIN artists a ON a.id = e.artist_id
     ORDER BY a.position, a.name, e.position, e.name
')->fetchAll();
if ($artistId > 0) {
    $eras = array_values(array_filter($eras, fn ($era) => (int) $era['artist_id'] === $artistId));
}

// An era left over from before the artist changed is no longer one of the
// choices, so it must not go on filtering invisibly.
if ($eraId !== 'none' && !in_array((int) $eraId, array_map('intval', array_column($eras, 'id')), true)) {
    $eraId = '';
}

$where = ['i.source = ?'];
$params = [$source];

if ($search !== '') {
    $where[] = '(r.title LIKE ? OR r.artists_text LIKE ? OR i.manual_title LIKE ? OR i.manual_artist LIKE ? OR i.barcode LIKE ? OR r.barcode LIKE ? OR i.notes LIKE ?)';
    $params = array_merge($params, array_fill(0, 7, '%' . $search . '%'));
}
if (isset(MEDIA_KINDS[$kind])) {
    $where[] = 'i.media_kind = ?';
    $params[] = $kind;
}
if ($artistId > 0) {
    $where[] = 'i.artist_id = ?';
    $params[] = $artistId;
}
if ($eraId === 'none') {
    $where[] = 'i.era_id IS NULL';
} elseif ($eraId !== '') {
    $where[] = 'i.era_id = ?';
    $params[] = (int) $eraId;
}

// "State" is about the row's standing rather than its content: gone from
// Discogs, hidden from the site, or never given any of Bruno's own fields.
match ($state) {
    'missing' => $where[] = 'i.missing_since IS NOT NULL',
    'hidden'  => $where[] = 'i.is_visible = 0',
    'blank'   => $where[] = "(COALESCE(i.notes, '') = '' AND COALESCE(i.barcode, '') = '' AND COALESCE(i.region, '') = '')",
    'noera'   => $where[] = 'i.era_id IS NULL AND i.artist_id IS NOT NULL',
    // How many discs is worked out in PHP, from the same rules the shelf draws
    // with (see item_discs()), so this only narrows to the shelf; the filtering
    // itself happens below, once the rows are read.
    'multi'   => $where[] = 'i.missing_since IS NULL',
    // Likewise worked out in PHP: vinyls whose colour text matches none of the keywords (a picture disc has no colour to match).
    'nocolor' => $where[] = "i.missing_since IS NULL AND i.media_kind = 'vinyl'",
    // A picture disc, in any format: disc art picked on the edit page, or Discogs
    // describing the pressing as one. The Format filter narrows it to vinyl, CD…
    'pic'     => $where[] = "i.missing_since IS NULL AND (COALESCE(i.disc_url, '') <> '' OR r.formats_json LIKE '%\"Picture Disc\"%')",
    'nopic'   => $where[] = "i.missing_since IS NULL AND COALESCE(i.disc_url, '') = '' AND COALESCE(r.formats_json, '') NOT LIKE '%\"Picture Disc\"%'",
    // "Picked" is the cover chosen on the edit page, as against Discogs' own default.
    'cover'   => $where[] = "i.missing_since IS NULL AND COALESCE(i.cover_url, '') <> ''",
    'nocover' => $where[] = "i.missing_since IS NULL AND COALESCE(i.cover_url, '') = ''",
    default   => $where[] = 'i.missing_since IS NULL',
};

$whereSql = implode(' AND ', $where);

// Only what the table draws, not the whole release (tracklists, image galleries
// and so on): the list is sorted in PHP, so every matching row is read.
$stmt = db()->prepare("
    SELECT i.*,
           r.discogs_id, r.title, r.artists_text, r.year, r.released, r.released_formatted,
           r.barcode AS release_barcode, r.formats_json, r.thumb, r.cover_image,
           e.name AS era_name, e.position AS era_position, ea.name AS era_artist
      FROM items i
      LEFT JOIN releases r ON r.discogs_id = i.release_id
      LEFT JOIN eras e ON e.id = i.era_id
      LEFT JOIN artists ea ON ea.id = e.artist_id
     WHERE $whereSql
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// "More than 1 disc": a 2-CD set, a double LP, a record whose count was set by hand.
if ($state === 'multi') {
    $rows = array_values(array_filter($rows, fn (array $row) => count(item_discs($row, json_column($row['formats_json'] ?? null))) > 1));
}

/** The first vinyl disc of a record as the shelf would draw it, or null if it has none. */
function first_vinyl_disc(array $row): ?array
{
    foreach (item_discs($row, json_column($row['formats_json'] ?? null)) as $disc) {
        if ($disc['t'] === 'v') {
            return $disc;
        }
    }

    return null;
}

if ($state === 'nocolor') {
    $rows = array_values(array_filter($rows, function (array $row): bool {
        $disc = first_vinyl_disc($row);

        return $disc !== null && $disc['c'] === null && !$disc['pic'];
    }));
}

/**
 * What the table shows for a record, and what it sorts by. Sorting is done here
 * rather than in SQL because these are the values the site itself would use —
 * a barcode or type left blank falls back to Discogs' — and those are worked
 * out in PHP (see item_field_value()).
 */
function list_row(array $row): array
{
    $release = $row['discogs_id'] !== null ? [...$row, 'barcode' => $row['release_barcode']] : null;
    $barcode = trim((string) item_field_value($row, $release, 'barcode'));
    $type = trim((string) item_field_value($row, $release, 'item_type'));

    return $row + [
        'cell_barcode'  => $barcode,
        'own_barcode'   => trim((string) $row['barcode']) !== '',
        'cell_type'     => $type,
        'own_type'      => trim((string) $row['item_type']) !== '',
        'disc'          => $row['media_kind'] === 'vinyl' ? first_vinyl_disc($row) : null,
        'sort_date'     => item_sort_date($row),
        'sort_era'      => $row['era_name'] !== null ? mb_strtolower($row['era_artist'] . sprintf('%06d', $row['era_position'])) : '',
    ];
}

$rows = array_map('list_row', $rows);

$sortValue = fn (array $row, string $by): string => match ($by) {
    'album'   => mb_strtolower(item_title($row)),
    'artist'  => mb_strtolower(item_artist($row)),
    'barcode' => $row['cell_barcode'],
    'kind'    => media_kind_label($row['media_kind']),
    'type'    => $row['cell_type'],
    'year'    => $row['sort_date'],
    'era'     => $row['sort_era'],
    default   => '',
};

// The order the list has always had — artist, release date, title — is the
// tie-breaker under any chosen column, and the whole order when none is.
$tieBreak = fn (array $row): string => implode("\0", [mb_strtolower(item_artist($row)), $row['sort_date'], mb_strtolower(item_title($row))]);

usort($rows, function (array $a, array $b) use ($sort, $dir, $sortValue, $tieBreak): int {
    if ($sort !== '') {
        [$x, $y] = [$sortValue($a, $sort), $sortValue($b, $sort)];
        // A blank goes last whichever way the column runs; it isn't "smallest".
        if (($x === '') !== ($y === '')) {
            return $x === '' ? 1 : -1;
        }
        if ($x !== $y) {
            // strcmp, not <=>: two barcodes are numeric strings, which <=> would compare as numbers.
            return ($dir === 'desc' ? -1 : 1) * strcmp($x, $y);
        }
    }

    return strcmp($tieBreak($a), $tieBreak($b));
});

$total = count($rows);
$pages = max(1, (int) ceil($total / $perPage));
$pageNo = min($pageNo, $pages);
$rows = array_slice($rows, ($pageNo - 1) * $perPage, $perPage);

/** A link to this list with some of the state changed; page resets unless given. */
function items_link(array $overrides): string
{
    global $f, $source;

    $params = array_filter(['source' => $source, 'f' => '1'] + array_merge($f, $overrides), fn ($v) => $v !== '' && $v !== null);

    return url('admin_items') . '?' . http_build_query($params);
}

/**
 * A column heading that sorts on click: ascending, then descending, then back
 * to the default order.
 */
function sort_header(string $key, string $label, string $class = ''): string
{
    global $sort, $dir;

    $active = $sort === $key;
    $link = match (true) {
        !$active => items_link(['sort' => $key, 'dir' => 'asc', 'page' => null]),
        $dir === 'asc' => items_link(['sort' => $key, 'dir' => 'desc', 'page' => null]),
        default => items_link(['sort' => null, 'dir' => null, 'page' => null]),
    };

    return '<th class="sortable' . ($active ? ' sorted ' . $dir : '') . ($class !== '' ? ' ' . $class : '') . '"'
        . ($active ? ' aria-sort="' . ($dir === 'asc' ? 'ascending' : 'descending') . '"' : '')
        . '><a href="' . e($link) . '">' . e($label) . '<i class="sort-arrow" aria-hidden="true"></i></a></th>';
}

// What is filtering the list, as removable chips under the bar: the filters and
// sort are remembered, so what is in effect should always be in view.
$chips = [];
if ($search !== '') {
    $chips[] = ['Search', '“' . $search . '”', items_link(['q' => null, 'page' => null])];
}
if (isset(MEDIA_KINDS[$kind])) {
    $chips[] = ['Format', MEDIA_KINDS[$kind], items_link(['kind' => null, 'page' => null])];
}
if ($artistId > 0) {
    // An era belongs to its artist, so it goes with it.
    $chips[] = ['Artist', array_column($artists, 'name', 'id')[$artistId] ?? 'Unknown', items_link(['artist' => null, 'era' => null, 'page' => null])];
}
if ($eraId !== '') {
    $chips[] = ['Era', $eraId === 'none' ? 'Not in an era' : (array_column($eras, 'name', 'id')[(int) $eraId] ?? 'Unknown'), items_link(['era' => null, 'page' => null])];
}
if (isset($stateLabels[$state])) {
    $chips[] = ['Show', $stateLabels[$state], items_link(['state' => null, 'page' => null])];
}
if ($sort !== '') {
    $chips[] = ['Sorted by', ucfirst($sort === 'kind' ? 'format' : $sort) . ($dir === 'desc' ? ' ↓' : ' ↑'), items_link(['sort' => null, 'dir' => null, 'page' => null])];
}

$sourceLabels = ['collection' => 'the collection', 'wantlist' => 'the wantlist', 'searching' => 'the hunting list'];

$pageTitle = $source === 'collection' ? 'Collection' : ucfirst($source);
$pageIntro = "$total record" . ($total === 1 ? '' : 's') . ' in ' . $sourceLabels[$source] . '.';
$pageScript = 'js/admin-filters.js';
$pageActions = $source === 'searching'
    ? '<a class="btn gold" href="' . e(url('admin_item?new=searching')) . '">Add something you\'re hunting</a>'
    : '';

require __DIR__ . '/../includes/admin_layout_top.php';
?>

<form class="filterbar" id="filters" method="get" action="<?= e(url('admin_items')) ?>">
  <input type="hidden" name="source" value="<?= e($source) ?>">
  <input type="hidden" name="f" value="1">
  <?php if ($sort !== ''): ?>
    <input type="hidden" name="sort" value="<?= e($sort) ?>">
    <input type="hidden" name="dir" value="<?= e($dir) ?>">
  <?php endif; ?>

  <div class="field search<?= $search !== '' ? ' is-active' : '' ?>">
    <label for="q">Search</label>
    <input type="search" id="q" name="q" value="<?= e($search) ?>" autocomplete="off" enterkeyhint="search"
           placeholder="Title, artist, barcode, notes…">
  </div>

  <div class="field<?= isset(MEDIA_KINDS[$kind]) ? ' is-active' : '' ?>">
    <label for="kind">Format</label>
    <select id="kind" name="kind">
      <option value="">Any</option>
      <?php foreach (MEDIA_KINDS as $value => $label): ?>
        <option value="<?= e($value) ?>"<?= $kind === $value ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="field<?= $artistId > 0 ? ' is-active' : '' ?>">
    <label for="artist">Artist page</label>
    <select id="artist" name="artist">
      <option value="">Any</option>
      <?php foreach ($artists as $artist): ?>
        <option value="<?= (int) $artist['id'] ?>"<?= $artistId === (int) $artist['id'] ? ' selected' : '' ?>><?= e($artist['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="field<?= $eraId !== '' ? ' is-active' : '' ?>">
    <label for="era">Era</label>
    <select id="era" name="era">
      <option value="">Any</option>
      <option value="none"<?= $eraId === 'none' ? ' selected' : '' ?>>Not in an era</option>
      <?php
      // With no artist picked the eras are grouped under theirs; with one, they
      // are that artist's alone and need no heading.
      $byArtist = [];
      foreach ($eras as $era) {
          $byArtist[$era['artist']][] = $era;
      }
      foreach ($byArtist as $artistName => $group):
      ?>
        <?php if ($artistId <= 0): ?><optgroup label="<?= e($artistName) ?>"><?php endif; ?>
        <?php foreach ($group as $era): ?>
          <option value="<?= (int) $era['id'] ?>"<?= $eraId === (string) $era['id'] ? ' selected' : '' ?>><?= e($era['name']) ?></option>
        <?php endforeach; ?>
        <?php if ($artistId <= 0): ?></optgroup><?php endif; ?>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="field<?= isset($stateLabels[$state]) ? ' is-active' : '' ?>">
    <label for="state">Show</label>
    <select id="state" name="state">
      <option value="">On the shelf</option>
      <?php foreach (LIST_STATES as $group => $choices): ?>
        <optgroup label="<?= e($group) ?>">
          <?php foreach ($choices as $value => $label): ?>
            <option value="<?= e($value) ?>"<?= $state === $value ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </optgroup>
      <?php endforeach; ?>
    </select>
  </div>

  <noscript><div class="field"><button type="submit" class="ghost">Filter</button></div></noscript>
</form>

<?php if ($chips): ?>
  <div class="filter-chips" aria-label="What is filtering this list">
    <?php foreach ($chips as [$name, $value, $removeUrl]): ?>
      <a class="chip" href="<?= e($removeUrl) ?>" title="Remove this filter">
        <span class="k"><?= e($name) ?></span> <?= e($value) ?> <span class="x" aria-hidden="true">×</span>
      </a>
    <?php endforeach; ?>
    <a class="clear-all" href="<?= e(url('admin_items?source=' . $source . '&reset=1')) ?>" title="Filters and sorting are remembered until you clear them">Clear all</a>
  </div>
<?php endif; ?>

<div class="card">
  <?php if (!$rows): ?>
    <?php if ($chips): ?>
      <p class="empty">Nothing matches these filters. <a href="<?= e(url('admin_items?source=' . $source . '&reset=1')) ?>">Clear them</a></p>
    <?php else: ?>
      <p class="empty">Nothing here. <?= $source === 'collection' ? 'Run a sync from the dashboard to pull the shelf in.' : '' ?></p>
    <?php endif; ?>
  <?php else: ?>
    <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th class="thumb"></th>
          <?= sort_header('album', 'Album') ?>
          <?= sort_header('artist', 'Artist') ?>
          <?= sort_header('barcode', 'Barcode', 'hide-sm') ?>
          <?= sort_header('kind', 'Format') ?>
          <?= sort_header('type', 'Type', 'hide-sm') ?>
          <?= sort_header('year', 'Year') ?>
          <?= sort_header('era', 'Era', 'hide-sm') ?>
          <th class="right"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <?php $editUrl = url('admin_item?id=' . (int) $row['id']); ?>
          <tr>
            <td class="thumb">
              <?php if (item_thumb($row)): ?>
                <img src="<?= e(item_thumb($row)) ?>" alt="" loading="lazy">
              <?php else: ?>
                <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="">
              <?php endif; ?>
            </td>
            <td class="title">
              <b><a href="<?= e($editUrl) ?>"><?= e(item_title($row)) ?></a></b>
              <?php if (!$row['is_visible']): ?><span class="pill">hidden</span><?php endif; ?>
              <?php if ($row['missing_since']): ?><span class="pill warn">gone</span><?php endif; ?>
            </td>
            <td><?= e(item_artist($row)) ?></td>
            <td class="hide-sm mono<?= $row['own_barcode'] ? '' : ' derived' ?>"<?= $row['cell_barcode'] !== '' && !$row['own_barcode'] ? ' title="From Discogs"' : '' ?>><?= $row['cell_barcode'] !== '' ? e($row['cell_barcode']) : '<span class="none">—</span>' ?></td>
            <td>
              <span class="kind <?= e($row['media_kind']) ?>"><?= e(media_kind_label($row['media_kind'])) ?></span>
              <?php if ($row['disc'] && ($row['disc']['c'] !== null || !$row['disc']['pic'])): ?>
                <a class="disc-dot<?= $row['disc']['c'] === null ? ' unknown' : '' ?>" href="<?= e($editUrl) ?>#colourPick"
                   style="<?= $row['disc']['c'] !== null ? 'background:' . e($row['disc']['c']) : '' ?>"
                   title="<?= e($row['disc']['c'] === null ? 'No colour recognised (shows as black)' : (array_filter(disc_colours($row)['hex']) ? 'Picked by hand' : 'Automatic')) ?>"
                   aria-label="Vinyl colour: <?= e($row['disc']['c'] ?? 'not recognised') ?>"></a>
              <?php endif; ?>
            </td>
            <td class="hide-sm<?= $row['own_type'] ? '' : ' derived' ?>"<?= $row['cell_type'] !== '' && !$row['own_type'] ? ' title="From Discogs"' : '' ?>><?= $row['cell_type'] !== '' ? e(ucfirst($row['cell_type'])) : '<span class="none">—</span>' ?></td>
            <td><?= year_cell($row) ?></td>
            <td class="hide-sm"><?= $row['era_name'] !== null ? e($row['era_name']) : '<span class="none">—</span>' ?></td>
            <td class="right"><a class="btn ghost small" href="<?= e($editUrl) ?>">Edit</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <?php if ($pages > 1): ?>
      <div class="form-actions">
        <?php if ($pageNo > 1): ?><a class="btn ghost small" href="<?= e(items_link(['page' => $pageNo - 1])) ?>">← Previous</a><?php endif; ?>
        <span style="font-size:0.85rem;opacity:0.6;">Page <?= $pageNo ?> of <?= $pages ?></span>
        <?php if ($pageNo < $pages): ?><a class="btn ghost small" href="<?= e(items_link(['page' => $pageNo + 1])) ?>">Next →</a><?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin_layout_bottom.php'; ?>
