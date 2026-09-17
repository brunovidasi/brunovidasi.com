/**
 * The bid form's tab strip (Strategies / Steps / Scheduled / Anyway) and the
 * strategy cards that prefill a Steps ladder or jump to another tab.
 */
(function (Bidwraith) {
    'use strict';

    /** Maximum number of Steps rows the form offers, matching the fixed rows in includes/bid_steps_view.php. */
    var MAX_STEPS = 5;

    Bidwraith.switchBidTab = function (form, tab) {
        form.querySelectorAll('[data-bid-tab]').forEach(function (btn) {
            var active = btn.dataset.bidTab === tab;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        form.querySelectorAll('[data-bid-panel]').forEach(function (panel) {
            panel.hidden = panel.dataset.bidPanel !== tab;
        });
    };

    document.querySelectorAll('[data-bid-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = btn.closest('form');
            if (form) {
                Bidwraith.switchBidTab(form, btn.dataset.bidTab);
            }
        });
    });

    /**
     * Fills in a max bid for each step of a strategy, working backwards from the
     * user's target max bid (which always lands on the last, closest-to-end step)
     * toward the item's current price. With only one step there's nothing to
     * ramp, so it just gets the target. With more, earlier steps are spaced
     * between the current price and the target so each one is equal or higher
     * than the step before it, satisfying the same ordering the server enforces.
     */
    function computeStrategyMaxBids(stepCount, targetMaxBid, currentPrice) {
        var values = [];
        if (stepCount <= 1) {
            values.push(targetMaxBid);
            return values;
        }

        var base = (typeof currentPrice === 'number' && !isNaN(currentPrice) && currentPrice > 0 && currentPrice < targetMaxBid)
            ? currentPrice
            : targetMaxBid;

        for (var i = 0; i < stepCount; i++) {
            var fraction = (i + 1) / stepCount;
            var value = base + (targetMaxBid - base) * fraction;
            values.push(Math.round(value * 100) / 100);
        }
        values[stepCount - 1] = targetMaxBid;
        return values;
    }

    /** Replaces the Steps ladder with the seconds a strategy card carries. */
    function applyStrategy(card) {
        var form = card.closest('form');
        if (!form) {
            return;
        }
        var seconds = JSON.parse(card.dataset.strategy);

        var targetMaxBidInput = form.querySelector('#target_max_bid');
        var targetMaxBid = targetMaxBidInput ? parseFloat(targetMaxBidInput.value) : NaN;
        var stepMaxBids = !isNaN(targetMaxBid) && targetMaxBid > 0
            ? computeStrategyMaxBids(seconds.length, targetMaxBid, Bidwraith.item.currentPrice)
            : [];

        var rows = form.querySelectorAll('.bid-step-row');
        rows.forEach(function (row, i) {
            var idInput = row.querySelector('input[name="step_id[]"]');
            var secondsInput = row.querySelector('input[name="step_seconds[]"]');
            var maxBidInput = row.querySelector('input[name="step_max_bid[]"]');
            if (idInput) {
                idInput.value = '';
            }
            if (i < seconds.length) {
                if (secondsInput) {
                    secondsInput.value = seconds[i];
                }
                if (maxBidInput) {
                    maxBidInput.value = stepMaxBids.length ? stepMaxBids[i] : '';
                }
                row.hidden = false;
            } else {
                if (secondsInput) {
                    secondsInput.value = '';
                }
                if (maxBidInput) {
                    maxBidInput.value = '';
                }
                row.hidden = true;
            }
        });

        var addBtn = form.querySelector('[data-add-step]');
        if (addBtn) {
            addBtn.hidden = seconds.length >= MAX_STEPS;
        }

        Bidwraith.showBidStepError(form, null);
        Bidwraith.clearAllFieldErrors(form);
        Bidwraith.switchBidTab(form, 'custom');

        var firstMaxBid = form.querySelector('.bid-step-row:not([hidden]) input[name="step_max_bid[]"]');
        if (firstMaxBid) {
            firstMaxBid.focus();
        }

        Bidwraith.revalidateSave(form);
    }

    /** Runs `action` straight away, or behind the card's confirm dialog if it sets one. */
    function withStrategyConfirm(card, action) {
        var confirmMessage = card.dataset.strategyConfirm;
        if (!confirmMessage) {
            action();
            return;
        }
        Bidwraith.showConfirmModal(confirmMessage).then(function (confirmed) {
            if (confirmed) {
                action();
            }
        });
    }

    document.querySelectorAll('[data-strategy]').forEach(function (card) {
        card.addEventListener('click', function () {
            withStrategyConfirm(card, function () {
                applyStrategy(card);
            });
        });
    });

    // Some strategy cards (e.g. "Scheduled Bid", "I Want The Item Anyway") don't
    // prefill seconds at all — their inputs live on their own tab, so clicking the
    // card just switches to it (behind the same confirm dialog as a data-strategy
    // card, if one is set).
    document.querySelectorAll('[data-strategy-goto]').forEach(function (card) {
        card.addEventListener('click', function () {
            var form = card.closest('form');
            if (!form) {
                return;
            }
            withStrategyConfirm(card, function () {
                Bidwraith.switchBidTab(form, card.dataset.strategyGoto);
            });
        });
    });
})(window.Bidwraith);
