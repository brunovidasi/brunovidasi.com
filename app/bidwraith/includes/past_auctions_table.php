<?php
/**
 * Table of finished auctions, shared by the user's auction list and the admin dashboard.
 * Expects $pastRows (watched_auctions rows, each with a 'steps' key, already sorted by
 * the caller's SQL query to match $sortKey/$sortDir), $currency, and $showOwner (true to
 * add an owner column, for the admin view). $anchor, when given (an element id, no
 * leading '#'), is passed through to the sort links so reloading lands back on this
 * table instead of the top of the page.
 *
 * A row with an 'owner_currency' key (the admin view, where rows span multiple users
 * with potentially different currencies) shows prices in that row's own currency;
 * otherwise every row falls back to $currency, the single currency of the page's own
 * user.
 */
$showOwner = $showOwner ?? false;
// Each title links through to a detail page: the admin views to their own, everyone
// else to the user-facing one, which only ever shows the viewer's own auctions.
$detailPage = $detailPage ?? 'auction.php';
$sortKey = $sortKey ?? 'end';
$sortDir = $sortDir ?? 'desc';
$sortParam = $sortParam ?? 'psort';
$dirParam = $dirParam ?? 'pdir';
$sortResetParams = $sortResetParams ?? [];
$anchor = $anchor ?? '';
?>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <?= sortable_th('Item', 'item', $sortKey, $sortDir, $sortParam, $dirParam, $sortResetParams, '', $anchor) ?>
                <?php if ($showOwner): ?><?= sortable_th('Owner', 'owner', $sortKey, $sortDir, $sortParam, $dirParam, $sortResetParams, '', $anchor) ?><?php endif; ?>
                <?= sortable_th('Ended', 'end', $sortKey, $sortDir, $sortParam, $dirParam, $sortResetParams, '', $anchor) ?>
                <?= sortable_th('Result', 'result', $sortKey, $sortDir, $sortParam, $dirParam, $sortResetParams, '', $anchor) ?>
                <th>Bids</th>
                <?= sortable_th('Final price', 'price', $sortKey, $sortDir, $sortParam, $dirParam, $sortResetParams, 'num', $anchor) ?>
                <?= sortable_th('Top bid', 'topbid', $sortKey, $sortDir, $sortParam, $dirParam, $sortResetParams, 'num', $anchor) ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pastRows as $row):
                $steps = $row['steps'] ?? [];
                $topBid = bid_steps_top_amount($steps);
                $outcome = bid_outcome_summary($steps, $row['status']);
                $settled = in_array($row['status'], ['won', 'lost'], true);
                $rowCurrency = array_key_exists('owner_currency', $row) ? user_currency(['currency' => $row['owner_currency']]) : $currency;
            ?>
                <tr>
                    <td class="cell-title" title="<?= htmlspecialchars($row['title'] ?? '') ?>">
                        <?php $title = htmlspecialchars($row['title'] ?? '(unknown title)'); ?>
                        <a href="<?= htmlspecialchars($detailPage) ?>?id=<?= (int) $row['id'] ?>"><?= $title ?></a>
                        <span class="muted"><?= htmlspecialchars($row['item_id']) ?></span>
                    </td>
                    <?php if ($showOwner): ?>
                        <td class="cell-email" title="<?= htmlspecialchars($row['owner_email']) ?>">
                            <a href="admin_user.php?id=<?= (int) $row['user_id'] ?>"><?= htmlspecialchars($row['owner_email']) ?></a>
                        </td>
                    <?php endif; ?>
                    <td class="nowrap muted"><?= $row['end_time'] !== null ? local_time((int) strtotime($row['end_time']), $row['end_time']) : 'unknown' ?></td>
                    <td class="nowrap">
                        <?php if ($settled): ?>
                            <span class="status-<?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span>
                        <?php else: ?>
                            <span class="outcome-unknown" title="Ended while still marked “<?= htmlspecialchars($row['status']) ?>” — the app doesn't check the final result on eBay.">unknown</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="outcome-<?= htmlspecialchars($outcome['tone']) ?>"><?= htmlspecialchars($outcome['label']) ?></span>
                        <?php if ($outcome['detail'] !== ''): ?>
                            <span class="outcome-detail"><?= htmlspecialchars($outcome['detail']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="num"><?= $row['current_price'] !== null ? htmlspecialchars($rowCurrency . ' ' . number_format($row['current_price'], 2)) : '—' ?></td>
                    <td class="num"><?= htmlspecialchars($rowCurrency . ' ' . number_format($topBid, 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
