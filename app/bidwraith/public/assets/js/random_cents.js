/**
 * The "random cents" buttons that sit next to every max-bid field.
 *
 * A max bid ending in a common round number (.00, .50) is easy for another
 * sniper's bid to land on or just beat. Bumping it to a random ending over
 * 51 cents keeps the whole-dollar amount intact but makes the final cents
 * unpredictable, which is enough to win most eBay proxy-bidding ties.
 */
(function (Bidwraith) {
    'use strict';

    /**
     * Every step must stay equal or higher than the step before it — the same
     * rule the server enforces — so `minValue`/`maxValue` let the caller pass
     * the neighboring steps' amounts (previous and next) and this keeps the
     * result within that window. That way clicking the button can never itself
     * put a step's order out of line and trigger a validation error: if the
     * >51-cents band doesn't fit inside the window, it falls back to whatever
     * room the neighbors actually leave.
     */
    function randomCentsValue(current, minValue, maxValue) {
        var base = parseFloat(current);
        if (isNaN(base) || base < 0) {
            base = 0;
        }
        var dollars = Math.floor(base);

        var lowCents = dollars * 100 + 51;
        var highCents = dollars * 100 + 99;

        var minTotal = (typeof minValue === 'number' && !isNaN(minValue)) ? Math.round(minValue * 100) : null;
        var maxTotal = (typeof maxValue === 'number' && !isNaN(maxValue)) ? Math.round(maxValue * 100) : null;

        if (minTotal !== null && lowCents < minTotal) {
            lowCents = minTotal;
        }
        if (maxTotal !== null && highCents > maxTotal) {
            highCents = maxTotal;
        }

        if (lowCents > highCents) {
            lowCents = minTotal !== null ? minTotal : dollars * 100;
            highCents = maxTotal !== null ? maxTotal : dollars * 100 + 99;
            if (lowCents > highCents) {
                highCents = lowCents;
            }
        }

        var totalCents = lowCents + Math.floor(Math.random() * (highCents - lowCents + 1));
        return totalCents / 100;
    }

    /** Returns the value written, or null if the field couldn't be written to. */
    function applyRandomCents(input, minValue, maxValue) {
        if (!input || input.readOnly || input.disabled) {
            return null;
        }
        var value = randomCentsValue(input.value, minValue, maxValue);
        input.value = value.toFixed(2);
        // Setting .value directly doesn't fire a native input event, so anything
        // listening for one (the max-bid warning/estimate box, the Save-button
        // validity check) wouldn't otherwise notice this changed.
        input.dispatchEvent(new Event('input', { bubbles: true }));
        return value;
    }

    /** Wires a button to randomise the cents of one field, found by id within its form. */
    function wireSingleFieldButton(selector, fieldId) {
        document.querySelectorAll(selector).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var form = btn.closest('form');
                var input = form ? form.querySelector('#' + fieldId) : document.getElementById(fieldId);
                applyRandomCents(input);
            });
        });
    }

    wireSingleFieldButton('[data-random-cents-target]', 'target_max_bid');
    wireSingleFieldButton('[data-random-cents-scheduled]', 'scheduled_max_bid');

    // One Steps row: kept inside the window left by the rows either side of it.
    document.querySelectorAll('.bid-step-row [data-random-cents]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var field = btn.closest('.bid-step-field');
            var input = field ? field.querySelector('input[type=number]') : null;
            var form = btn.closest('form');
            var row = btn.closest('.bid-step-row');
            var minValue, maxValue;

            if (form && row) {
                var rows = Bidwraith.visibleBidStepRows(form);
                var rowIndex = rows.indexOf(row);
                if (rowIndex > 0) {
                    var prevMaxBid = parseFloat(rows[rowIndex - 1].querySelector('input[name="step_max_bid[]"]').value);
                    if (!isNaN(prevMaxBid)) {
                        minValue = prevMaxBid;
                    }
                }
                if (rowIndex >= 0 && rowIndex < rows.length - 1) {
                    var nextMaxBid = parseFloat(rows[rowIndex + 1].querySelector('input[name="step_max_bid[]"]').value);
                    if (!isNaN(nextMaxBid)) {
                        maxValue = nextMaxBid;
                    }
                }
            }

            applyRandomCents(input, minValue, maxValue);
            if (form) {
                Bidwraith.revalidateStepOrder(form);
            }
        });
    });

    // Every Steps row at once: each row is floored at the value just given to the
    // row above it, so the ladder stays in ascending order.
    document.querySelectorAll('[data-random-cents-all]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = btn.closest('form');
            if (!form) {
                return;
            }
            var minValue;
            Bidwraith.visibleBidStepRows(form).forEach(function (row) {
                var input = row.querySelector('input[name="step_max_bid[]"]');
                var value = applyRandomCents(input, minValue);
                if (value !== null) {
                    minValue = value;
                }
            });
            Bidwraith.revalidateStepOrder(form);
        });
    });
})(window.Bidwraith);
