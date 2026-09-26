<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$artist = published_artist(query('slug'));

if ($artist === null) {
    not_found();
}

$accent = preg_match('/^#[0-9a-f]{3,8}$/i', (string) $artist['accent']) ? $artist['accent'] : '#C99A2E';

$page = [
    'title'       => "{$artist['name']} — Bruno's Collection",
    'description' => $artist['tagline'] ?: "Every record by {$artist['name']} in Bruno's collection, era by era.",
    'styles'      => ['floor', 'artist', 'spotlight'],
    'body'        => ' data-slug="' . e($artist['slug']) . '" style="--accent: ' . e($accent) . '"',
    'heading'     => $artist['name'],
    'intro'       => e($artist['tagline'] ?: 'Every record in the collection, era by era.'),
    'stats'       => hero_stats(hero_figures((int) $artist['id'])),
    'links'       => hero_links((int) $artist['id'], false, 'Other artists', true),
    'platter'     => hero_platter('record', $accent),
    'loading'     => 'Loading the collection…',
    'scripts'     => ['common', 'hero', 'controls', 'tiles', 'shelf', 'spotlight-core', 'spotlight', 'artist'],
];

require __DIR__ . '/../includes/templates/public_top.php';
?>

<div class="controls">
  <div class="controls-inner">
    <?= hero_search('Search title, barcode…', 'Search ' . $artist['name'] . ' records') ?>
    <div class="seg" id="orderToggle" role="group" aria-label="Order of eras">
      <button type="button" data-order="oldest">Oldest first</button>
      <button type="button" data-order="newest">Newest first</button>
    </div>
    <div class="seg" id="viewToggle" role="group" aria-label="View">
      <button type="button" data-view="grid">Grid</button>
      <button type="button" data-view="list">List</button>
    </div>
  </div>
</div>

<nav class="era-nav" id="eraNav" aria-label="Eras" hidden></nav>

<?php require __DIR__ . '/../includes/templates/public_bottom.php'; ?>
