<?php
/**
 * Renders the "I Want The Item Anyway" tab shared by the add/edit auction forms: a
 * single bid whose amount isn't fixed ahead of time — at fire time it adds a flat
 * value or a percentage on top of whatever the item's live price is then,
 * optionally capped at a max value. See resolve_anyway_bid_input() and the matching
 * fire-time logic in snipe_runner.php.
 *
 * Deliberately its own wrapper rather than .bid-step-row, for the same reason
 * render_scheduled_bid_panel() has its own: the Steps tab's JS walks every
 * .bid-step-row in the form assuming it's part of that ladder, and this single
 * field group isn't one.
 *
 * Each field gets its own .scheduled-row rather than pairing two per row (unlike
 * the Scheduled Bid tab): the max value field's hint is long enough that pairing
 * it with a shorter neighbor, both bottom-aligned in the same flex row, throws the
 * two inputs out of line with each other.
 *
 * $anyway: ['seconds_before' => int|'', 'increment_type' => 'value'|'percent'|'',
 * 'increment_amount' => float|'', 'max_bid' => float|'', 'readonly' => bool,
 * 'status' => ?string, 'exists' => bool]. There is at most one of these per
 * auction, so unlike render_bid_step_rows() this renders a single, always-visible
 * set of fields.
 */
function render_anyway_bid_panel(array $anyway, string $currency): void
{
    $incrementType = $anyway['increment_type'] !== '' ? $anyway['increment_type'] : 'value';
    $readonly = !empty($anyway['readonly']);
    ?>
    <div class="scheduled-row">
        <div class="bid-step-field">
            <label for="anyway_seconds_before">Seconds before end</label>
            <input type="number" id="anyway_seconds_before" name="anyway_seconds_before" min="1" max="60"
                   placeholder="<?= ANYWAY_DEFAULT_SECONDS_BEFORE ?> (default)"
                   value="<?= htmlspecialchars((string) $anyway['seconds_before']) ?>"
                   <?= $readonly ? 'readonly' : '' ?>>
            <div class="hint">
                Defaults to <?= ANYWAY_DEFAULT_SECONDS_BEFORE ?>s before the end if left blank. You can set it lower,
                but under <?= ANYWAY_DEFAULT_SECONDS_BEFORE ?>s risks eBay responding too slowly for the bid to
                register in time.
            </div>
            <div class="field-error" data-field-error></div>
        </div>
    </div>

    <div class="scheduled-mode-toggle" role="radiogroup" aria-label="How much more than the current price to bid" data-anyway-increment-toggle data-currency="<?= htmlspecialchars($currency) ?>">
        <label>
            <input type="radio" name="anyway_increment_type" value="value" <?= $incrementType === 'value' ? 'checked' : '' ?> <?= $readonly ? 'disabled' : '' ?>>
            A fixed amount over the price at the time of the bid
        </label>
        <label>
            <input type="radio" name="anyway_increment_type" value="percent" <?= $incrementType === 'percent' ? 'checked' : '' ?> <?= $readonly ? 'disabled' : '' ?>>
            A percentage over the price at the time of the bid
        </label>
    </div>

    <div class="scheduled-row">
        <div class="bid-step-field">
            <label for="anyway_increment_amount">Amount (<span data-anyway-increment-unit><?= $incrementType === 'percent' ? '%' : htmlspecialchars($currency) ?></span>)</label>
            <input type="number" id="anyway_increment_amount" name="anyway_increment_amount" step="0.01" min="0.01"
                   placeholder="e.g. 10"
                   value="<?= htmlspecialchars((string) $anyway['increment_amount']) ?>"
                   <?= $readonly ? 'readonly' : '' ?>>
            <div class="field-error" data-field-error></div>
        </div>
    </div>

    <div class="scheduled-row">
        <div class="bid-step-field">
            <label for="anyway_max_bid">Max value (<?= htmlspecialchars($currency) ?>) &mdash; optional</label>
            <input type="number" id="anyway_max_bid" name="anyway_max_bid" step="0.01" min="0.01"
                   placeholder="Leave blank for no limit"
                   value="<?= htmlspecialchars((string) $anyway['max_bid']) ?>"
                   <?= $readonly ? 'readonly' : '' ?>>
            <div class="hint">Won't bid above this, no matter how high the price has gone. Leave blank if you don't want a limit.</div>
            <div class="field-error" data-field-error></div>
        </div>
        <?php if (!empty($anyway['status'])): ?>
            <span class="status-<?= htmlspecialchars($anyway['status']) ?>"><?= htmlspecialchars($anyway['status']) ?></span>
        <?php endif; ?>
    </div>
    <?php if (!$readonly): ?>
        <div class="hint">Leave the amount blank to not use this option<?= !empty($anyway['exists']) ? ', or clear it to remove the one below' : '' ?>.</div>
    <?php endif; ?>
    <?php
}
