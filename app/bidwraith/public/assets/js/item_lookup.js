/**
 * The eBay item ID field on the add-auction form: looks the item up as it's
 * typed (via public/item_lookup.php), shows what was found, and keeps the rest
 * of the form in step with the item that's attached.
 */
(function (Bidwraith) {
    'use strict';

    var LOOKUP_DEBOUNCE_MS = 700;

    var input = document.getElementById('item_id');
    var result = document.getElementById('itemLookupResult');
    var itemIdField = document.getElementById('itemIdField');
    var details = document.getElementById('auctionDetails');
    if (!input || !result) {
        return;
    }

    var debounceTimer = null;
    var lastQueried = null;

    function hideDetails() {
        Bidwraith.resetItem();
        if (details) {
            details.hidden = true;
        }
        if (Bidwraith.hideMaxBidHelpers) {
            Bidwraith.hideMaxBidHelpers();
        }
    }

    /**
     * Blanks every bid field (Max bid, all Steps rows, the Scheduled Bid tab,
     * the "I want the item anyway" tab) back to its fresh, empty-form state.
     * Removing the item makes all of that stale — it was priced/timed against
     * an auction that's no longer attached to the form.
     */
    function resetBidFields(form) {
        if (!form) {
            return;
        }

        if (Bidwraith.clearMaxBidField) {
            Bidwraith.clearMaxBidField();
        }
        Bidwraith.resetStepRows(form);
        Bidwraith.showBidStepError(form, null);
        Bidwraith.resetScheduledFields(form);
        Bidwraith.resetAnywayFields(form);

        // The manual end time, shown only when the lookup couldn't find one.
        ['end_time', 'end_time_utc'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.value = '';
            }
        });

        Bidwraith.switchBidTab(form, 'strategies');
    }

    function removeItem() {
        input.value = '';
        lastQueried = null;
        result.hidden = true;
        result.innerHTML = '';
        hideDetails();
        if (itemIdField) {
            itemIdField.hidden = false;
        }
        input.focus();
        var form = input.closest('form');
        resetBidFields(form);
        Bidwraith.revalidateSave(form);
    }

    function addRemoveButton() {
        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'item-lookup-remove';
        removeBtn.setAttribute('aria-label', 'Remove item');
        removeBtn.innerHTML = '&times;';
        removeBtn.addEventListener('click', removeItem);
        result.appendChild(removeBtn);
    }

    function renderResult(data) {
        if (itemIdField) {
            itemIdField.hidden = true;
        }

        if (!data.found) {
            result.className = 'item-lookup-result is-error';
            result.textContent = data.error || "Couldn't find that item.";
            addRemoveButton();
            result.hidden = false;
            hideDetails();
            return;
        }

        if (details) {
            details.hidden = false;
        }

        Bidwraith.item.currentPrice = (data.current_price !== null && data.current_price !== undefined)
            ? Number(data.current_price)
            : null;
        Bidwraith.item.shippingCost = (data.shipping_cost !== null && data.shipping_cost !== undefined)
            ? Number(data.shipping_cost)
            : null;
        Bidwraith.item.country = data.item_country || null;
        Bidwraith.item.currency = data.currency || null;
        if (Bidwraith.updateMaxBidHelpers) {
            Bidwraith.updateMaxBidHelpers();
        }

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
        addRemoveButton();
        result.hidden = false;
    }

    /** Ignores a response whose item ID no longer matches what's in the field. */
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
        debounceTimer = setTimeout(lookup, LOOKUP_DEBOUNCE_MS);
    });

    input.addEventListener('blur', function () {
        clearTimeout(debounceTimer);
        lookup();
    });

    // A form that came back from the server with an item ID already in it (a
    // failed submit, or ?item_id= in the URL) still needs its details filled in.
    if (input.value.trim() !== '') {
        lookup();
    }
})(window.Bidwraith);
