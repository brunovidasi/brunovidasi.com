/**
 * The "Max bid" field at the top of the add-auction form: its validity, the
 * warning when it sits below the item's current price, the landed-cost estimate,
 * and the first reveal of the strategies/tabs section below it.
 *
 * Everything here is deliberately split into two halves: an immediate one that
 * only ever *hides* things (so clearing the field clears the page with it), and a
 * settled one that waits for a pause in typing before showing anything. A max bid
 * is typed one digit at a time and every prefix of it ("5" on the way to "56") is
 * a different, usually wrong, number — reacting to each of those in turn is what
 * made the page flicker and pop open mid-keystroke.
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
    // field goes through invalid or too-low in-between states — see
    // collapseBidTabsIfUnused() for the one case that closes them again.
    var bidTabsRevealed = !!(bidTabsSection && !bidTabsSection.hidden);
    // How long the field has to sit still before anything reacts to it. Long
    // enough to type a two- or three-digit amount straight through without the
    // page moving underneath, short enough not to feel stuck.
    var SETTLE_DELAY_MS = 800;
    var settleTimer = null;

    /**
     * Reads target_max_bid's current value and how it stands against the item's
     * current price. Read fresh at settle time rather than captured when the timer
     * was set, since the value (or a late item lookup) may have changed since.
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

    /** Whether any of the bid tabs has something entered in it. */
    function bidTabsHaveInput() {
        var form = targetMaxBidInput.closest('form');
        if (!form) {
            return false;
        }
        return (Bidwraith.hasAnyStep && Bidwraith.hasAnyStep(form))
            || (Bidwraith.hasScheduledBid && Bidwraith.hasScheduledBid(form))
            || (Bidwraith.hasAnywayBid && Bidwraith.hasAnywayBid(form));
    }

    /**
     * Puts the tabs back behind their first reveal when the max bid is cleared —
     * but only while they're still empty. Once there are bids in them, collapsing
     * would hide the user's own work (and the Steps tab's last max bid mirrors
     * back into this very field, so clearing it there must not close the tab
     * being typed in — see bid_steps.js).
     */
    function collapseBidTabsIfUnused() {
        if (!bidTabsSection || !bidTabsRevealed || bidTabsHaveInput()) {
            return;
        }
        bidTabsSection.hidden = true;
        bidTabsRevealed = false;
    }

    /** Clears the field error, the below-price warning and the cost estimate. */
    function clearMaxBidFeedback() {
        targetMaxBidInput.classList.remove('has-error');
        if (targetMaxBidError) {
            targetMaxBidError.textContent = '';
        }
        if (maxBidWarning) {
            maxBidWarning.hidden = true;
        }
        if (maxBidEstimate) {
            maxBidEstimate.hidden = true;
        }
    }

    /**
     * Everything that should only happen once the user has stopped typing: the
     * verdict on the field, the below-price warning, the cost estimate, and the
     * one-time reveal of the strategies/tabs section.
     */
    function applySettledState() {
        var v = readMaxBidValidity();
        var currentPrice = Bidwraith.item.currentPrice;

        // Only flags the field itself for the basic-format problem; "below
        // current price" already gets its own clearer message via maxBidWarning.
        //
        // Not reusing setFieldError() here: it looks up the nearest
        // [data-field-error] via parentNode, but target_max_bid's parent also
        // contains every Steps row's own error slot — it would write into
        // whichever one happens to come first in the DOM instead of this field's.
        var showFieldError = v.raw !== '' && !v.basicValid;
        targetMaxBidInput.classList.toggle('has-error', showFieldError);
        if (targetMaxBidError) {
            targetMaxBidError.textContent = showFieldError ? 'Enter a max bid greater than 0.' : '';
        }

        // A max bid already below the current price could never win, and the
        // Strategies tab needs a real number to scale a ladder from, so the
        // section waits for one before appearing for the first time.
        if (bidTabsSection && !bidTabsRevealed && v.basicValid && !v.belowCurrentPrice) {
            bidTabsSection.hidden = false;
            bidTabsRevealed = true;
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
    }

    /**
     * Call on every change to the max bid field. Pass `immediate` when the change
     * didn't come from typing (a blur, or an item lookup landing), where there's
     * no next keystroke to wait for.
     */
    Bidwraith.updateMaxBidHelpers = function (immediate) {
        var currentPrice = Bidwraith.item.currentPrice;
        var priceKnown = typeof currentPrice === 'number' && !isNaN(currentPrice);

        targetMaxBidInput.placeholder = priceKnown
            ? 'Max bid should be more than ' + Bidwraith.currencyPrefix() + currentPrice.toFixed(2)
            : 'e.g. 75.00';

        clearTimeout(settleTimer);

        // An empty field has nothing to say anything about, so everything hanging
        // off it goes at once rather than lingering until the next settle.
        if (targetMaxBidInput.value.trim() === '') {
            clearMaxBidFeedback();
            collapseBidTabsIfUnused();
            return;
        }

        if (immediate) {
            applySettledState();
            return;
        }
        settleTimer = setTimeout(applySettledState, SETTLE_DELAY_MS);
    };

    /** Hides the warning/estimate and puts the tabs back behind their first reveal. */
    Bidwraith.hideMaxBidHelpers = function () {
        clearTimeout(settleTimer);
        bidTabsRevealed = false;
        if (bidTabsSection) {
            bidTabsSection.hidden = true;
        }
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

    targetMaxBidInput.addEventListener('input', function () {
        Bidwraith.updateMaxBidHelpers(false);
    });

    // Leaving the field is as clear an "I've finished typing" as a pause is, and
    // waiting out the timer after that just looks unresponsive. 'change' covers the
    // same thing for a value that arrived all at once ("Add random cents").
    ['blur', 'change'].forEach(function (event) {
        targetMaxBidInput.addEventListener(event, function () {
            Bidwraith.updateMaxBidHelpers(true);
        });
    });
})(window.Bidwraith);
