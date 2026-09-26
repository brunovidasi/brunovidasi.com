<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$title = setting('site_title', DEFAULT_SITE_TITLE);
$intro = setting('site_intro', DEFAULT_SITE_INTRO);

$page = [
    'title'   => "$title — Bruno's Vinyl",
    'styles'  => ['floor', 'crate', 'morph', 'spotlight'],
    'lead'    => hero_eyebrow(),
    'heading' => $title,
    'intro'   => e($intro),
    'stats'   => hero_stats(hero_figures()),
    'links'   => hero_links(null, true, 'Artist pages', true),
    'platter' => hero_platter(),
    'loading' => 'Loading the collection…',
    'scripts' => ['common', 'hero', 'controls', 'dropdown', 'tiles', 'shelf', 'spotlight-core', 'spotlight', 'crate', 'morph', 'script'],
];

require __DIR__ . '/../includes/templates/public_top.php';
?>

<div class="controls">
  <div class="controls-inner">
    <?= hero_search('Search title, artist, barcode…', 'Search collection') ?>
    <select id="sort" aria-label="Sort collection">
      <option value="date-desc" data-short="Newest first">Release date, newest first</option>
      <option value="date-asc" data-short="Oldest first">Release date, oldest first</option>
      <option value="artist" data-short="Artist A–Z">Artist, A–Z</option>
      <option value="added" data-short="Recently added">Recently added</option>
      <option value="custom" disabled hidden></option>
    </select>
    <div class="organise" id="organiseWrap" hidden><span>Organise by</span> <select id="organiseBy" aria-label="Organise the crate by"></select></div>
    <div class="floor-tools">
      <label class="mess" id="messWrap">Messiness <input type="range" id="mess" min="0" max="1.4" step="0.05" value="0.7" aria-label="Messiness of the pile"></label>
      <button type="button" class="crate-btn" id="crateBtn">Put albums in a crate</button>
      <button type="button" class="crate-btn" id="digBtn" hidden>🎲 Dig a random one</button>
      <button type="button" class="ghost" id="messBtn" hidden>Back to the mess</button>
    </div>
    <div class="seg" id="viewToggle" role="group" aria-label="View">
      <button type="button" data-view="floor">Floor</button>
      <button type="button" data-view="grid">Grid</button>
      <button type="button" data-view="list">List</button>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/templates/public_bottom.php'; ?>
