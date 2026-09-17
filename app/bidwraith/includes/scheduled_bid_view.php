<?php
/**
 * Renders the "Scheduled Bid" tab shared by the add/edit auction forms: a single
 * bid timed either by hours/minutes before the auction ends, or at an exact date,
 * as opposed to the Steps tab's last-minute (1-60s) snipe ladder.
 *
 * Deliberately uses its own .scheduled-row wrapper rather than .bid-step-row: the
 * Steps tab's JS (ordering revalidation, per-row "random cents" min/max, row
 * indexing) walks every .bid-step-row in the form assuming it's part of that
 * ladder, and this single field group isn't one.
 *
 * $scheduled: ['hours' => int|'', 'minutes' => int|'', 'max_bid' => float|'',
 * 'date_local' => string, 'mode' => 'offset'|'date'|'', 'readonly' => bool,
 * 'status' => ?string, 'exists' => bool]. There is at most one scheduled bid
 * per auction, so unlike
 * render_bid_step_rows() this renders a single, always-visible set of fields.
 */
function render_scheduled_bid_panel(array $scheduled, string $currency): void
{
    $mode = $scheduled['mode'] !== '' ? $scheduled['mode'] : 'offset';
    $readonly = !empty($scheduled['readonly']);
    ?>
    <div class="scheduled-mode-toggle" role="radiogroup" aria-label="How the scheduled bid is timed" data-scheduled-mode-toggle>
        <label>
            <input type="radio" name="scheduled_mode" value="offset" <?= $mode === 'offset' ? 'checked' : '' ?> <?= $readonly ? 'disabled' : '' ?>>
            Hours/minutes before the end
        </label>
        <label>
            <input type="radio" name="scheduled_mode" value="date" <?= $mode === 'date' ? 'checked' : '' ?> <?= $readonly ? 'disabled' : '' ?>>
            An exact date &amp; time
        </label>
    </div>

    <div class="scheduled-row" data-scheduled-fields="offset"<?= $mode !== 'offset' ? ' hidden' : '' ?>>
        <div class="bid-step-field">
            <label for="scheduled_hours">Hours before end</label>
            <input type="number" id="scheduled_hours" name="scheduled_hours" min="0" max="720" step="1"
                   placeholder="e.g. 2"
                   value="<?= htmlspecialchars((string) $scheduled['hours']) ?>"
                   <?= $readonly ? 'readonly' : '' ?>>
            <div class="field-error" data-field-error></div>
        </div>
        <div class="bid-step-field">
            <label for="scheduled_minutes">Minutes before end</label>
            <input type="number" id="scheduled_minutes" name="scheduled_minutes" min="0" max="59" step="1"
                   placeholder="e.g. 30"
                   value="<?= htmlspecialchars((string) $scheduled['minutes']) ?>"
                   <?= $readonly ? 'readonly' : '' ?>>
            <div class="field-error" data-field-error></div>
        </div>
    </div>

    <div class="scheduled-row" data-scheduled-fields="date"<?= $mode !== 'date' ? ' hidden' : '' ?>>
        <div class="bid-step-field">
            <label for="scheduled_date">Date &amp; time to fire</label>
            <input type="datetime-local" id="scheduled_date" name="scheduled_date"
                   value="<?= htmlspecialchars((string) ($scheduled['date_local'] ?? '')) ?>"
                   <?= $readonly ? 'readonly disabled' : '' ?>>
            <input type="hidden" id="scheduled_date_utc" name="scheduled_date_utc">
            <div class="hint">In your own local time zone — converted automatically.</div>
            <div class="field-error" data-field-error></div>
        </div>
    </div>

    <div class="scheduled-row">
        <div class="bid-step-field">
            <div class="bid-step-field-header">
                <label for="scheduled_max_bid">Max bid (<?= htmlspecialchars($currency) ?>)</label>
                <button type="button" class="link-btn cents-btn" data-random-cents-scheduled<?= $readonly ? ' disabled' : '' ?>>Add random cents</button>
            </div>
            <input type="number" id="scheduled_max_bid" name="scheduled_max_bid" step="0.01" min="0"
                   placeholder="e.g. 55.00"
                   value="<?= htmlspecialchars((string) $scheduled['max_bid']) ?>"
                   <?= $readonly ? 'readonly' : '' ?>>
            <div class="field-error" data-field-error></div>
        </div>
        <?php if (!empty($scheduled['status'])): ?>
            <span class="status-<?= htmlspecialchars($scheduled['status']) ?>"><?= htmlspecialchars($scheduled['status']) ?></span>
        <?php endif; ?>
    </div>
    <?php if (!$readonly): ?>
        <div class="hint">Leave every field blank to not use a scheduled bid<?= !empty($scheduled['exists']) ? ', or clear them to remove the one below' : '' ?>.</div>
    <?php endif; ?>
    <?php
}
