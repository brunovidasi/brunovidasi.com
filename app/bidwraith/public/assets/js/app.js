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

function applyStrategy(card) {
    var form = card.closest('form');
    if (!form) {
        return;
    }
    var seconds = JSON.parse(card.dataset.strategy);
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
                maxBidInput.value = '';
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

    var notice = document.getElementById('anywayModeNotice');
    if (notice) {
        notice.hidden = !card.classList.contains('strategy-card-danger');
    }

    showBidStepError(form, null);
    clearAllFieldErrors(form);
    switchBidTab(form, 'custom');

    var firstMaxBid = form.querySelector('.bid-step-row:not([hidden]) input[name="step_max_bid[]"]');
    if (firstMaxBid) {
        firstMaxBid.focus();
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
