<?php

require_once __DIR__ . '/../includes/bootstrap.php';

if (!setting('show_selling', true)) {
    not_found();
}

$count = (int) db()->query("
    SELECT COUNT(*) FROM items
     WHERE source = 'for_sale' AND is_visible = 1 AND sold_at IS NULL
")->fetchColumn();

$page = [
    'title'       => "Selling — Bruno's Collection",
    'description' => 'Records and CDs Bruno is selling.',
    'styles'      => ['floor', 'artist', 'spotlight', 'selling'],
    'heading'     => 'Selling',
    'intro'       => "$count " . plural($count, 'record') . ' up for sale, sold through eBay.',
    'links'       => hero_links(null, true),
    'platter'     => hero_platter('sleeve'),
    'tip'         => false,
    'scripts'     => ['common', 'hero', 'controls', 'dropdown', 'tiles', 'shelf', 'spotlight-core', 'sale-spotlight', 'selling'],
];

require __DIR__ . '/../includes/templates/public_top.php';
?>

<div class="controls">
  <div class="controls-inner">
    <?= hero_search('Search title, artist, notes…', 'Search what\'s for sale') ?>
    <select id="sort" aria-label="Sort">
      <option value="listed-desc">Newest listed</option>
      <option value="price-asc">Price, low to high</option>
      <option value="price-desc">Price, high to low</option>
    </select>
    <div class="seg" id="viewToggle" role="group" aria-label="View">
      <button type="button" data-view="grid">Grid</button>
      <button type="button" data-view="list">List</button>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/templates/public_bottom.php'; ?>
