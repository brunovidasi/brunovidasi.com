<?php

/**
 * Everything listed for sale: what's up, what's sold, and the drafts still
 * waiting on a price. One flat table — nothing here is filed into eras or
 * artist pages, so there's no filter bar to speak of, just a sortable list
 * (see admin_wantlist.php, the same shape for the same reason).
 */

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$rows = db()->query(ITEM_SELECT . " WHERE i.source = 'for_sale' ORDER BY i.created_at DESC")->fetchAll();

$forSale = count(array_filter($rows, fn ($r) => $r['is_visible'] && $r['sold_at'] === null));
$sold = count(array_filter($rows, fn ($r) => $r['sold_at'] !== null));
$drafts = count(array_filter($rows, fn ($r) => !$r['is_visible'] && $r['sold_at'] === null));

$pageTitle = 'Selling';
$pageIntro = "$forSale for sale, $sold sold, $drafts still a draft.";
$pageScript = 'js/admin-table.js';
$pageActions = '<a class="btn gold" href="' . e(url('admin_selling_item')) . '">Add an item to sell</a>';

require __DIR__ . '/../includes/admin_layout_top.php';
?>

<div class="card">
  <?php if (!$rows): ?>
    <p class="empty">Nothing listed yet. <a href="<?= e(url('admin_selling_item')) ?>">Add an item to sell</a> — paste a Discogs link and the details come with it.</p>
  <?php else: ?>
    <div class="filterbar" style="grid-template-columns:minmax(0,320px);margin-bottom:1rem;">
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
          $status = $row['sold_at'] !== null
              ? ['label' => 'Sold', 'class' => '']
              : ($row['is_visible'] ? ['label' => 'For sale', 'class' => 'good'] : ['label' => 'Draft', 'class' => 'warn']);
          ?>
          <tr>
            <td class="thumb">
              <?php if (item_thumb($row)): ?>
                <img src="<?= e(item_thumb($row)) ?>" alt="" loading="lazy">
              <?php else: ?>
                <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="">
              <?php endif; ?>
            </td>
            <td class="title"><b><a href="<?= e($editUrl) ?>"><?= e(item_title($row)) ?></a></b></td>
            <td><?= e(item_artist($row)) ?></td>
            <td><span class="kind <?= e($row['media_kind']) ?>"><?= e(media_kind_label($row['media_kind'])) ?></span></td>
            <td><?= e(['new' => 'New', 'used' => 'Used'][$row['sale_condition'] ?? ''] ?? '—') ?></td>
            <td data-sort-value="<?= (float) $row['sale_price'] ?>"><?= $row['sale_price'] !== null ? e(sale_price_label((float) $row['sale_price'], $row['sale_currency'])) : '<span class="none">—</span>' ?></td>
            <td><span class="pill <?= e($status['class']) ?>"><?= e($status['label']) ?></span></td>
            <td class="right"><a class="btn ghost small" href="<?= e($editUrl) ?>">Edit</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin_layout_bottom.php'; ?>
