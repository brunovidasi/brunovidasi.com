/**
 * The bid form's tab strip (Strategies / Steps / Scheduled / Anyway) and the
 * strategy cards that prefill a Steps ladder or jump to another tab.
 */
(function (Bidwraith) {
    'use strict';

    /** Maximum number of Steps rows the form offers, matching the fixed rows in includes/bid_steps_view.php. */
    var MAX_STEPS = 5;

    /**
     * Scrolls a tab into view within its strip when it's off the end. Done by hand
     * rather than with scrollIntoView(), which is also entitled to scroll the page
     * itself — jumping the form around under someone who only picked a tab.
     */
    function revealTab(btn) {
        var strip = btn.closest('.bid-tabs');
        if (!strip || strip.scrollWidth <= strip.clientWidth) {
            return;
        }
        var margin = 16;
        var left = btn.offsetLeft;
        var right = left + btn.offsetWidth;
        if (left - margin < strip.scrollLeft) {
            strip.scrollLeft = left - margin;
        } else if (right + margin > strip.scrollLeft + strip.clientWidth) {
            strip.scrollLeft = right + margin - strip.clientWidth;
        }
    }

    Bidwraith.switchBidTab = function (form, tab) {
        form.querySelectorAll('[data-bid-tab]').forEach(function (btn) {
            var active = btn.dataset.bidTab === tab;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
            if (active) {
                revealTab(btn);
            }
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
     * Lets the strip be dragged with a mouse, which touch gets natively from
     * overflow-x but a pointer doesn't. Only the drag itself is handled here:
     * pointer events on a touchscreen stay untouched so the native inertia and
     * rubber-banding keep working.
     *
     * A drag that moved is swallowed on the way back up, so releasing over a tab
     * scrolls the strip instead of also switching to that tab.
     */
    var DRAG_THRESHOLD_PX = 4;

    /** Marks which sides of the strip still have tabs off-screen, for the edge shadows. */
    function updateScrollHints(strip) {
        // A browser can land a pixel short of the exact end, which would leave the
        // shadow up with nothing left to scroll to.
        var max = strip.scrollWidth - strip.clientWidth;
        strip.classList.toggle('can-scroll-left', strip.scrollLeft > 1);
        strip.classList.toggle('can-scroll-right', strip.scrollLeft < max - 1);
    }

    document.querySelectorAll('.bid-tabs').forEach(function (strip) {
        var startX = 0;
        var startScroll = 0;
        var pointerId = null;
        var moved = false;

        strip.addEventListener('pointerdown', function (e) {
            if (e.pointerType === 'touch' || e.button !== 0) {
                return;
            }
            pointerId = e.pointerId;
            startX = e.clientX;
            startScroll = strip.scrollLeft;
            moved = false;
        });

        strip.addEventListener('pointermove', function (e) {
            if (pointerId === null || e.pointerId !== pointerId) {
                return;
            }
            var dx = e.clientX - startX;
            if (!moved) {
                if (Math.abs(dx) < DRAG_THRESHOLD_PX) {
                    return;
                }
                moved = true;
                strip.classList.add('is-dragging');
                // Claiming the pointer keeps the drag alive past the strip's edges,
                // and stops the browser starting a text selection out of it.
                strip.setPointerCapture(pointerId);
            }
            strip.scrollLeft = startScroll - dx;
            e.preventDefault();
        });

        function endDrag(e) {
            if (pointerId === null || (e && e.pointerId !== pointerId)) {
                return;
            }
            if (strip.hasPointerCapture && strip.hasPointerCapture(pointerId)) {
                strip.releasePointerCapture(pointerId);
            }
            pointerId = null;
            strip.classList.remove('is-dragging');
        }

        strip.addEventListener('pointerup', endDrag);
        strip.addEventListener('pointercancel', endDrag);

        strip.addEventListener('click', function (e) {
            if (!moved) {
                return;
            }
            moved = false;
            e.preventDefault();
            e.stopPropagation();
        }, true);

        strip.addEventListener('scroll', function () { updateScrollHints(strip); });
        // The strip can start out scrollable (a narrow window) or become so later
        // (a rotation, or the whole section being revealed with the max bid).
        if (typeof ResizeObserver === 'function') {
            new ResizeObserver(function () { updateScrollHints(strip); }).observe(strip);
        }
        updateScrollHints(strip);
    });

    // A form redisplayed on an open tab (a validation error, say) may have that tab
    // off the end of the strip on a narrow screen.
    document.querySelectorAll('.bid-tab.is-active').forEach(revealTab);

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
        Bidwraith.updateStepRemoveButtons(form);
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
