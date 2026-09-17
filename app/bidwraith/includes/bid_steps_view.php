<?php
/**
 * Renders up to 5 "bid step" rows (seconds-before-end + max bid) for the add/edit
 * auction forms. $steps holds already-known rows (from the DB); the rest render as
 * empty, hidden-by-default inputs revealed one at a time via the "+ Add another bid"
 * button, so the common case (1-2 bids) doesn't show 5 rows at once.
 *
 * Each entry in $steps: ['id' => int|'', 'seconds_before' => int|'', 'max_bid' => float|'',
 * 'readonly' => bool, 'status' => ?string].
 */
function render_bid_step_rows(array $steps, string $currency, bool $allowAdd = true): void
{
    $visibleCount = max(1, count($steps));

    for ($i = 0; $i < 5; $i++) {
        $step = $steps[$i] ?? ['id' => '', 'seconds_before' => '', 'max_bid' => '', 'readonly' => false, 'status' => null];
        $hidden = $i >= $visibleCount;
        $readonly = !empty($step['readonly']);
        ?>
        <div class="bid-step-row"<?= $hidden ? ' hidden' : '' ?>>
            <input type="hidden" name="step_id[]" value="<?= htmlspecialchars((string) $step['id']) ?>">
            <span class="step-index">Step <?= $i + 1 ?></span>
            <div class="bid-step-field">
                <label for="step_seconds_<?= $i ?>">Seconds before end</label>
                <input type="number" id="step_seconds_<?= $i ?>" name="step_seconds[]" min="1" max="60"
                       placeholder="e.g. 5"
                       value="<?= htmlspecialchars((string) $step['seconds_before']) ?>"
                       <?= $readonly ? 'readonly' : '' ?>>
                <div class="field-error" data-field-error></div>
            </div>
            <div class="bid-step-field">
                <div class="bid-step-field-header">
                    <label for="step_max_bid_<?= $i ?>">Max bid (<?= htmlspecialchars($currency) ?>)</label>
                    <button type="button" class="link-btn cents-btn" data-random-cents<?= $readonly ? ' disabled' : '' ?>>Add random cents</button>
                </div>
                <input type="number" id="step_max_bid_<?= $i ?>" name="step_max_bid[]" step="0.01" min="0"
                       placeholder="e.g. 55.00"
                       value="<?= htmlspecialchars((string) $step['max_bid']) ?>"
                       <?= $readonly ? 'readonly' : '' ?>>
                <div class="field-error" data-field-error></div>
            </div>
            <?php if ($step['status']): ?>
                <span class="status-<?= htmlspecialchars($step['status']) ?>"><?= htmlspecialchars($step['status']) ?></span>
            <?php endif; ?>
        </div>
        <?php
    }
    ?>
    <?php if ($allowAdd): ?>
        <button type="button" class="secondary" data-add-step<?= $visibleCount >= 5 ? ' hidden' : '' ?>>+ Add another bid</button>
    <?php endif; ?>
    <?php
}
