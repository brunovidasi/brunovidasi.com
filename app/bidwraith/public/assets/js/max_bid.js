/**
 * The "Max bid" field at the top of the add-auction form: its validity, the
 * warning when it sits below the item's current price, the landed-cost estimate,
 * and the first reveal of the strategies/tabs section below it.
 */
(function (Bidwraith) {
    'use strict';

    var targetMaxBidInput = document.getElementById('target_max_bid');
    var targetMaxBidError = document.getElementById('targetMaxBidError');
    var maxBidWarning = document.getElementById('maxBidWarning');
    var maxBidEstimate = document.getElementById('maxBidEstimate');
    var bidTabsSection = document.getElementById('bidTabsSection');
    if (!targetMaxBidInput) {
        return;
    }

    // Once the bid tabs have been revealed, they stay open even if the max bid
    // field goes through invalid or too-low in-between states — see the
    // bidTabsSection block in updateMaxBidHelpers() below.
    var bidTabsRevealed = !!(bidTabsSection && !bidTabsSection.hidden);
    // First reveal only happens after the user pauses typing for a bit, so the
    // whole strategies/tabs section doesn't pop in after the very first digit.
    var REVEAL_DELAY_MS = 500;
    var revealTimer = null;

    /**
     * Reads target_max_bid's current value and how it stands against the item's
     * current price. Shared by updateMaxBidHelpers() (live, every keystroke) and
     * the deferred first-reveal check below (re-read after the pause, since the
     * value may have kept changing during the wait).
     */
    function readMaxBidValidity() {
        var raw = targetMaxBidInput.value.trim();
        var value = parseFloat(raw);
        // checkValidity() also catches what parseFloat alone wouldn't (e.g. a
        // negative number, which fails the field's min="0.01") — the field
        // needs to actually pass its own validation, not just parse as a number.
        var basicValid = raw !== '' && !isNaN(value) && value > 0
            && (!targetMaxBidInput.checkValidity || targetMaxBidInput.checkValidity());
        var currentPrice = Bidwraith.item.currentPrice;
        var priceKnown = typeof currentPrice === 'number' && !isNaN(currentPrice);
        var belowCurrentPrice = basicValid && priceKnown && value < currentPrice;
        return { raw: raw, value: value, basicValid: basicValid, priceKnown: priceKnown, belowCurrentPrice: belowCurrentPrice };
    }

    /**
     * Fires ~REVEAL_DELAY_MS after the user stops typing a first, not-yet-revealed
     * max bid. Re-checks validity at fire time rather than trusting whatever it
     * was when the timer was set, since more typing (or a late item lookup) may
     * have changed it in the meantime.
     */
    function revealBidTabsIfStillValid() {
        if (bidTabsRevealed || !bidTabsSection) {
            return;
        }
        var v = readMaxBidValidity();
        if (v.basicValid && !v.belowCurrentPrice) {
            bidTabsSection.hidden = false;
            bidTabsRevealed = true;
        }
    }

    /** Renders the estimated total if this bid wins, broken down by component. */
    function renderEstimate(value) {
        var estimate = Bidwraith.estimateLandedCost(
            value,
            Bidwraith.item.shippingCost,
            Bidwraith.item.country,
            window.bidwraithHomeCountry
        );
        var currencyPrefix = Bidwraith.currencyPrefix();
        maxBidEstimate.innerHTML = '';

        var totalLine = document.createElement('div');
        totalLine.className = 'max-bid-estimate-total';
        totalLine.textContent = currencyPrefix + estimate.total.toFixed(2) + ' — est. total if you win';
        maxBidEstimate.appendChild(totalLine);

        var parts = [
            'max bid ' + value.toFixed(2),
            'shipping ' + estimate.shipping.toFixed(2),
            'buyer protection fee (est.) ' + estimate.buyer_protection_fee.toFixed(2),
        ];
        if (estimate.gst > 0) {
            parts.push('GST on import (est.) ' + estimate.gst.toFixed(2));
        }
        var breakdownLine = document.createElement('div');
        breakdownLine.className = 'hint';
        breakdownLine.textContent = parts.join(' + ');
        maxBidEstimate.appendChild(breakdownLine);

        maxBidEstimate.hidden = false;
    }

    Bidwraith.updateMaxBidHelpers = function () {
        var v = readMaxBidValidity();
        var currentPrice = Bidwraith.item.currentPrice;

        targetMaxBidInput.placeholder = v.priceKnown
            ? 'Max bid should be more than ' + Bidwraith.currencyPrefix() + currentPrice.toFixed(2)
            : 'e.g. 75.00';

        if (bidTabsSection) {
            if (bidTabsRevealed) {
                // Once revealed, an in-between invalid/too-low value (including a
                // momentary one, e.g. a number input clearing itself on a stray
                // "." or the value dipping low mid-keystroke) no longer hides this
                // whole section again — it contains the Steps tab itself, and a
                // Steps row's max bid mirrors live into this field (see bid_steps.js),
                // so re-hiding here would yank the very field the user is typing
                // into out from under them.
                bidTabsSection.hidden = false;
            } else if (v.basicValid && !v.belowCurrentPrice) {
                // Passes full validation (a real, positive number that isn't
                // below the current price) — wait for a pause in typing before
                // revealing, rather than popping the whole section in after the
                // very first digit.
                clearTimeout(revealTimer);
                revealTimer = setTimeout(revealBidTabsIfStillValid, REVEAL_DELAY_MS);
            } else {
                clearTimeout(revealTimer);
                bidTabsSection.hidden = true;
            }
        }

        // Not reusing setFieldError() here: it looks up the nearest
        // [data-field-error] via parentNode, but target_max_bid's parent also
        // contains every Steps row's own error slot — it would write into
        // whichever one happens to come first in the DOM instead of this field's.
        // Only flags the field itself for the basic-format problem; "below
        // current price" already gets its own clearer message via maxBidWarning.
        var showFieldError = v.raw !== '' && !v.basicValid;
        targetMaxBidInput.classList.toggle('has-error', showFieldError);
        if (targetMaxBidError) {
            targetMaxBidError.textContent = showFieldError ? 'Enter a max bid greater than 0.' : '';
        }

        if (!maxBidWarning || !maxBidEstimate) {
            return;
        }

        if (!v.basicValid) {
            maxBidWarning.hidden = true;
            maxBidEstimate.hidden = true;
            return;
        }

        if (v.belowCurrentPrice) {
            maxBidWarning.textContent = 'Your max bid is below the current price ('
                + Bidwraith.currencyPrefix() + currentPrice.toFixed(2)
                + ') — you are unlikely to win at this level.';
            maxBidWarning.hidden = false;
        } else {
            maxBidWarning.hidden = true;
        }

        if (!v.priceKnown) {
            maxBidEstimate.hidden = true;
            return;
        }

        renderEstimate(v.value);
    };

    /** Hides the warning/estimate and puts the tabs back behind their first reveal. */
    Bidwraith.hideMaxBidHelpers = function () {
        bidTabsRevealed = false;
        clearTimeout(revealTimer);
        if (maxBidWarning) {
            maxBidWarning.hidden = true;
        }
        if (maxBidEstimate) {
            maxBidEstimate.hidden = true;
        }
    };

    /** Blanks the field itself, along with any error showing against it. */
    Bidwraith.clearMaxBidField = function () {
        targetMaxBidInput.value = '';
        targetMaxBidInput.classList.remove('has-error');
        if (targetMaxBidError) {
            targetMaxBidError.textContent = '';
        }
    };

    targetMaxBidInput.addEventListener('input', Bidwraith.updateMaxBidHelpers);
})(window.Bidwraith);
