<?php
/**
 * Prev/next pager for one admin table.
 * Expects $pagerPage, $pagerPages and $pagerParam (the page query param) to be set.
 * $pagerAnchor, when set (an element id, no leading '#'), is appended to both links so
 * the reloaded page scrolls straight back to that table instead of landing at the top.
 */
if ($pagerPages < 2) {
    return;
}
$pagerFragment = !empty($pagerAnchor) ? '#' . $pagerAnchor : '';
?>
<div class="pager">
    <?php if ($pagerPage > 1): ?>
        <a href="<?= htmlspecialchars(url_with([$pagerParam => $pagerPage - 1]) . $pagerFragment) ?>">&larr; Previous</a>
    <?php else: ?>
        <span class="pager-disabled">&larr; Previous</span>
    <?php endif; ?>
    <span>Page <?= $pagerPage ?> of <?= $pagerPages ?></span>
    <?php if ($pagerPage < $pagerPages): ?>
        <a href="<?= htmlspecialchars(url_with([$pagerParam => $pagerPage + 1]) . $pagerFragment) ?>">Next &rarr;</a>
    <?php else: ?>
        <span class="pager-disabled">Next &rarr;</span>
    <?php endif; ?>
</div>
<?php unset($pagerAnchor); ?>
