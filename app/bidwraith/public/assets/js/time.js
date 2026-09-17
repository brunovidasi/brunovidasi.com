/**
 * Everything that turns a server-side instant into the visitor's own clock, and
 * back again: the live countdowns, the absolute timestamps rendered by
 * local_time_html() in includes/helpers.php, and the wall-clock inputs that need
 * a UTC epoch attached before they reach the server.
 */
(function () {
    'use strict';

    /** Live "2d 04:31:09 left" countdowns on [data-countdown-end] elements. */
    (function () {
        var timers = document.querySelectorAll('[data-countdown-end]');
        if (!timers.length) {
            return;
        }
        // Count against the server's clock so a skewed client clock doesn't shift the timer.
        var holder = document.querySelector('[data-server-now]');
        var offsetMs = holder ? parseInt(holder.dataset.serverNow, 10) * 1000 - Date.now() : 0;

        function pad(n) {
            return n < 10 ? '0' + n : String(n);
        }

        function tick() {
            var now = Date.now() + offsetMs;
            timers.forEach(function (el) {
                var left = Math.floor((parseInt(el.dataset.countdownEnd, 10) * 1000 - now) / 1000);
                if (left <= 0) {
                    el.textContent = 'Ended';
                    el.classList.add('is-urgent');
                    return;
                }
                var days = Math.floor(left / 86400);
                var hours = Math.floor((left % 86400) / 3600);
                var minutes = Math.floor((left % 3600) / 60);
                var seconds = left % 60;
                el.textContent = (days ? days + 'd ' : '') + pad(hours) + ':' + pad(minutes) + ':' + pad(seconds) + ' left';
                el.classList.toggle('is-urgent', left < 3600);
            });
        }

        tick();
        setInterval(tick, 1000);
    })();

    /** Epochs rendered server-side, re-formatted in the visitor's own timezone. */
    (function () {
        var els = document.querySelectorAll('[data-local-time]');
        if (!els.length) {
            return;
        }
        els.forEach(function (el) {
            var epoch = parseInt(el.dataset.localTime, 10);
            if (isNaN(epoch)) {
                return;
            }
            var d = new Date(epoch * 1000);
            if (isNaN(d.getTime())) {
                return;
            }
            el.textContent = d.toLocaleString(undefined, {
                year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
            });
        });
    })();

    /**
     * A datetime-local field is a plain wall-clock value with no timezone of its
     * own, so the browser is the only one who knows what timezone the person
     * typing it is actually in. Convert it into the paired hidden UTC epoch field
     * on submit rather than letting the server guess.
     */
    function syncLocalDateToUtc(inputId, utcInputId) {
        var input = document.getElementById(inputId);
        var utcInput = document.getElementById(utcInputId);
        if (!input || !utcInput) {
            return;
        }
        var form = input.closest('form');
        if (!form) {
            return;
        }
        form.addEventListener('submit', function () {
            if (!input.value) {
                return;
            }
            var d = new Date(input.value);
            if (!isNaN(d.getTime())) {
                utcInput.value = Math.floor(d.getTime() / 1000);
            }
        });
    }

    // The manual auction end time (shown when the lookup couldn't find one) and
    // the scheduled bid's own date/time picker.
    syncLocalDateToUtc('end_time', 'end_time_utc');
    syncLocalDateToUtc('scheduled_date', 'scheduled_date_utc');
})();
