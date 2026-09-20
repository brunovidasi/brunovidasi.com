<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$source = in_array(query('source'), ['collection', 'wantlist', 'searching'], true) ? query('source') : 'collection';
$search = query('q');
$kind = query('kind');
$artistId = (int) query('artist');
$eraId = query('era');
$state = query('state');
$page = max(1, (int) query('page', '1'));
$perPage = 50;

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
} elseif ($eraId !== '' && (int) $eraId > 0) {
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
    default   => $where[] = 'i.missing_since IS NULL',
};

$whereSql = implode(' AND ', $where);

$total = (int) (function () use ($whereSql, $params) {
    $stmt = db()->prepare("SELECT COUNT(*) FROM items i LEFT JOIN releases r ON r.discogs_id = i.release_id WHERE $whereSql");
    $stmt->execute($params);
    return $stmt->fetchColumn();
})();

$stmt = db()->prepare(ITEM_SELECT . "
    WHERE $whereSql
    ORDER BY r.primary_artist COLLATE NOCASE, r.year, r.title COLLATE NOCASE
    LIMIT $perPage OFFSET " . (($page - 1) * $perPage));
$stmt->execute($params);
$rows = $stmt->fetchAll();

$artists = all_artists();
$eras = $artistId > 0 ? eras_for_artist($artistId) : [];
$pages = max(1, (int) ceil($total / $perPage));

/** Keeps the current filters when only one of them changes. */
function items_link(array $overrides): string
{
    $params = array_merge(array_filter([
        'source' => query('source'),
        'q'      => query('q'),
        'kind'   => query('kind'),
        'artist' => query('artist'),
        'era'    => query('era'),
        'state'  => query('state'),
    ], fn ($v) => $v !== ''), $overrides);

    return url('admin_items') . ($params ? '?' . http_build_query(array_filter($params, fn ($v) => $v !== '' && $v !== null)) : '');
}

$sourceLabels = ['collection' => 'the collection', 'wantlist' => 'the wantlist', 'searching' => 'the hunting list'];

$pageTitle = $source === 'collection' ? 'Collection' : ucfirst($source);
$pageIntro = "$total record" . ($total === 1 ? '' : 's') . ' in ' . $sourceLabels[$source] . '.';
$pageActions = $source === 'searching'
    ? '<a class="btn gold" href="' . e(url('admin_item?new=searching')) . '">Add something you\'re hunting</a>'
    : '';

require __DIR__ . '/../includes/admin_layout_top.php';
?>

<form class="filters" method="get" action="<?= e(url('admin_items')) ?>">
  <input type="hidden" name="source" value="<?= e($source) ?>">

  <div class="field grow">
    <label for="q">Search</label>
    <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Title, artist, barcode or a word from your notes">
  </div>

  <div class="field">
    <label for="kind">Format</label>
    <select id="kind" name="kind">
      <option value="">Any</option>
      <?php foreach (MEDIA_KINDS as $value => $label): ?>
        <option value="<?= e($value) ?>"<?= $kind === $value ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="field">
    <label for="artist">Artist page</label>
    <select id="artist" name="artist">
      <option value="">Any</option>
      <?php foreach ($artists as $artist): ?>
        <option value="<?= (int) $artist['id'] ?>"<?= $artistId === (int) $artist['id'] ? ' selected' : '' ?>><?= e($artist['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <?php if ($eras): ?>
    <div class="field">
      <label for="era">Era</label>
      <select id="era" name="era">
        <option value="">Any</option>
        <option value="none"<?= $eraId === 'none' ? ' selected' : '' ?>>Not in an era</option>
        <?php foreach ($eras as $era): ?>
          <option value="<?= (int) $era['id'] ?>"<?= $eraId === (string) $era['id'] ? ' selected' : '' ?>><?= e($era['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php endif; ?>

  <div class="field">
    <label for="state">Show</label>
    <select id="state" name="state">
      <option value="">On the shelf</option>
      <option value="noera"<?= $state === 'noera' ? ' selected' : '' ?>>Missing an era</option>
      <option value="blank"<?= $state === 'blank' ? ' selected' : '' ?>>Nothing filled in yet</option>
      <option value="hidden"<?= $state === 'hidden' ? ' selected' : '' ?>>Hidden from the site</option>
      <option value="missing"<?= $state === 'missing' ? ' selected' : '' ?>>No longer on Discogs</option>
    </select>
  </div>

  <button type="submit" class="ghost">Filter</button>
  <?php if ($search || $kind || $artistId || $eraId || $state): ?>
    <a class="btn ghost" href="<?= e(url('admin_items?source=' . $source)) ?>">Clear</a>
  <?php endif; ?>
</form>

<div class="card">
  <?php if (!$rows): ?>
    <p class="empty">Nothing here. <?= $source === 'collection' ? 'Run a sync from the dashboard to pull the shelf in.' : '' ?></p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th class="thumb"></th>
          <th>Record</th>
          <th>Format</th>
          <th class="hide-sm">Year</th>
          <th class="hide-sm">Era</th>
          <th class="hide-sm">Mine</th>
          <th class="right"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <?php
          $filled = array_filter(['barcode', 'release_date', 'region', 'media', 'vinyl_size', 'vinyl_color', 'item_type', 'notes'], fn ($f) => trim((string) $row[$f]) !== '');
          $era = $row['era_id'] ? era_by_id((int) $row['era_id']) : null;
          ?>
          <tr>
            <td class="thumb">
              <?php if (item_thumb($row)): ?>
                <img src="<?= e(item_thumb($row)) ?>" alt="" loading="lazy">
              <?php else: ?>
                <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="">
              <?php endif; ?>
            </td>
            <td class="title">
              <b><a href="<?= e(url('admin_item?id=' . (int) $row['id'])) ?>"><?= e(item_title($row)) ?></a></b>
              <small><?= e(item_artist($row)) ?></small>
            </td>
            <td><span class="kind <?= e($row['media_kind']) ?>"><?= e(media_kind_label($row['media_kind'])) ?></span></td>
            <td class="hide-sm"><?= e($row['year'] ?: '—') ?></td>
            <td class="hide-sm"><?= $era ? e($era['name']) : '<span style="opacity:0.4">—</span>' ?></td>
            <td class="hide-sm"><?= $filled ? '<span class="pill good">' . count($filled) . '</span>' : '<span style="opacity:0.35">—</span>' ?></td>
            <td class="right">
              <?php if (!$row['is_visible']): ?><span class="pill">hidden</span><?php endif; ?>
              <?php if ($row['missing_since']): ?><span class="pill warn">gone</span><?php endif; ?>
              <a class="btn ghost small" href="<?= e(url('admin_item?id=' . (int) $row['id'])) ?>">Edit</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($pages > 1): ?>
      <div class="form-actions">
        <?php if ($page > 1): ?><a class="btn ghost small" href="<?= e(items_link(['page' => $page - 1])) ?>">← Previous</a><?php endif; ?>
        <span style="font-size:0.85rem;opacity:0.6;">Page <?= $page ?> of <?= $pages ?></span>
        <?php if ($page < $pages): ?><a class="btn ghost small" href="<?= e(items_link(['page' => $page + 1])) ?>">Next →</a><?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin_layout_bottom.php'; ?>
