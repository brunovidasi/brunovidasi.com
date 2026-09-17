/**
 * Shared namespace and small helpers every other file builds on.
 *
 * The app has no build step: each file is a plain <script> (see app_scripts() in
 * includes/helpers.php for the load order) and they talk to each other through
 * the single `Bidwraith` global this file creates, rather than through loose
 * top-level globals.
 */
window.Bidwraith = window.Bidwraith || {};

(function (Bidwraith) {
    'use strict';

    /**
     * Details of the eBay item currently attached to the form, filled in by the
     * item lookup and read by anything that prices a bid against it (strategy
     * ladders, the max-bid warning, the landed-cost estimate). Null until an
     * item has actually been looked up.
     */
    Bidwraith.item = {
        currentPrice: null,
        shippingCost: null,
        country: null,
        currency: null,
    };

    Bidwraith.resetItem = function () {
        Bidwraith.item.currentPrice = null;
        Bidwraith.item.shippingCost = null;
        Bidwraith.item.country = null;
        Bidwraith.item.currency = null;
    };

    /** The item's currency as a display prefix ('AUD ', or '' when unknown). */
    Bidwraith.currencyPrefix = function () {
        return Bidwraith.item.currency ? Bidwraith.item.currency + ' ' : '';
    };

    /** Writes (or clears, with a null message) the error slot next to one field. */
    Bidwraith.setFieldError = function (input, message) {
        var el = input.parentNode.querySelector('[data-field-error]');
        if (!el) {
            return;
        }
        el.textContent = message || '';
        input.classList.toggle('has-error', !!message);
    };

    Bidwraith.clearAllFieldErrors = function (form) {
        form.querySelectorAll('.bid-step-row input[type=number]').forEach(function (input) {
            Bidwraith.setFieldError(input, null);
        });
    };

    /**
     * Steps rows are fixed DOM slots toggled via [hidden] rather than added and
     * removed, so "the rows in play" means the visible ones.
     */
    Bidwraith.visibleBidStepRows = function (form) {
        return Array.prototype.filter.call(form.querySelectorAll('.bid-step-row'), function (row) {
            return !row.hidden;
        });
    };

    /** Shows (or removes, with a null message) the form-level error banner. */
    Bidwraith.showBidStepError = function (form, message) {
        var existing = form.parentNode.querySelector('.bid-step-client-error');
        if (existing) {
            existing.remove();
        }
        if (!message) {
            return;
        }
        var div = document.createElement('div');
        div.className = 'flash flash-error bid-step-client-error';
        div.textContent = message;
        form.parentNode.insertBefore(div, form);
        div.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    /**
     * Re-runs the Save-button gating set up in bid_form.js. Anything that sets a
     * field's .value directly (strategies, add-step, random cents) has to ask for
     * this, since a programmatic value change fires no native input event.
     */
    Bidwraith.revalidateSave = function (form) {
        if (form && form.bidwraithRevalidateSave) {
            form.bidwraithRevalidateSave();
        }
    };
})(window.Bidwraith);
