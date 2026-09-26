<?php

require_once __DIR__ . '/../includes/admin.php';

require_login();

const LIST_KEYS = ['q', 'kind', 'artist', 'era', 'state', 'sort', 'dir', 'page'];
const LIST_SORTS = ['album', 'artist', 'barcode', 'kind', 'type', 'year', 'era'];
const LIST_SOURCES = ['collection' => 'the collection', 'wantlist' => 'the wantlist', 'searching' => 'the hunting list'];
const LIST_STATES = [
    'Discs and pictures' => [
        'multi'   => 'More than 1 disc',
        'pic'     => 'With a picture disc',
        'nopic'   => 'Without a picture disc',
        'cover'   => 'With a cover I picked',
        'nocover' => 'No cover picked yet',
        'nocolor' => 'Vinyl with no colour recognised',
    ],
    'Still to do' => [
        'noera' => 'Missing an era',
        'blank' => 'Nothing filled in yet',
    ],
    'Not on the shelf' => [
        'hidden'  => 'Hidden from the site',
        'missing' => 'No longer on Discogs',
    ],
];
const PER_PAGE = 50;

/** What the table shows for a record, and sorts by: the values the site itself would show. */
function list_row(array $row): array
{
    $release = $row['discogs_id'] !== null ? [...$row, 'barcode' => $row['release_barcode']] : null;

    return $row + [
        'cell_barcode' => trim((string) item_field_value($row, $release, 'barcode')),
        'own_barcode'  => trim((string) $row['barcode']) !== '',
        'cell_type'    => trim((string) item_field_value($row, $release, 'item_type')),
        'own_type'     => trim((string) $row['item_type']) !== '',
        'disc'         => $row['media_kind'] === 'vinyl' ? first_vinyl_disc($row) : null,
        'sort_date'    => item_sort_date($row),
        'sort_era'     => $row['era_name'] !== null ? mb_strtolower($row['era_artist'] . sprintf('%06d', $row['era_position'])) : '',
    ];
}

function list_sort_value(array $row, string $by): string
{
    return match ($by) {
        'album'   => mb_strtolower(item_title($row)),
        'artist'  => mb_strtolower(item_artist($row)),
        'barcode' => $row['cell_barcode'],
        'kind'    => media_kind_label($row['media_kind']),
        'type'    => $row['cell_type'],
        'year'    => $row['sort_date'],
        'era'     => $row['sort_era'],
        default   => '',
    };
}

/** Sorted by a column, with artist, release date and title breaking ties (and as the order without one). */
function sort_list_rows(array $rows, string $sort, string $dir): array
{
    $tieBreak = fn (array $row): string => implode("\0", [mb_strtolower(item_artist($row)), $row['sort_date'], mb_strtolower(item_title($row))]);

    usort($rows, function (array $a, array $b) use ($sort, $dir, $tieBreak): int {
        if ($sort !== '') {
            [$x, $y] = [list_sort_value($a, $sort), list_sort_value($b, $sort)];
            // A blank goes last whichever way the column runs.
            if (($x === '') !== ($y === '')) {
                return $x === '' ? 1 : -1;
            }
            if ($x !== $y) {
                // strcmp, not <=>, which would compare two barcodes as numbers.
                return ($dir === 'desc' ? -1 : 1) * strcmp($x, $y);
            }
        }

        return strcmp($tieBreak($a), $tieBreak($b));
    });

    return $rows;
}

$source = isset(LIST_SOURCES[query('source')]) ? query('source') : 'collection';
$filters = remembered_list_state($source, LIST_KEYS, 'admin_items?source=' . $source);

$pageNo = max(1, (int) ($filters['page'] ?? 1));
$search = $filters['q'] ?? '';
$kind = isset(MEDIA_KINDS[$filters['kind'] ?? '']) ? $filters['kind'] : '';
$artistId = (int) ($filters['artist'] ?? 0);
$eraId = $filters['era'] ?? '';
$state = $filters['state'] ?? '';
$stateLabels = array_merge(...array_values(LIST_STATES));
$sort = in_array($filters['sort'] ?? '', LIST_SORTS, true) ? $filters['sort'] : '';
$dir = ($filters['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

$listUrl = fn (array $changes) => list_url('admin_items', ['source' => $source, 'f' => '1'] + $filters, $changes);
$clearUrl = url('admin_items?source=' . $source . '&reset=1');

$artists = all_artists();

// Every era while no artist is picked, only that artist's once one is.
$eras = db()->query('
    SELECT e.id, e.name, e.artist_id, a.name AS artist
      FROM eras e
      JOIN artists a ON a.id = e.artist_id
     ORDER BY a.position, a.name, e.position, e.name
')->fetchAll();
if ($artistId > 0) {
    $eras = array_values(array_filter($eras, fn ($era) => (int) $era['artist_id'] === $artistId));
}

// An era left over from another artist is no longer a choice, so it stops filtering.
if ($eraId !== 'none' && !in_array((int) $eraId, array_map('intval', array_column($eras, 'id')), true)) {
    $eraId = '';
}

$where = ['i.source = ?'];
$params = [$source];

if ($search !== '') {
    $where[] = '(r.title LIKE ? OR r.artists_text LIKE ? OR i.manual_title LIKE ? OR i.manual_artist LIKE ? OR i.barcode LIKE ? OR r.barcode LIKE ? OR i.notes LIKE ?)';
    array_push($params, ...array_fill(0, 7, '%' . $search . '%'));
}
if ($kind !== '') {
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

// "multi" and "nocolor" are worked out in PHP below, by the rules the shelf draws with.
$where[] = match ($state) {
    'missing' => 'i.missing_since IS NOT NULL',
    'hidden'  => 'i.is_visible = 0',
    'blank'   => "(COALESCE(i.notes, '') = '' AND COALESCE(i.barcode, '') = '' AND COALESCE(i.region, '') = '')",
    'noera'   => 'i.era_id IS NULL AND i.artist_id IS NOT NULL',
    'nocolor' => "i.missing_since IS NULL AND i.media_kind = 'vinyl'",
    'pic'     => "i.missing_since IS NULL AND (COALESCE(i.disc_url, '') <> '' OR r.formats_json LIKE '%\"Picture Disc\"%')",
    'nopic'   => "i.missing_since IS NULL AND COALESCE(i.disc_url, '') = '' AND COALESCE(r.formats_json, '') NOT LIKE '%\"Picture Disc\"%'",
    'cover'   => "i.missing_since IS NULL AND COALESCE(i.cover_url, '') <> ''",
    'nocover' => "i.missing_since IS NULL AND COALESCE(i.cover_url, '') = ''",
    default   => 'i.missing_since IS NULL',
};

// Only what the table draws: every matching row is read, since the sorting happens in PHP.
$stmt = db()->prepare('
    SELECT i.*,
           r.discogs_id, r.title, r.artists_text, r.year, r.released, r.released_formatted,
           r.barcode AS release_barcode, r.formats_json, r.thumb, r.cover_image,
           e.name AS era_name, e.position AS era_position, ea.name AS era_artist
      FROM items i
      LEFT JOIN releases r ON r.discogs_id = i.release_id
      LEFT JOIN eras e ON e.id = i.era_id
      LEFT JOIN artists ea ON ea.id = e.artist_id
     WHERE ' . implode(' AND ', $where));
$stmt->execute($params);
$rows = $stmt->fetchAll();

if ($state === 'multi') {
    $rows = array_filter($rows, fn (array $row) => count(item_discs($row, item_formats($row))) > 1);
} elseif ($state === 'nocolor') {
    $rows = array_filter($rows, function (array $row): bool {
        $disc = first_vinyl_disc($row);

        return $disc !== null && $disc['c'] === null && !$disc['pic'];
    });
}

$rows = sort_list_rows(array_map('list_row', array_values($rows)), $sort, $dir);

$total = count($rows);
$pages = max(1, (int) ceil($total / PER_PAGE));
$pageNo = min($pageNo, $pages);
$rows = array_slice($rows, ($pageNo - 1) * PER_PAGE, PER_PAGE);

/** A heading that sorts on click: ascending, then descending, then back to the default order. */
$sortHeader = function (string $key, string $label, string $class = '') use ($sort, $dir, $listUrl): string {
    $active = $sort === $key;
    $link = match (true) {
        !$active       => $listUrl(['sort' => $key, 'dir' => 'asc', 'page' => null]),
        $dir === 'asc' => $listUrl(['sort' => $key, 'dir' => 'desc', 'page' => null]),
        default        => $listUrl(['sort' => null, 'dir' => null, 'page' => null]),
    };

    return '<th class="sortable' . ($active ? ' sorted ' . $dir : '') . ($class !== '' ? ' ' . $class : '') . '"'
        . ($active ? ' aria-sort="' . ($dir === 'asc' ? 'ascending' : 'descending') . '"' : '')
        . '><a href="' . e($link) . '">' . e($label) . '<i class="sort-arrow" aria-hidden="true"></i></a></th>';
};

$chips = [];
if ($search !== '') {
    $chips[] = ['Search', '“' . $search . '”', $listUrl(['q' => null, 'page' => null])];
}
if ($kind !== '') {
    $chips[] = ['Format', MEDIA_KINDS[$kind], $listUrl(['kind' => null, 'page' => null])];
}
if ($artistId > 0) {
    // An era belongs to its artist, so it goes with it.
    $chips[] = ['Artist', array_column($artists, 'name', 'id')[$artistId] ?? 'Unknown', $listUrl(['artist' => null, 'era' => null, 'page' => null])];
}
if ($eraId !== '') {
    $chips[] = ['Era', $eraId === 'none' ? 'Not in an era' : (array_column($eras, 'name', 'id')[(int) $eraId] ?? 'Unknown'), $listUrl(['era' => null, 'page' => null])];
}
if (isset($stateLabels[$state])) {
    $chips[] = ['Show', $stateLabels[$state], $listUrl(['state' => null, 'page' => null])];
}
if ($sort !== '') {
    $chips[] = ['Sorted by', ucfirst($sort === 'kind' ? 'format' : $sort) . ($dir === 'desc' ? ' ↓' : ' ↑'), $listUrl(['sort' => null, 'dir' => null, 'page' => null])];
}

$erasByArtist = [];
foreach ($eras as $era) {
    $erasByArtist[$era['artist']][] = $era;
}

admin_header(
    $source === 'collection' ? 'Collection' : ucfirst($source),
    "$total " . plural($total, 'record') . ' in ' . LIST_SOURCES[$source] . '.',
    $source === 'searching' ? '<a class="btn gold" href="' . e(url('admin_item?new=searching')) . '">Add something you\'re hunting</a>' : ''
);
?>

<form class="filterbar" id="filters" method="get" action="<?= e(url('admin_items')) ?>">
  <input type="hidden" name="source" value="<?= e($source) ?>">
  <input type="hidden" name="f" value="1">
  <?php if ($sort !== ''): ?>
    <input type="hidden" name="sort" value="<?= e($sort) ?>">
    <input type="hidden" name="dir" value="<?= e($dir) ?>">
  <?php endif; ?>

  <?= filter_search($search, 'Title, artist, barcode, notes…') ?>
  <?= filter_select('kind', 'Format', MEDIA_KINDS, $kind) ?>
  <?= filter_select('artist', 'Artist page', array_column($artists, 'name', 'id'), $artistId > 0 ? (string) $artistId : '') ?>

  <div class="field<?= $eraId !== '' ? ' is-active' : '' ?>">
    <label for="era">Era</label>
    <select id="era" name="era">
      <option value="">Any</option>
      <option value="none"<?= $eraId === 'none' ? ' selected' : '' ?>>Not in an era</option>
      <?php foreach ($erasByArtist as $artistName => $group): ?>
        <?php $options = options_html(array_column($group, 'name', 'id'), $eraId); ?>
        <?= $artistId > 0 ? $options : '<optgroup label="' . e($artistName) . '">' . $options . '</optgroup>' ?>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="field<?= isset($stateLabels[$state]) ? ' is-active' : '' ?>">
    <label for="state">Show</label>
    <select id="state" name="state">
      <option value="">On the shelf</option>
      <?php foreach (LIST_STATES as $group => $choices): ?>
        <optgroup label="<?= e($group) ?>"><?= options_html($choices, $state) ?></optgroup>
      <?php endforeach; ?>
    </select>
  </div>

  <noscript><div class="field"><button type="submit" class="ghost">Filter</button></div></noscript>
</form>

<?= filter_chips($chips, $clearUrl, 'Filters and sorting are remembered until you clear them') ?>

<div class="card">
  <?php if (!$rows): ?>
    <?php if ($chips): ?>
      <p class="empty">Nothing matches these filters. <a href="<?= e($clearUrl) ?>">Clear them</a></p>
    <?php else: ?>
      <p class="empty">Nothing here. <?= $source === 'collection' ? 'Run a sync from the dashboard to pull the shelf in.' : '' ?></p>
    <?php endif; ?>
  <?php else: ?>
    <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th class="thumb"></th>
          <?= $sortHeader('album', 'Album') ?>
          <?= $sortHeader('artist', 'Artist') ?>
          <?= $sortHeader('barcode', 'Barcode', 'hide-sm') ?>
          <?= $sortHeader('kind', 'Format') ?>
          <?= $sortHeader('type', 'Type', 'hide-sm') ?>
          <?= $sortHeader('year', 'Year') ?>
          <?= $sortHeader('era', 'Era', 'hide-sm') ?>
          <th class="right"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <?php
          $editUrl = url('admin_item?id=' . (int) $row['id']);
          $disc = $row['disc'];
          ?>
          <tr>
            <?= thumb_cell($row) ?>
            <td class="title">
              <b><a href="<?= e($editUrl) ?>"><?= e(item_title($row)) ?></a></b>
              <?php if (!$row['is_visible']): ?><span class="pill">hidden</span><?php endif; ?>
              <?php if ($row['missing_since']): ?><span class="pill warn">gone</span><?php endif; ?>
            </td>
            <td><?= e(item_artist($row)) ?></td>
            <td class="hide-sm mono<?= $row['own_barcode'] ? '' : ' derived' ?>"<?= $row['cell_barcode'] !== '' && !$row['own_barcode'] ? ' title="From Discogs"' : '' ?>><?= $row['cell_barcode'] !== '' ? e($row['cell_barcode']) : none_mark() ?></td>
            <td>
              <?= kind_badge($row['media_kind']) ?>
              <?php if ($disc && ($disc['c'] !== null || !$disc['pic'])): ?>
                <a class="disc-dot<?= $disc['c'] === null ? ' unknown' : '' ?>" href="<?= e($editUrl) ?>#colourPick"
                   style="<?= $disc['c'] !== null ? 'background:' . e($disc['c']) : '' ?>"
                   title="<?= e($disc['c'] === null ? 'No colour recognised (shows as black)' : (array_filter(disc_colours($row)['hex']) ? 'Picked by hand' : 'Automatic')) ?>"
                   aria-label="Vinyl colour: <?= e($disc['c'] ?? 'not recognised') ?>"></a>
              <?php endif; ?>
            </td>
            <td class="hide-sm<?= $row['own_type'] ? '' : ' derived' ?>"<?= $row['cell_type'] !== '' && !$row['own_type'] ? ' title="From Discogs"' : '' ?>><?= $row['cell_type'] !== '' ? e(ucfirst($row['cell_type'])) : none_mark() ?></td>
            <td><?= year_cell($row) ?></td>
            <td class="hide-sm"><?= $row['era_name'] !== null ? e($row['era_name']) : none_mark() ?></td>
            <td class="right"><?= edit_button($editUrl) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <?= pagination($pageNo, $pages, fn (int $page) => $listUrl(['page' => $page])) ?>
  <?php endif; ?>
</div>

<?php admin_footer('admin-filters'); ?>
