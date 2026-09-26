<?php

require_once __DIR__ . '/../includes/bootstrap.php';

if (!setting('show_wantlist', true)) {
    not_found();
}

$count = (int) db()->query("
    SELECT COUNT(*) FROM items
     WHERE source IN ('wantlist', 'searching') AND is_visible = 1 AND missing_since IS NULL
")->fetchColumn();

$page = [
    'title'       => "Wantlist — Bruno's Collection",
    'description' => 'The records Bruno is still looking for.',
    'styles'      => ['floor', 'artist', 'spotlight'],
    'heading'     => 'Still Wanted',
    'intro'       => "$count records that aren't on the shelf yet.",
    'links'       => hero_links(null, false, 'Artist pages', true),
    'platter'     => hero_platter('sleeve'),
    'scripts'     => ['common', 'hero', 'controls', 'tiles', 'shelf', 'spotlight-core', 'spotlight', 'wantlist'],
];

require __DIR__ . '/../includes/templates/public_top.php';
?>

<div class="controls">
  <div class="controls-inner">
    <?= hero_search('Search title, artist, barcode…', 'Search the wantlist') ?>
    <label class="mess" id="messWrap">Messiness <input type="range" id="mess" min="0" max="1.4" step="0.05" value="0.6" aria-label="Messiness of the pile"></label>
    <div class="seg" id="viewToggle" role="group" aria-label="View">
      <button type="button" data-view="floor">Floor</button>
      <button type="button" data-view="grid">Grid</button>
      <button type="button" data-view="list">List</button>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/templates/public_bottom.php'; ?>
