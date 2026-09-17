/**
 * Pulls the three bid tabs together into one verdict on the form as a whole:
 * gates the Save button on it live, and re-checks it as a backstop on submit.
 *
 * Loaded last, since it calls into every tab's own validator.
 */
(function (Bidwraith) {
    'use strict';

    /**
     * Whether the form has at least one bid entered anywhere — mirrors
     * add_auction.php's "Add at least one bid" requirement. Only add_auction.php
     * enforces this (see the data-requires-bid form attribute); editing an auction
     * down to zero bids is allowed, so edit_auction.php never sets it.
     */
    function formHasAnyBid(form) {
        return Bidwraith.hasAnyStep(form)
            || Bidwraith.hasScheduledBid(form)
            || Bidwraith.hasAnywayBid(form);
    }

    /**
     * Whether the bid form currently has anything that would make the server reject
     * it. Returns null when the form is submittable.
     */
    function getBidFormError(form) {
        var itemIdInput = form.querySelector('#item_id');
        if (itemIdInput && itemIdInput.value.trim() === '') {
            return 'Enter an eBay item ID.';
        }

        return getBidTabError(form)
            || (form.hasAttribute('data-requires-bid') && !formHasAnyBid(form)
                ? 'Add at least one bid: a Steps bid, a Scheduled bid, or "I want the item anyway".'
                : null);
    }

    /** The first complaint any of the three bid tabs has, or null if they're all happy. */
    function getBidTabError(form) {
        return Bidwraith.getStepsTabError(form)
            || Bidwraith.getScheduledTabError(form)
            || Bidwraith.getAnywayTabError(form);
    }

    /**
     * Keeps the Save button disabled until the form would actually pass validation —
     * the server still has the final say (it alone knows real end times, for
     * instance), but this stops the common case of an obviously invalid submit
     * round-tripping to the server just to bounce back with an error.
     */
    function updateSaveButtonState(form) {
        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = !!getBidFormError(form);
        }
    }

    document.querySelectorAll('form').forEach(function (form) {
        var hasBidFields = form.querySelector('.bid-step-row, input[name="scheduled_mode"], input[name="anyway_increment_type"]');
        if (!hasBidFields) {
            return;
        }

        // The tab checks are the final backstop in case the Save-button gating was
        // somehow bypassed (e.g. a forced click on a disabled-then-re-enabled
        // button). Scoped to the tabs: the item ID and "at least one bid" rules
        // gate the button but are left to the server to report on submit.
        form.addEventListener('submit', function (e) {
            var error = getBidTabError(form);
            Bidwraith.showBidStepError(form, error);
            if (error) {
                e.preventDefault();
            }
        });

        if (!form.querySelector('button[type="submit"]')) {
            return;
        }

        var update = function () { updateSaveButtonState(form); };
        // Covers typing in any field, plus radios/checkboxes (which fire both
        // events in modern browsers) — a single delegated pair beats wiring every
        // individual field, and stays correct as rows are added dynamically.
        form.addEventListener('input', update);
        form.addEventListener('change', update);

        // Exposed (and called through Bidwraith.revalidateSave) so code that sets a
        // field's .value directly can refresh the button without relying on a native
        // event that a programmatic value change doesn't dispatch on its own.
        form.bidwraithRevalidateSave = update;
        update();
    });
})(window.Bidwraith);
