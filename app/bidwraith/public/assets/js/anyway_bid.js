/**
 * The "I Want The Item Anyway" tab (includes/anyway_bid_view.php): keep bidding a
 * little over the current price, right before the end, up to an optional ceiling.
 */
(function (Bidwraith) {
    'use strict';

    var INPUT_IDS = ['anyway_increment_amount', 'anyway_seconds_before', 'anyway_max_bid'];

    /**
     * The amount field is either a currency value or a percentage depending on the
     * increment-type radio, so its unit label switches to match instead of always
     * showing "AUD or %" regardless of which is selected.
     */
    function setIncrementUnit(form, type) {
        var toggle = form.querySelector('[data-anyway-increment-toggle]');
        var unitEl = form.querySelector('[data-anyway-increment-unit]');
        if (!toggle || !unitEl) {
            return;
        }
        unitEl.textContent = type === 'percent' ? '%' : (toggle.dataset.currency || '');
    }

    document.querySelectorAll('[data-anyway-increment-toggle]').forEach(function (toggle) {
        var form = toggle.closest('form');
        if (!form || !form.querySelector('[data-anyway-increment-unit]')) {
            return;
        }
        toggle.querySelectorAll('input[name="anyway_increment_type"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                setIncrementUnit(form, radio.value);
            });
        });
    });

    /**
     * Client-side backstop for the tab, mirroring (loosely) the server's validation
     * in resolve_anyway_bid_input(). Returns an error string, or null if the tab is
     * either valid or left entirely blank.
     */
    Bidwraith.getAnywayTabError = function (form) {
        var incrementTypeInputs = form.querySelectorAll('input[name="anyway_increment_type"]');
        if (!incrementTypeInputs.length) {
            return null;
        }

        var incrementAmountInput = form.querySelector('#anyway_increment_amount');
        var secondsInput = form.querySelector('#anyway_seconds_before');
        var maxBidInput = form.querySelector('#anyway_max_bid');

        var incrementAmountRaw = incrementAmountInput ? incrementAmountInput.value.trim() : '';
        var secondsRaw = secondsInput ? secondsInput.value.trim() : '';
        var maxBidRaw = maxBidInput ? maxBidInput.value.trim() : '';

        var blank = incrementAmountRaw === '' && secondsRaw === '' && maxBidRaw === '';
        if (blank) {
            return null;
        }

        var incrementAmount = parseFloat(incrementAmountRaw);
        if (incrementAmountRaw === '' || isNaN(incrementAmount) || incrementAmount <= 0) {
            return 'Enter how much more than the current price to bid, greater than 0.';
        }

        if (secondsRaw !== '') {
            var seconds = parseInt(secondsRaw, 10);
            if (isNaN(seconds) || seconds < 1 || seconds > 60) {
                return 'Seconds before end must be between 1 and 60.';
            }
        }

        if (maxBidRaw !== '') {
            var maxBid = parseFloat(maxBidRaw);
            if (isNaN(maxBid) || maxBid <= 0) {
                return 'The max value for "I want the item anyway", if set, must be greater than 0.';
            }
        }

        return null;
    };

    /** Whether the tab has anything typed into it. */
    Bidwraith.hasAnywayBid = function (form) {
        return INPUT_IDS.some(function (id) {
            var el = form.querySelector('#' + id);
            return !!el && el.value.trim() !== '';
        });
    };

    /** Blanks the tab back to its fresh, empty-form state (a value increment, no amounts). */
    Bidwraith.resetAnywayFields = function (form) {
        INPUT_IDS.forEach(function (id) {
            var el = form.querySelector('#' + id);
            if (el) {
                el.value = '';
            }
        });
        var valueRadio = form.querySelector('input[name="anyway_increment_type"][value="value"]');
        if (valueRadio) {
            valueRadio.checked = true;
        }
        setIncrementUnit(form, 'value');
    };
})(window.Bidwraith);
