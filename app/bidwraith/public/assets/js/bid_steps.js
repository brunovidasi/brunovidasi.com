/**
 * The Steps tab: the ladder of "bid this much, N seconds before the end" rows —
 * adding, removing, keeping them in a valid order, and validating them.
 *
 * Rows are fixed DOM slots (0-4, rendered by includes/bid_steps_view.php) toggled
 * via [hidden] rather than added to and removed from the DOM.
 */
(function (Bidwraith) {
    'use strict';

    /**
     * Reads the filled-in rows as {seconds, maxBid} objects. A row left entirely
     * blank is skipped, same as the server ignores it. Returns an error string
     * instead of the list if any filled row is malformed on its own terms.
     */
    function readSteps(form) {
        var steps = [];
        var rows = Bidwraith.visibleBidStepRows(form);
        for (var i = 0; i < rows.length; i++) {
            var secondsInput = rows[i].querySelector('input[name="step_seconds[]"]');
            var maxBidInput = rows[i].querySelector('input[name="step_max_bid[]"]');
            if (!secondsInput || !maxBidInput) {
                continue;
            }
            var secondsRaw = secondsInput.value.trim();
            var maxBidRaw = maxBidInput.value.trim();
            if (secondsRaw === '' && maxBidRaw === '') {
                continue;
            }

            var seconds = parseFloat(secondsRaw);
            if (secondsRaw === '' || isNaN(seconds) || !Number.isInteger(seconds) || seconds < 1 || seconds > 60) {
                return 'Seconds before end must be between 1 and 60.';
            }
            var maxBid = parseFloat(maxBidRaw);
            if (maxBidRaw === '' || isNaN(maxBid) || maxBid <= 0) {
                return 'Each max bid must be greater than 0.';
            }
            steps.push({ seconds: seconds, maxBid: maxBid });
        }
        return steps;
    }

    /**
     * Client-side backstop for the Steps tab, mirroring (loosely) the server's
     * validation in add_auction.php/edit_auction.php: each filled-in row needs
     * whole seconds 1-60 and a max bid greater than 0, seconds can't repeat,
     * and — same rule as revalidateStepOrder() enforces per-field — a bid closer
     * to the end can't be lower than one further out. Returns an error string, or
     * null if every filled row is valid.
     */
    Bidwraith.getStepsTabError = function (form) {
        var steps = readSteps(form);
        if (typeof steps === 'string') {
            return steps;
        }

        var secondsSeen = [];
        for (var i = 0; i < steps.length; i++) {
            if (secondsSeen.indexOf(steps[i].seconds) !== -1) {
                return 'Each bid must use a different number of seconds before the end.';
            }
            secondsSeen.push(steps[i].seconds);
        }

        var sorted = steps.slice().sort(function (a, b) { return b.seconds - a.seconds; });
        for (var j = 1; j < sorted.length; j++) {
            if (sorted[j].maxBid < sorted[j - 1].maxBid) {
                return 'The bid ' + sorted[j].seconds + 's before the end can\'t be smaller than the bid ' +
                    sorted[j - 1].seconds + 's before the end — bids closer to the end must be equal or higher.';
            }
        }

        return null;
    };

    /**
     * Shows each row's remove button only where pressing it would actually do
     * something. Removing the last row left doesn't take it away — removeBidStepRow()
     * always keeps one on screen — so all a press can do there is blank the fields,
     * which is nothing at all when they're already blank.
     */
    Bidwraith.updateStepRemoveButtons = function (form) {
        var rows = Bidwraith.visibleBidStepRows(form);
        rows.forEach(function (row) {
            var btn = row.querySelector('[data-remove-step]');
            var secondsInput = row.querySelector('input[name="step_seconds[]"]');
            var maxBidInput = row.querySelector('input[name="step_max_bid[]"]');
            if (!btn || !secondsInput || !maxBidInput) {
                return;
            }
            // A step that already fired, or one on an auction that has ended, has
            // its button hidden server-side and can't be edited at all — leave it.
            if (secondsInput.readOnly || secondsInput.disabled) {
                return;
            }
            var empty = secondsInput.value.trim() === '' && maxBidInput.value.trim() === '';
            btn.hidden = rows.length === 1 && empty;
        });
    };

    /** Whether any Steps row has something typed into it. */
    Bidwraith.hasAnyStep = function (form) {
        var rows = Bidwraith.visibleBidStepRows(form);
        for (var i = 0; i < rows.length; i++) {
            var secondsInput = rows[i].querySelector('input[name="step_seconds[]"]');
            var maxBidInput = rows[i].querySelector('input[name="step_max_bid[]"]');
            if ((secondsInput && secondsInput.value.trim() !== '') || (maxBidInput && maxBidInput.value.trim() !== '')) {
                return true;
            }
        }
        return false;
    };

    /**
     * Live feedback as the user tabs between bid steps: each step is expected to fire
     * closer to the auction's end than the one before it, so its seconds-before-end
     * must be lower and its max bid must be equal or higher than the previous step's
     * (the same rule the server enforces on submit, checked here row-by-row against
     * the step directly above it since that's how the form is filled in).
     */
    Bidwraith.revalidateStepOrder = function (form) {
        var rows = Bidwraith.visibleBidStepRows(form);
        rows.forEach(function (row, i) {
            var secondsInput = row.querySelector('input[name="step_seconds[]"]');
            var maxBidInput = row.querySelector('input[name="step_max_bid[]"]');
            if (!secondsInput || !maxBidInput) {
                return;
            }
            if (i === 0) {
                Bidwraith.setFieldError(secondsInput, null);
                Bidwraith.setFieldError(maxBidInput, null);
                return;
            }

            var prevRow = rows[i - 1];
            var prevSeconds = parseInt(prevRow.querySelector('input[name="step_seconds[]"]').value, 10);
            var prevMaxBid = parseFloat(prevRow.querySelector('input[name="step_max_bid[]"]').value);
            var seconds = parseInt(secondsInput.value, 10);
            var maxBid = parseFloat(maxBidInput.value);

            if (!isNaN(seconds) && !isNaN(prevSeconds) && seconds >= prevSeconds) {
                Bidwraith.setFieldError(secondsInput, 'Must be fewer seconds before the end than step ' + i + ' (' + prevSeconds + 's).');
            } else {
                Bidwraith.setFieldError(secondsInput, null);
            }

            if (!isNaN(maxBid) && !isNaN(prevMaxBid) && maxBid < prevMaxBid) {
                Bidwraith.setFieldError(maxBidInput, 'Can\'t be lower than step ' + i + '\'s max bid (' + prevMaxBid + ').');
            } else {
                Bidwraith.setFieldError(maxBidInput, null);
            }
        });
    };

    /** Blanks every row back to the fresh, empty-form state (first row visible, rest hidden). */
    Bidwraith.resetStepRows = function (form) {
        form.querySelectorAll('.bid-step-row').forEach(function (row, i) {
            row.querySelector('input[name="step_id[]"]').value = '';
            row.querySelector('input[name="step_seconds[]"]').value = '';
            row.querySelector('input[name="step_max_bid[]"]').value = '';
            row.hidden = i > 0;
        });
        var addStepBtn = form.querySelector('[data-add-step]');
        if (addStepBtn) {
            addStepBtn.hidden = false;
        }
        Bidwraith.clearAllFieldErrors(form);
        Bidwraith.updateStepRemoveButtons(form);
    };

    /**
     * "Removing" a row means shifting every following row's values up into it and
     * clearing/hiding the freed slot at the end — that keeps the visible rows
     * contiguous from index 0 and their static "Step N" labels correct without
     * re-rendering anything.
     */
    function removeBidStepRow(row) {
        var form = row.closest('form');
        if (!form) {
            return;
        }
        var rows = Array.prototype.slice.call(form.querySelectorAll('.bid-step-row'));
        var index = rows.indexOf(row);
        if (index === -1) {
            return;
        }

        for (var i = index; i < rows.length - 1; i++) {
            var nextRow = rows[i + 1];
            rows[i].querySelector('input[name="step_id[]"]').value = nextRow.querySelector('input[name="step_id[]"]').value;
            rows[i].querySelector('input[name="step_seconds[]"]').value = nextRow.querySelector('input[name="step_seconds[]"]').value;
            rows[i].querySelector('input[name="step_max_bid[]"]').value = nextRow.querySelector('input[name="step_max_bid[]"]').value;
            rows[i].hidden = nextRow.hidden;
        }

        var lastRow = rows[rows.length - 1];
        lastRow.querySelector('input[name="step_id[]"]').value = '';
        lastRow.querySelector('input[name="step_seconds[]"]').value = '';
        lastRow.querySelector('input[name="step_max_bid[]"]').value = '';
        lastRow.hidden = true;

        if (!Bidwraith.visibleBidStepRows(form).length) {
            rows[0].hidden = false;
        }

        var addBtn = form.querySelector('[data-add-step]');
        if (addBtn) {
            addBtn.hidden = !form.querySelector('.bid-step-row[hidden]');
        }

        Bidwraith.clearAllFieldErrors(form);
        Bidwraith.revalidateStepOrder(form);
        Bidwraith.updateStepRemoveButtons(form);
        Bidwraith.revalidateSave(form);
    }

    document.querySelectorAll('.bid-step-row input[name="step_seconds[]"], .bid-step-row input[name="step_max_bid[]"]').forEach(function (input) {
        input.addEventListener('blur', function () {
            var form = input.closest('form');
            if (form) {
                Bidwraith.revalidateStepOrder(form);
            }
        });
    });

    /**
     * The last step (closest to the auction's end) is effectively the ceiling of the
     * whole Steps ladder, so editing it should keep the "Max bid" field at the top —
     * which strategies use to (re)generate the ladder in the first place — in sync
     * rather than silently drifting out of date.
     */
    document.querySelectorAll('.bid-step-row input[name="step_max_bid[]"]').forEach(function (input) {
        input.addEventListener('input', function () {
            var form = input.closest('form');
            var targetMaxBidInput = form ? form.querySelector('#target_max_bid') : null;
            if (!targetMaxBidInput) {
                return;
            }

            var rows = Bidwraith.visibleBidStepRows(form);
            var lastRow = rows[rows.length - 1];
            if (!lastRow || lastRow.querySelector('input[name="step_max_bid[]"]') !== input) {
                return;
            }

            targetMaxBidInput.value = input.value;
            targetMaxBidInput.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });

    document.querySelectorAll('[data-add-step]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = btn.closest('form');
            var nextHidden = form.querySelector('.bid-step-row[hidden]');
            if (nextHidden) {
                nextHidden.hidden = false;
            }
            if (!form.querySelector('.bid-step-row[hidden]')) {
                btn.hidden = true;
            }
            Bidwraith.updateStepRemoveButtons(form);
            Bidwraith.revalidateSave(form);
        });
    });

    document.querySelectorAll('[data-remove-step]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.closest('.bid-step-row');
            if (row) {
                removeBidStepRow(row);
            }
        });
    });

    // One delegated listener per form covers typing in any row and any value set
    // programmatically that dispatches an input event (the random-cents buttons),
    // and keeps working for rows revealed later.
    document.querySelectorAll('form').forEach(function (form) {
        if (!form.querySelector('.bid-step-row')) {
            return;
        }
        form.addEventListener('input', function () {
            Bidwraith.updateStepRemoveButtons(form);
        });
        Bidwraith.updateStepRemoveButtons(form);
    });
})(window.Bidwraith);
