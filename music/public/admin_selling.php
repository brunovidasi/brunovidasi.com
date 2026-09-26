<?php

require_once __DIR__ . '/../includes/admin.php';

require_login();

$rows = items_query("WHERE i.source = 'for_sale' ORDER BY i.created_at DESC");

$forSale = count(array_filter($rows, fn ($r) => $r['is_visible'] && $r['sold_at'] === null));
$sold = count(array_filter($rows, fn ($r) => $r['sold_at'] !== null));
$drafts = count(array_filter($rows, fn ($r) => !$r['is_visible'] && $r['sold_at'] === null));

admin_header(
    'Selling',
    "$forSale for sale, $sold sold, $drafts still a draft.",
    '<a class="btn gold" href="' . e(url('admin_selling_item')) . '">Add an item to sell</a>'
);
?>

<div class="card">
  <?php if (!$rows): ?>
    <p class="empty">Nothing listed yet. <a href="<?= e(url('admin_selling_item')) ?>">Add an item to sell</a> — paste a Discogs link and the details come with it.</p>
  <?php else: ?>
    <div class="filterbar single">
      <div class="field search">
        <label for="sellingSearch">Search</label>
        <input type="search" id="sellingSearch" data-table-search="sellingTable" autocomplete="off" placeholder="Title, artist…">
      </div>
    </div>
    <table class="table" id="sellingTable" data-sortable>
      <thead>
        <tr>
          <th class="thumb"></th>
          <th data-sort>Album</th>
          <th data-sort>Artist</th>
          <th data-sort>Format</th>
          <th data-sort>Condition</th>
          <th data-sort>Price</th>
          <th data-sort>Status</th>
          <th class="right"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <?php
          $editUrl = url('admin_selling_item?id=' . (int) $row['id']);
          [$status, $statusClass] = match (true) {
              $row['sold_at'] !== null => ['Sold', ''],
              (bool) $row['is_visible'] => ['For sale', 'good'],
              default                  => ['Draft', 'warn'],
          };
          ?>
          <tr>
            <?= thumb_cell($row) ?>
            <td class="title"><b><a href="<?= e($editUrl) ?>"><?= e(item_title($row)) ?></a></b></td>
            <td><?= e(item_artist($row)) ?></td>
            <td><?= kind_badge($row['media_kind']) ?></td>
            <td><?= e(sale_condition_label($row) ?? '—') ?></td>
            <td data-sort-value="<?= (float) $row['sale_price'] ?>"><?= $row['sale_price'] !== null ? e(sale_price_label((float) $row['sale_price'], $row['sale_currency'])) : none_mark() ?></td>
            <td><span class="pill <?= $statusClass ?>"><?= $status ?></span></td>
            <td class="right"><?= edit_button($editUrl) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php admin_footer('admin-table'); ?>
