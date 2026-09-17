function showConfirmModal(message) {
    var overlay = document.getElementById('confirmModal');
    if (!overlay) {
        return Promise.resolve(true);
    }
    var messageEl = document.getElementById('confirmModalMessage');
    var okBtn = document.getElementById('confirmModalOk');
    var cancelBtn = document.getElementById('confirmModalCancel');

    return new Promise(function (resolve) {
        messageEl.textContent = message;
        overlay.hidden = false;
        okBtn.focus();

        function cleanup(result) {
            overlay.hidden = true;
            okBtn.removeEventListener('click', onOk);
            cancelBtn.removeEventListener('click', onCancel);
            overlay.removeEventListener('click', onOverlayClick);
            document.removeEventListener('keydown', onKeydown);
            resolve(result);
        }

        function onOk() { cleanup(true); }
        function onCancel() { cleanup(false); }
        function onOverlayClick(e) {
            if (e.target === overlay) {
                cleanup(false);
            }
        }
        function onKeydown(e) {
            if (e.key === 'Escape') {
                cleanup(false);
            }
        }

        okBtn.addEventListener('click', onOk);
        cancelBtn.addEventListener('click', onCancel);
        overlay.addEventListener('click', onOverlayClick);
        document.addEventListener('keydown', onKeydown);
    });
}

document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        showConfirmModal(form.dataset.confirm).then(function (confirmed) {
            if (confirmed) {
                form.submit();
            }
        });
    });
});

(function () {
    var toggle = document.getElementById('navToggle');
    var menu = document.getElementById('navMenu');
    if (!toggle || !menu) {
        return;
    }
    toggle.addEventListener('click', function () {
        var isOpen = menu.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    menu.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            menu.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        });
    });
})();

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

(function () {
    // The manual end-time field is a plain wall-clock value with no timezone of its
    // own, so the browser is the only one who knows what timezone the person typing
    // it is actually in. Convert it here rather than letting the server guess.
    var input = document.getElementById('end_time');
    var utcInput = document.getElementById('end_time_utc');
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
})();

function switchBidTab(form, tab) {
    form.querySelectorAll('[data-bid-tab]').forEach(function (btn) {
        var active = btn.dataset.bidTab === tab;
        btn.classList.toggle('is-active', active);
        btn.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    form.querySelectorAll('[data-bid-panel]').forEach(function (panel) {
        panel.hidden = panel.dataset.bidPanel !== tab;
    });
}

document.querySelectorAll('[data-bid-tab]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var form = btn.closest('form');
        if (form) {
            switchBidTab(form, btn.dataset.bidTab);
        }
    });
});

var bidwraithCurrentPrice = null;
var bidwraithShippingCost = null;
var bidwraithItemCountry = null;
var bidwraithCurrency = null;

/**
 * JS port of estimate_buyer_protection_fee() in includes/helpers.php — must be
 * kept in sync with it. Duplicated here (rather than fetched from the server)
 * so the estimate updates live as the user types, with no round trip.
 */
function estimateBuyerProtectionFee(price) {
    var fee = 0.30;
    fee += 0.08 * Math.min(price, 20);
    if (price > 20) {
        fee += 0.06 * (Math.min(price, 500) - 20);
    }
    if (price > 500) {
        fee += 0.04 * (Math.min(price, 5000) - 500);
    }
    return Math.round(fee * 100) / 100;
}

/** JS port of estimate_landed_cost() in includes/helpers.php — keep in sync. */
function estimateLandedCost(price, shippingCost, itemCountry, homeCountry) {
    var shipping = (typeof shippingCost === 'number' && !isNaN(shippingCost)) ? shippingCost : 0;
    var buyerProtectionFee = estimateBuyerProtectionFee(price);
    var isOverseas = !!(itemCountry && homeCountry && itemCountry.toUpperCase() !== homeCountry.toUpperCase());
    var gst = (isOverseas && (price + shipping) <= 1000) ? Math.round(0.10 * (price + shipping) * 100) / 100 : 0;

    return {
        shipping: Math.round(shipping * 100) / 100,
        buyer_protection_fee: buyerProtectionFee,
        gst: gst,
        is_overseas: isOverseas,
        total: Math.round((price + shipping + buyerProtectionFee + gst) * 100) / 100,
    };
}

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

function applyStrategy(card) {
    var form = card.closest('form');
    if (!form) {
        return;
    }
    var seconds = JSON.parse(card.dataset.strategy);

    var targetMaxBidInput = form.querySelector('#target_max_bid');
    var targetMaxBid = targetMaxBidInput ? parseFloat(targetMaxBidInput.value) : NaN;
    var stepMaxBids = !isNaN(targetMaxBid) && targetMaxBid > 0
        ? computeStrategyMaxBids(seconds.length, targetMaxBid, bidwraithCurrentPrice)
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
        addBtn.hidden = seconds.length >= 5;
    }

    showBidStepError(form, null);
    clearAllFieldErrors(form);
    switchBidTab(form, 'custom');

    var firstMaxBid = form.querySelector('.bid-step-row:not([hidden]) input[name="step_max_bid[]"]');
    if (firstMaxBid) {
        firstMaxBid.focus();
    }

    if (form.bidwraithRevalidateSave) {
        form.bidwraithRevalidateSave();
    }
}

document.querySelectorAll('[data-strategy]').forEach(function (card) {
    card.addEventListener('click', function () {
        var confirmMessage = card.dataset.strategyConfirm;
        if (confirmMessage) {
            showConfirmModal(confirmMessage).then(function (confirmed) {
                if (confirmed) {
                    applyStrategy(card);
                }
            });
            return;
        }
        applyStrategy(card);
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
        var goToTab = function () {
            switchBidTab(form, card.dataset.strategyGoto);
        };
        var confirmMessage = card.dataset.strategyConfirm;
        if (confirmMessage) {
            showConfirmModal(confirmMessage).then(function (confirmed) {
                if (confirmed) {
                    goToTab();
                }
            });
            return;
        }
        goToTab();
    });
});

(function () {
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
})();

// The "I Want The Item Anyway" amount field is either a currency value or a
// percentage depending on the increment-type radio, so its unit label switches
// to match instead of always showing "AUD or %" regardless of which is selected.
(function () {
    document.querySelectorAll('[data-anyway-increment-toggle]').forEach(function (toggle) {
        var form = toggle.closest('form');
        var unitEl = form ? form.querySelector('[data-anyway-increment-unit]') : null;
        if (!unitEl) {
            return;
        }
        var currency = toggle.dataset.currency || '';
        toggle.querySelectorAll('input[name="anyway_increment_type"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                unitEl.textContent = radio.value === 'percent' ? '%' : currency;
            });
        });
    });
})();

// Same local-to-UTC conversion the manual end-time field uses, for the scheduled
// bid's own date/time picker.
(function () {
    var input = document.getElementById('scheduled_date');
    var utcInput = document.getElementById('scheduled_date_utc');
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
})();

document.querySelectorAll('[data-random-cents-scheduled]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var form = btn.closest('form');
        var input = form ? form.querySelector('#scheduled_max_bid') : document.getElementById('scheduled_max_bid');
        applyRandomCents(input);
    });
});

/**
 * Client-side backstop for the Scheduled Bid tab, mirroring (loosely) the server's
 * validation in resolve_scheduled_bid_input() — the server has the final say since
 * it alone knows the auction's real end time. Returns an error string, or null if
 * the tab is either valid or left entirely blank (blank isn't an error here — it
 * just means this bid type isn't being used).
 */
function getScheduledTabError(form) {
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
}

/**
 * Client-side backstop for the "I Want The Item Anyway" tab, mirroring (loosely)
 * the server's validation in resolve_anyway_bid_input(). Returns an error string,
 * or null if the tab is either valid or left entirely blank.
 */
function getAnywayTabError(form) {
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
}

// The functions above are also used live (see the Save-button gating further
// down); on submit they're still the final backstop in case that gating was
// somehow bypassed (e.g. a forced click on a disabled-then-re-enabled button).
document.querySelectorAll('form').forEach(function (form) {
    if (!form.querySelectorAll('input[name="scheduled_mode"]').length) {
        return;
    }
    form.addEventListener('submit', function (e) {
        var error = getScheduledTabError(form);
        if (error) {
            showBidStepError(form, error);
            e.preventDefault();
        }
    });
});

document.querySelectorAll('form').forEach(function (form) {
    if (!form.querySelectorAll('input[name="anyway_increment_type"]').length) {
        return;
    }
    form.addEventListener('submit', function (e) {
        var error = getAnywayTabError(form);
        if (error) {
            showBidStepError(form, error);
            e.preventDefault();
        }
    });
});

function setFieldError(input, message) {
    var el = input.parentNode.querySelector('[data-field-error]');
    if (!el) {
        return;
    }
    el.textContent = message || '';
    input.classList.toggle('has-error', !!message);
}

function visibleBidStepRows(form) {
    return Array.prototype.filter.call(form.querySelectorAll('.bid-step-row'), function (row) {
        return !row.hidden;
    });
}

/**
 * Client-side backstop for the Steps tab, mirroring (loosely) the server's
 * validation in add_auction.php/edit_auction.php: each filled-in row needs whole
 * seconds 1-60 and a max bid greater than 0, seconds can't repeat, and — same rule
 * as revalidateStepOrder() enforces per-field — a bid closer to the end can't be
 * lower than one further out. A row left entirely blank is skipped, same as the
 * server ignores it. Returns an error string, or null if every filled row is valid.
 */
function getStepsTabError(form) {
    var steps = [];
    var rows = visibleBidStepRows(form);
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

    var secondsSeen = [];
    for (var j = 0; j < steps.length; j++) {
        if (secondsSeen.indexOf(steps[j].seconds) !== -1) {
            return 'Each bid must use a different number of seconds before the end.';
        }
        secondsSeen.push(steps[j].seconds);
    }

    var sorted = steps.slice().sort(function (a, b) { return b.seconds - a.seconds; });
    for (var k = 1; k < sorted.length; k++) {
        if (sorted[k].maxBid < sorted[k - 1].maxBid) {
            return 'The bid closer to the end can\'t be smaller than the one before it.';
        }
    }

    return null;
}

/**
 * Whether the form has at least one bid entered anywhere (a filled Steps row, a
 * filled Scheduled Bid tab, or a filled "I want the item anyway" tab) — mirrors
 * add_auction.php's "Add at least one bid" requirement. Only add_auction.php
 * enforces this (see the data-requires-bid form attribute); editing an auction
 * down to zero bids is allowed, so edit_auction.php never calls this.
 */
function formHasAnyBid(form) {
    var rows = visibleBidStepRows(form);
    for (var i = 0; i < rows.length; i++) {
        var secondsInput = rows[i].querySelector('input[name="step_seconds[]"]');
        var maxBidInput = rows[i].querySelector('input[name="step_max_bid[]"]');
        if ((secondsInput && secondsInput.value.trim() !== '') || (maxBidInput && maxBidInput.value.trim() !== '')) {
            return true;
        }
    }

    var scheduledFields = ['#scheduled_max_bid', '#scheduled_hours', '#scheduled_minutes', '#scheduled_date'];
    for (var s = 0; s < scheduledFields.length; s++) {
        var el = form.querySelector(scheduledFields[s]);
        if (el && el.value.trim() !== '') {
            return true;
        }
    }

    var anywayFields = ['#anyway_increment_amount', '#anyway_seconds_before', '#anyway_max_bid'];
    for (var a = 0; a < anywayFields.length; a++) {
        var el2 = form.querySelector(anywayFields[a]);
        if (el2 && el2.value.trim() !== '') {
            return true;
        }
    }

    return false;
}

/**
 * Live feedback as the user tabs between bid steps: each step is expected to fire
 * closer to the auction's end than the one before it, so its seconds-before-end
 * must be lower and its max bid must be equal or higher than the previous step's
 * (the same rule the server enforces on submit, checked here row-by-row against
 * the step directly above it since that's how the form is filled in).
 */
function revalidateStepOrder(form) {
    var rows = visibleBidStepRows(form);
    rows.forEach(function (row, i) {
        var secondsInput = row.querySelector('input[name="step_seconds[]"]');
        var maxBidInput = row.querySelector('input[name="step_max_bid[]"]');
        if (!secondsInput || !maxBidInput) {
            return;
        }
        if (i === 0) {
            setFieldError(secondsInput, null);
            setFieldError(maxBidInput, null);
            return;
        }

        var prevRow = rows[i - 1];
        var prevSeconds = parseInt(prevRow.querySelector('input[name="step_seconds[]"]').value, 10);
        var prevMaxBid = parseFloat(prevRow.querySelector('input[name="step_max_bid[]"]').value);
        var seconds = parseInt(secondsInput.value, 10);
        var maxBid = parseFloat(maxBidInput.value);

        if (!isNaN(seconds) && !isNaN(prevSeconds) && seconds >= prevSeconds) {
            setFieldError(secondsInput, 'Must be fewer seconds before the end than step ' + i + ' (' + prevSeconds + 's).');
        } else {
            setFieldError(secondsInput, null);
        }

        if (!isNaN(maxBid) && !isNaN(prevMaxBid) && maxBid < prevMaxBid) {
            setFieldError(maxBidInput, 'Can\'t be lower than step ' + i + '\'s max bid (' + prevMaxBid + ').');
        } else {
            setFieldError(maxBidInput, null);
        }
    });
}

function clearAllFieldErrors(form) {
    form.querySelectorAll('.bid-step-row input[type=number]').forEach(function (input) {
        setFieldError(input, null);
    });
}

document.querySelectorAll('.bid-step-row input[name="step_seconds[]"], .bid-step-row input[name="step_max_bid[]"]').forEach(function (input) {
    input.addEventListener('blur', function () {
        var form = input.closest('form');
        if (form) {
            revalidateStepOrder(form);
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

        var rows = visibleBidStepRows(form);
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
        if (form.bidwraithRevalidateSave) {
            form.bidwraithRevalidateSave();
        }
    });
});

/**
 * A max bid ending in a common round number (.00, .50) is easy for another
 * sniper's bid to land on or just beat. Bumping it to a random ending over
 * 51 cents keeps the whole-dollar amount intact but makes the final cents
 * unpredictable, which is enough to win most eBay proxy-bidding ties.
 *
 * Every step must stay equal or higher than the step before it — the same
 * rule the server enforces — so `minValue`/`maxValue` let the caller pass
 * the neighboring steps' amounts (previous and next) and this keeps the
 * result within that window. That way clicking the button can never itself
 * put a step's order out of line and trigger a validation error: if the
 * >51-cents band doesn't fit inside the window, it falls back to whatever
 * room the neighbors actually leave.
 */
function randomCentsValue(current, minValue, maxValue) {
    var base = parseFloat(current);
    if (isNaN(base) || base < 0) {
        base = 0;
    }
    var dollars = Math.floor(base);

    var lowCents = dollars * 100 + 51;
    var highCents = dollars * 100 + 99;

    var minTotal = (typeof minValue === 'number' && !isNaN(minValue)) ? Math.round(minValue * 100) : null;
    var maxTotal = (typeof maxValue === 'number' && !isNaN(maxValue)) ? Math.round(maxValue * 100) : null;

    if (minTotal !== null && lowCents < minTotal) {
        lowCents = minTotal;
    }
    if (maxTotal !== null && highCents > maxTotal) {
        highCents = maxTotal;
    }

    if (lowCents > highCents) {
        lowCents = minTotal !== null ? minTotal : dollars * 100;
        highCents = maxTotal !== null ? maxTotal : dollars * 100 + 99;
        if (lowCents > highCents) {
            highCents = lowCents;
        }
    }

    var totalCents = lowCents + Math.floor(Math.random() * (highCents - lowCents + 1));
    return totalCents / 100;
}

function applyRandomCents(input, minValue, maxValue) {
    if (!input || input.readOnly || input.disabled) {
        return null;
    }
    var value = randomCentsValue(input.value, minValue, maxValue);
    input.value = value.toFixed(2);
    // Setting .value directly doesn't fire a native input event, so anything
    // listening for one (the max-bid warning/estimate box, the Save-button
    // validity check) wouldn't otherwise notice this changed.
    input.dispatchEvent(new Event('input', { bubbles: true }));
    return value;
}

document.querySelectorAll('[data-random-cents-target]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var form = btn.closest('form');
        var input = form ? form.querySelector('#target_max_bid') : document.getElementById('target_max_bid');
        applyRandomCents(input);
    });
});

document.querySelectorAll('.bid-step-row [data-random-cents]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var field = btn.closest('.bid-step-field');
        var input = field ? field.querySelector('input[type=number]') : null;
        var form = btn.closest('form');
        var row = btn.closest('.bid-step-row');
        var minValue, maxValue;

        if (form && row) {
            var rows = visibleBidStepRows(form);
            var rowIndex = rows.indexOf(row);
            if (rowIndex > 0) {
                var prevMaxBid = parseFloat(rows[rowIndex - 1].querySelector('input[name="step_max_bid[]"]').value);
                if (!isNaN(prevMaxBid)) {
                    minValue = prevMaxBid;
                }
            }
            if (rowIndex >= 0 && rowIndex < rows.length - 1) {
                var nextMaxBid = parseFloat(rows[rowIndex + 1].querySelector('input[name="step_max_bid[]"]').value);
                if (!isNaN(nextMaxBid)) {
                    maxValue = nextMaxBid;
                }
            }
        }

        applyRandomCents(input, minValue, maxValue);
        if (form) {
            revalidateStepOrder(form);
        }
    });
});

document.querySelectorAll('[data-random-cents-all]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var form = btn.closest('form');
        if (!form) {
            return;
        }
        var minValue;
        visibleBidStepRows(form).forEach(function (row) {
            var input = row.querySelector('input[name="step_max_bid[]"]');
            var value = applyRandomCents(input, minValue);
            if (value !== null) {
                minValue = value;
            }
        });
        revalidateStepOrder(form);
    });
});

function showBidStepError(form, message) {
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
}

/**
 * Whether the bid form currently has anything that would make the server reject
 * it, reusing the same per-tab checks the submit-time backstops fall back on.
 * Returns null when the form is submittable.
 */
function getBidFormError(form) {
    var itemIdInput = form.querySelector('#item_id');
    if (itemIdInput && itemIdInput.value.trim() === '') {
        return 'Enter an eBay item ID.';
    }

    return getStepsTabError(form)
        || getScheduledTabError(form)
        || getAnywayTabError(form)
        || (form.hasAttribute('data-requires-bid') && !formHasAnyBid(form)
            ? 'Add at least one bid: a Steps bid, a Scheduled bid, or "I want the item anyway".'
            : null);
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
    if (!hasBidFields || !form.querySelector('button[type="submit"]')) {
        return;
    }

    var update = function () { updateSaveButtonState(form); };
    // Covers typing in any field, plus radios/checkboxes (which fire both
    // events in modern browsers) — a single delegated pair beats wiring every
    // individual field, and stays correct as rows are added dynamically.
    form.addEventListener('input', update);
    form.addEventListener('change', update);

    // Exposed so code that sets a field's .value directly (strategies, add-step,
    // random-cents) can refresh the button without relying on a native event
    // that a programmatic value change doesn't dispatch on its own.
    form.bidwraithRevalidateSave = update;
    update();
});

(function () {
    var input = document.getElementById('item_id');
    var result = document.getElementById('itemLookupResult');
    var details = document.getElementById('auctionDetails');
    var targetMaxBidInput = document.getElementById('target_max_bid');
    var maxBidWarning = document.getElementById('maxBidWarning');
    var maxBidEstimate = document.getElementById('maxBidEstimate');
    var bidTabsSection = document.getElementById('bidTabsSection');
    var targetMaxBidError = document.getElementById('targetMaxBidError');
    if (!input || !result) {
        return;
    }

    var debounceTimer = null;
    var lastQueried = null;

    function hideDetails() {
        bidwraithCurrentPrice = null;
        bidwraithShippingCost = null;
        bidwraithItemCountry = null;
        bidwraithCurrency = null;
        if (details) {
            details.hidden = true;
        }
        if (maxBidWarning) {
            maxBidWarning.hidden = true;
        }
        if (maxBidEstimate) {
            maxBidEstimate.hidden = true;
        }
    }

    function renderResult(data) {
        if (!data.found) {
            result.className = 'item-lookup-result is-error';
            result.textContent = data.error || "Couldn't find that item.";
            result.hidden = false;
            hideDetails();
            return;
        }

        if (details) {
            details.hidden = false;
        }

        bidwraithCurrentPrice = (data.current_price !== null && data.current_price !== undefined)
            ? Number(data.current_price)
            : null;
        bidwraithShippingCost = (data.shipping_cost !== null && data.shipping_cost !== undefined)
            ? Number(data.shipping_cost)
            : null;
        bidwraithItemCountry = data.item_country || null;
        bidwraithCurrency = data.currency || null;
        updateMaxBidHelpers();

        result.className = 'item-lookup-result is-ok';
        result.innerHTML = '';

        if (data.image_url) {
            var img = document.createElement('img');
            img.src = data.image_url;
            img.alt = '';
            img.className = 'item-lookup-thumb';
            result.appendChild(img);
        }

        var resultDetails = document.createElement('div');
        resultDetails.className = 'item-lookup-details';

        var title = document.createElement('div');
        title.className = 'item-lookup-title';
        title.textContent = data.title || '(no title returned)';
        resultDetails.appendChild(title);

        var parts = [];
        if (data.current_price !== null && data.current_price !== undefined) {
            parts.push(data.currency + ' ' + Number(data.current_price).toFixed(2));
        }
        if (data.end_time) {
            var end = new Date(data.end_time);
            if (!isNaN(end.getTime())) {
                parts.push('Ends ' + end.toLocaleString());
            }
        }
        if (parts.length) {
            var meta = document.createElement('div');
            meta.className = 'item-lookup-meta';
            meta.textContent = parts.join(' · ');
            resultDetails.appendChild(meta);
        }

        result.appendChild(resultDetails);
        result.hidden = false;
    }

    function updateMaxBidHelpers() {
        var raw = targetMaxBidInput ? targetMaxBidInput.value.trim() : '';
        var value = parseFloat(raw);
        // checkValidity() also catches what parseFloat alone wouldn't (e.g. a
        // negative number, which fails the field's min="0.01") — the field
        // needs to actually pass its own validation, not just parse as a number.
        var basicValid = raw !== '' && !isNaN(value) && value > 0
            && (!targetMaxBidInput.checkValidity || targetMaxBidInput.checkValidity());

        var priceKnown = typeof bidwraithCurrentPrice === 'number' && !isNaN(bidwraithCurrentPrice);
        if (targetMaxBidInput) {
            targetMaxBidInput.placeholder = priceKnown
                ? 'Max bid should be more than ' + (bidwraithCurrency ? bidwraithCurrency + ' ' : '') + bidwraithCurrentPrice.toFixed(2)
                : 'e.g. 75.00';
        }
        var belowCurrentPrice = basicValid && priceKnown && value < bidwraithCurrentPrice;
        // A max bid below the current price can never win, so it doesn't count
        // as "valid" for revealing the bid tabs either — same signal as the
        // warning message below, just also gating what appears beneath it.
        var maxBidValid = basicValid && !belowCurrentPrice;

        if (bidTabsSection) {
            bidTabsSection.hidden = !maxBidValid;
        }
        // Not reusing setFieldError() here: it looks up the nearest
        // [data-field-error] via parentNode, but target_max_bid's parent also
        // contains every Steps row's own error slot — it would write into
        // whichever one happens to come first in the DOM instead of this field's.
        // Only flags the field itself for the basic-format problem; "below
        // current price" already gets its own clearer message via maxBidWarning.
        var showFieldError = raw !== '' && !basicValid;
        if (targetMaxBidInput) {
            targetMaxBidInput.classList.toggle('has-error', showFieldError);
        }
        if (targetMaxBidError) {
            targetMaxBidError.textContent = showFieldError ? 'Enter a max bid greater than 0.' : '';
        }

        if (!targetMaxBidInput || !maxBidWarning || !maxBidEstimate) {
            return;
        }

        if (!basicValid) {
            maxBidWarning.hidden = true;
            maxBidEstimate.hidden = true;
            return;
        }

        if (belowCurrentPrice) {
            maxBidWarning.textContent = 'Your max bid is below the current price ('
                + (bidwraithCurrency ? bidwraithCurrency + ' ' : '') + bidwraithCurrentPrice.toFixed(2)
                + ') — you are unlikely to win at this level.';
            maxBidWarning.hidden = false;
        } else {
            maxBidWarning.hidden = true;
        }

        if (!priceKnown) {
            maxBidEstimate.hidden = true;
            return;
        }

        var estimate = estimateLandedCost(value, bidwraithShippingCost, bidwraithItemCountry, bidwraithHomeCountry);
        var currencyPrefix = bidwraithCurrency ? bidwraithCurrency + ' ' : '';
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

    if (targetMaxBidInput) {
        targetMaxBidInput.addEventListener('input', updateMaxBidHelpers);
    }

    function lookup() {
        var raw = input.value.trim();
        if (raw === '' || raw === lastQueried) {
            return;
        }
        lastQueried = raw;

        fetch('item_lookup.php?item_id=' + encodeURIComponent(raw), { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (input.value.trim() === raw) {
                    renderResult(data);
                }
            })
            .catch(function () {
                if (input.value.trim() === raw) {
                    result.className = 'item-lookup-result is-error';
                    result.textContent = "Couldn't check that item right now.";
                    result.hidden = false;
                }
            });
    }

    input.addEventListener('input', function () {
        result.hidden = true;
        lastQueried = null;
        clearTimeout(debounceTimer);
        if (input.value.trim() === '') {
            hideDetails();
            return;
        }
        debounceTimer = setTimeout(lookup, 700);
    });

    input.addEventListener('blur', function () {
        clearTimeout(debounceTimer);
        lookup();
    });

    if (input.value.trim() !== '') {
        lookup();
    }
})();

document.querySelectorAll('form').forEach(function (form) {
    var stepRows = form.querySelectorAll('.bid-step-row');
    if (!stepRows.length) {
        return;
    }

    form.addEventListener('submit', function (e) {
        var steps = [];
        var error = null;

        stepRows.forEach(function (row) {
            var secondsInput = row.querySelector('input[name="step_seconds[]"]');
            var maxBidInput = row.querySelector('input[name="step_max_bid[]"]');
            var secondsRaw = secondsInput ? secondsInput.value.trim() : '';
            var maxBidRaw = maxBidInput ? maxBidInput.value.trim() : '';
            if (secondsRaw === '' && maxBidRaw === '') {
                return;
            }

            var seconds = parseInt(secondsRaw, 10);
            var maxBid = parseFloat(maxBidRaw);

            if (!error && (isNaN(seconds) || seconds < 1 || seconds > 60)) {
                error = 'Seconds before end must be between 1 and 60.';
            }
            if (!error && (isNaN(maxBid) || maxBid <= 0)) {
                error = 'Each max bid must be greater than 0.';
            }

            steps.push({ seconds: seconds, maxBid: maxBid });
        });

        if (!error) {
            var seen = {};
            for (var i = 0; i < steps.length; i++) {
                if (seen[steps[i].seconds]) {
                    error = 'Each bid must use a different number of seconds before the end.';
                    break;
                }
                seen[steps[i].seconds] = true;
            }
        }

        if (!error) {
            var sorted = steps.slice().sort(function (a, b) { return b.seconds - a.seconds; });
            for (var j = 1; j < sorted.length; j++) {
                if (sorted[j].maxBid < sorted[j - 1].maxBid) {
                    error = 'The bid ' + sorted[j].seconds + 's before the end can\'t be smaller than the bid ' +
                        sorted[j - 1].seconds + 's before the end — bids closer to the end must be equal or higher.';
                    break;
                }
            }
        }

        showBidStepError(form, error);
        if (error) {
            e.preventDefault();
        }
    });
});
