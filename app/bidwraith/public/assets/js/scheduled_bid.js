/**
 * The Scheduled Bid tab (includes/scheduled_bid_view.php): one bid placed either
 * a set time before the auction ends, or at a date and time you pick.
 */
(function (Bidwraith) {
    'use strict';

    /** Fields the user fills in themselves. */
    var INPUT_IDS = ['scheduled_max_bid', 'scheduled_hours', 'scheduled_minutes', 'scheduled_date'];
    /** Everything a reset has to blank, including the hidden UTC mirror of the date field. */
    var RESET_IDS = INPUT_IDS.concat(['scheduled_date_utc']);

    /** Only the fields belonging to the selected mode (offset / date) are shown. */
    document.querySelectorAll('[data-scheduled-mode-toggle]').forEach(function (toggle) {
        var form = toggle.closest('form');
        if (!form) {
            return;
        }
        toggle.querySelectorAll('input[name="scheduled_mode"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                form.querySelectorAll('[data-scheduled-fields]').forEach(function (row) {
                    row.hidden = row.dataset.scheduledFields !== radio.value;
                });
            });
        });
    });

    /**
     * Client-side backstop for the tab, mirroring (loosely) the server's validation
     * in resolve_scheduled_bid_input() — the server has the final say since it alone
     * knows the auction's real end time. Returns an error string, or null if the tab
     * is either valid or left entirely blank (blank isn't an error here — it just
     * means this bid type isn't being used).
     */
    Bidwraith.getScheduledTabError = function (form) {
        var modeInputs = form.querySelectorAll('input[name="scheduled_mode"]');
        if (!modeInputs.length) {
            return null;
        }

        var mode = null;
        modeInputs.forEach(function (r) {
            if (r.checked) {
                mode = r.value;
            }
        });

        var maxBidInput = form.querySelector('#scheduled_max_bid');
        var hoursInput = form.querySelector('#scheduled_hours');
        var minutesInput = form.querySelector('#scheduled_minutes');
        var dateInput = form.querySelector('#scheduled_date');

        var maxBidRaw = maxBidInput ? maxBidInput.value.trim() : '';
        var hoursRaw = hoursInput ? hoursInput.value.trim() : '';
        var minutesRaw = minutesInput ? minutesInput.value.trim() : '';
        var dateRaw = dateInput ? dateInput.value.trim() : '';

        var blank = maxBidRaw === '' && hoursRaw === '' && minutesRaw === '' && dateRaw === '';
        if (blank) {
            return null;
        }

        var maxBid = parseFloat(maxBidRaw);
        if (maxBidRaw === '' || isNaN(maxBid) || maxBid <= 0) {
            return 'The scheduled bid needs a max bid greater than 0.';
        }

        if (mode === 'offset') {
            var hours = hoursRaw === '' ? 0 : parseInt(hoursRaw, 10);
            var minutes = minutesRaw === '' ? 0 : parseInt(minutesRaw, 10);
            if (isNaN(hours) || isNaN(minutes) || hours < 0 || minutes < 0 || minutes > 59) {
                return 'Hours and minutes before the end must be whole numbers, with minutes 0-59.';
            }
            if (hours === 0 && minutes === 0) {
                return 'Enter hours and/or minutes before the end for the scheduled bid.';
            }
        } else if (mode === 'date') {
            if (dateRaw === '') {
                return 'Pick a date and time for the scheduled bid to fire.';
            }
        }

        return null;
    };

    /** Whether the tab has anything typed into it. */
    Bidwraith.hasScheduledBid = function (form) {
        return INPUT_IDS.some(function (id) {
            var el = form.querySelector('#' + id);
            return !!el && el.value.trim() !== '';
        });
    };

    /** Blanks the tab back to its fresh, empty-form state (offset mode, no values). */
    Bidwraith.resetScheduledFields = function (form) {
        RESET_IDS.forEach(function (id) {
            var el = form.querySelector('#' + id);
            if (el) {
                el.value = '';
            }
        });
        var offsetRadio = form.querySelector('input[name="scheduled_mode"][value="offset"]');
        if (offsetRadio) {
            offsetRadio.checked = true;
        }
        form.querySelectorAll('[data-scheduled-fields]').forEach(function (row) {
            row.hidden = row.dataset.scheduledFields !== 'offset';
        });
    };
})(window.Bidwraith);
