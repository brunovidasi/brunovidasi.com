<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
$error = null;
$lookupFailed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $itemId = extract_ebay_item_id($_POST['item_id'] ?? '');
    $manualEndTime = trim($_POST['end_time'] ?? '');
    $manualEndTimeUtc = trim($_POST['end_time_utc'] ?? '');

    $steps = [];
    $secondsInput = $_POST['step_seconds'] ?? [];
    $maxBidInput = $_POST['step_max_bid'] ?? [];
    foreach ($secondsInput as $i => $secondsRaw) {
        $maxBidRaw = $maxBidInput[$i] ?? '';
        if (trim((string) $secondsRaw) === '' || trim((string) $maxBidRaw) === '') {
            continue;
        }
        $steps[] = ['seconds_before' => (int) $secondsRaw, 'max_bid' => (float) $maxBidRaw];
    }

    if ($itemId === '') {
        $error = 'Enter an eBay item ID.';
    } else {
        foreach ($steps as $s) {
            if ($s['seconds_before'] < 1 || $s['seconds_before'] > 60) {
                $error = 'Seconds before end must be between 1 and 60.';
                break;
            }
            if ($s['max_bid'] <= 0) {
                $error = 'Each max bid must be greater than 0.';
                break;
            }
        }
    }

    $title = null;
    $endTime = null;
    $lookup = null;

    // Resolved before the scheduled bid is validated: an exact date (or even an
    // hours/minutes offset) can only become a seconds-before-end number once the
    // auction's real end time is known.
    if (!$error) {
        // end_time_utc is the browser's own reading of the local end_time field, converted
        // to an instant using the visitor's actual timezone (see assets/js/time.js) — that's what
        // decides when the sniper fires, so it takes priority over the raw field, which
        // would otherwise be misread as being in the server's configured timezone.
        if ($manualEndTimeUtc !== '' && ctype_digit($manualEndTimeUtc)) {
            $endTime = date('Y-m-d H:i:s', (int) $manualEndTimeUtc);
        } else {
            $endTime = $manualEndTime !== '' ? date('Y-m-d H:i:s', strtotime($manualEndTime)) : null;
        }

        try {
            $client = new EbayClient();
            $lookup = $client->getItemByLegacyId($itemId);
            if ($lookup && !empty($lookup['end_time'])) {
                $title = $lookup['title'];
                $endTime = date('Y-m-d H:i:s', strtotime($lookup['end_time']));
            }
        } catch (Throwable $e) {
            // Lookup is best-effort; fall through to manual end time if given.
        }

        // Without an end time nothing past this point can be resolved (a scheduled
        // bid's date/offset has nothing to be "before"), so this takes priority over
        // validating the bids themselves — same as before Scheduled Bid existed.
        if (!$endTime) {
            $error = "Couldn't find that item automatically (this is expected in the eBay Sandbox for real item IDs). Enter the auction end time manually below and save again.";
            $lookupFailed = true;
        }
    }

    if (!$error) {
        $scheduled = resolve_scheduled_bid_input($_POST, $endTime);
        if (isset($scheduled['error'])) {
            $error = $scheduled['error'];
        } elseif (isset($scheduled['seconds_before'])) {
            $steps[] = $scheduled;
        }
    }

    if (!$error) {
        $anyway = resolve_anyway_bid_input($_POST);
        if (isset($anyway['error'])) {
            $error = $anyway['error'];
        } elseif (isset($anyway['seconds_before'])) {
            $steps[] = $anyway;
        }
    }

    if (!$error && empty($steps)) {
        $error = 'Add at least one bid: a Steps bid (Steps tab), a Scheduled bid (Scheduled Bid tab), or "I want the item anyway".';
    } elseif (!$error && count($steps) > 5) {
        $error = 'You can have at most 5 bids per auction.';
    }

    if (!$error) {
        $secondsSeen = [];
        foreach ($steps as $s) {
            if (in_array($s['seconds_before'], $secondsSeen, true)) {
                $error = 'Each bid must use a different number of seconds before the end.';
                break;
            }
            $secondsSeen[] = $s['seconds_before'];
        }
    }
    if (!$error) {
        $error = validate_bid_step_ordering($steps);
    }

    if (!$error) {
        db()->beginTransaction();
        $stmt = db()->prepare('
            INSERT INTO watched_auctions (user_id, item_id, title, end_time, current_price, shipping_cost, item_country, image_url, price_checked_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\'))
        ');
        $stmt->execute([
            $user['id'], $itemId, $title, $endTime,
            $lookup['current_price'] ?? null, $lookup['shipping_cost'] ?? null, $lookup['item_country'] ?? null,
            $lookup['image_url'] ?? null,
        ]);
        $auctionId = (int) db()->lastInsertId();

        $stepStmt = db()->prepare('INSERT INTO bid_steps (watched_auction_id, seconds_before, max_bid, bid_mode, increment_type, increment_amount) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($steps as $s) {
            $stepStmt->execute([
                $auctionId, $s['seconds_before'], $s['max_bid'] ?? null,
                $s['bid_mode'] ?? 'fixed', $s['increment_type'] ?? null, $s['increment_amount'] ?? null,
            ]);
        }
        db()->commit();

        set_flash('success', 'Auction added to your auction list.');
        redirect('dashboard.php');
    }
}

$repopulateSteps = [];
foreach (($_POST['step_seconds'] ?? []) as $i => $secondsRaw) {
    $maxBidRaw = ($_POST['step_max_bid'] ?? [])[$i] ?? '';
    if ($secondsRaw === '' && $maxBidRaw === '') {
        continue;
    }
    $repopulateSteps[] = ['id' => '', 'seconds_before' => $secondsRaw, 'max_bid' => $maxBidRaw, 'readonly' => false, 'status' => null];
}

$scheduledView = [
    'mode' => $_POST['scheduled_mode'] ?? '',
    'hours' => $_POST['scheduled_hours'] ?? '',
    'minutes' => $_POST['scheduled_minutes'] ?? '',
    'date_local' => $_POST['scheduled_date'] ?? '',
    'max_bid' => $_POST['scheduled_max_bid'] ?? '',
    'readonly' => false,
    'status' => null,
    'exists' => false,
];
$scheduledFilled = $scheduledView['hours'] !== '' || $scheduledView['minutes'] !== ''
    || $scheduledView['date_local'] !== '' || $scheduledView['max_bid'] !== '';

$anywayView = [
    'seconds_before' => $_POST['anyway_seconds_before'] ?? '',
    'increment_type' => $_POST['anyway_increment_type'] ?? '',
    'increment_amount' => $_POST['anyway_increment_amount'] ?? '',
    'max_bid' => $_POST['anyway_max_bid'] ?? '',
    'readonly' => false,
    'status' => null,
    'exists' => false,
];
$anywayFilled = $anywayView['increment_amount'] !== '' || $anywayView['max_bid'] !== '' || $anywayView['seconds_before'] !== '';

$pageTitle = 'Add auction';
$currency = user_currency($user);
$homeCountry = marketplace_country_code(ebay_config()['marketplace_id']);
require __DIR__ . '/../includes/layout_top.php';
?>
<h1>Add an auction</h1>
<?php if ($error): ?><div class="flash flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form class="stacked" method="post" data-requires-bid>
    <?= csrf_field() ?>
    <div id="itemIdField">
        <label for="item_id">eBay item ID or listing URL</label>
        <input type="text" id="item_id" name="item_id" required placeholder="e.g. 123456789012 or listing url" value="<?= htmlspecialchars($_POST['item_id'] ?? $_GET['item_id'] ?? '') ?>" autocomplete="off">
        <div class="hint">Paste the item ID or the full listing URL — e.g. 123456789012.</div>
    </div>
    <div class="item-lookup-result" id="itemLookupResult" hidden></div>

    <?php
    // Once an item has been loaded (or the form is being redisplayed after a
    // POST, e.g. a validation error or a failed automatic lookup), the max
    // bid and bid tabs are already relevant and should not be hidden again.
    $showAuctionDetails = $_SERVER['REQUEST_METHOD'] === 'POST';
    ?>
    <div id="auctionDetails"<?= $showAuctionDetails ? '' : ' hidden' ?>>
    <div class="label-with-action">
        <label for="target_max_bid">Max bid</label>
        <button type="button" class="link-btn cents-btn" data-random-cents-target>Add random cents</button>
    </div>
    <input type="number" id="target_max_bid" name="target_max_bid" step="0.01" min="0.01"
           placeholder="e.g. 75.00"
           value="<?= htmlspecialchars($_POST['target_max_bid'] ?? '') ?>">
    <div class="hint">(Maximum you would bid for this item in <?= htmlspecialchars($currency) ?> - excluding shipping and fees)</div>
    <div class="field-error" id="targetMaxBidError"></div>
    
    <div class="max-bid-warning" id="maxBidWarning" hidden></div>
    <div class="max-bid-estimate" id="maxBidEstimate" hidden></div>

    <?php
    // The bid tabs stay hidden until there's a valid max bid to work from — the
    // Strategies tab in particular needs a real number to compute anything, and
    // a max bid already below the current price could never win with it anyway.
    // Mirrors the same check assets/js/max_bid.js runs live as the field is typed into. Once
    // a valid value is already present (redisplayed after a POST, or existing
    // bid data was already filled in), there's no reason to hide it again.
    $maxBidRaw = trim((string) ($_POST['target_max_bid'] ?? ''));
    $maxBidNumeric = ($maxBidRaw !== '' && is_numeric($maxBidRaw)) ? (float) $maxBidRaw : null;
    $lookedUpCurrentPrice = $lookup['current_price'] ?? null;
    $maxBidValid = $maxBidNumeric !== null && $maxBidNumeric > 0
        && ($lookedUpCurrentPrice === null || $maxBidNumeric >= $lookedUpCurrentPrice);
    $showBidTabsSection = $maxBidValid || $repopulateSteps || $scheduledFilled || $anywayFilled;
    ?>
    <div id="bidTabsSection"<?= $showBidTabsSection ? '' : ' hidden' ?>>
    <label>Bids (up to 5, timed before the auction ends)</label>
    <div class="hint">
        E.g. <?= htmlspecialchars($currency) ?> 50 at 10s before, <?= htmlspecialchars($currency) ?> 60 at 3s before,
        <?= htmlspecialchars($currency) ?> 70 at 1s before (the last second) — each one only fires if you haven't
        already won at an earlier, lower bid.
    </div>

    <?php
    $initialTab = 'strategies';
    if ($repopulateSteps) {
        $initialTab = 'custom';
    } elseif ($scheduledFilled) {
        $initialTab = 'scheduled';
    } elseif ($anywayFilled) {
        $initialTab = 'anyway';
    }
    ?>
    <div class="bid-tabs" role="tablist">
        <button type="button" class="bid-tab<?= $initialTab === 'strategies' ? ' is-active' : '' ?>" data-bid-tab="strategies" role="tab" aria-selected="<?= $initialTab === 'strategies' ? 'true' : 'false' ?>">Strategies</button>
        <button type="button" class="bid-tab<?= $initialTab === 'custom' ? ' is-active' : '' ?>" data-bid-tab="custom" role="tab" aria-selected="<?= $initialTab === 'custom' ? 'true' : 'false' ?>">Steps</button>
        <button type="button" class="bid-tab<?= $initialTab === 'scheduled' ? ' is-active' : '' ?>" data-bid-tab="scheduled" role="tab" aria-selected="<?= $initialTab === 'scheduled' ? 'true' : 'false' ?>">Scheduled Bid</button>
        <button type="button" class="bid-tab<?= $initialTab === 'anyway' ? ' is-active' : '' ?>" data-bid-tab="anyway" role="tab" aria-selected="<?= $initialTab === 'anyway' ? 'true' : 'false' ?>">I Want The Item Anyway</button>
    </div>

    <div class="bid-tab-panel" data-bid-panel="strategies"<?= $initialTab === 'strategies' ? '' : ' hidden' ?>>
        <div class="strategy-cards">
            <button type="button" class="strategy-card" data-strategy="[5,3,2]">
                <span class="strategy-name">3 Steps Strategy</span>
                <span class="strategy-desc">3 bids &mdash; 5s, 3s, and 2s before the end</span>
            </button>
            <button type="button" class="strategy-card" data-strategy="[2]">
                <span class="strategy-name">Last Second Strategy</span>
                <span class="strategy-desc">1 bid &mdash; 2s before the end</span>
            </button>
            <button type="button" class="strategy-card" data-strategy="[1]">
                <span class="strategy-name">High Risk Strategy</span>
                <span class="strategy-desc">1 bid &mdash; 1s before the end</span>
                <span class="strategy-warning">&#9888; If eBay responds slowly, there may not be enough time left for the bid to register &mdash; you could lose the auction.</span>
            </button>
            <button type="button" class="strategy-card strategy-card-danger" data-strategy-goto="anyway" data-strategy-confirm="eBay will automatically keep raising your bid above whoever else bids &mdash; all the way up to whatever the current price plus your amount comes to when it fires (or your max value, if you set one), no matter how far past the item's real value that goes. Only continue if you truly want this item at any price.">
                <span class="strategy-name">I Want The Item Anyway</span>
                <span class="strategy-desc"><?= ANYWAY_DEFAULT_SECONDS_BEFORE ?>s before the end, bids current price + your amount, up to an optional max</span>
                <span class="strategy-warning">&#9888; Dangerous: there's no fixed ceiling unless you set a max value. eBay's proxy bidding keeps outbidding everyone else automatically, all the way up to whatever the current price plus your amount comes to.</span>
            </button>
            <button type="button" class="strategy-card" data-strategy-goto="scheduled">
                <span class="strategy-name">Scheduled Bid</span>
                <span class="strategy-desc">A single bid hours/minutes before the end, or at an exact date &mdash; separate from the Steps ladder</span>
            </button>
        </div>
        <div class="hint">Pick a strategy to prefill the timing and, using your max bid above, the amount for each step — the final step gets your max bid, and any earlier steps are scaled up from the item's current price. Enter your max bid first for this to work; you can still fine-tune amounts on the Steps tab.</div>
    </div>

    <div class="bid-tab-panel" data-bid-panel="custom"<?= $initialTab === 'custom' ? '' : ' hidden' ?>>
        <?php render_bid_step_rows($repopulateSteps, $currency); ?>
        <button type="button" class="secondary" data-random-cents-all>Add random cents to all</button>
        <div class="hint">Bumps every step's max bid to a random amount over 51&cent; &mdash; a less common ending than a round number, which can be the difference between winning and losing a tie. Keeps each step at least as high as the one before it.</div>
    </div>

    <div class="bid-tab-panel" data-bid-panel="scheduled"<?= $initialTab === 'scheduled' ? '' : ' hidden' ?>>
        <div class="hint">A single bid, timed separately from the Steps ladder above — either hours/minutes before the auction ends, or at an exact date and time.</div>
        <?php render_scheduled_bid_panel($scheduledView, $currency); ?>
    </div>

    <div class="bid-tab-panel" data-bid-panel="anyway"<?= $initialTab === 'anyway' ? '' : ' hidden' ?>>
        <div class="hint">
            Bids whatever it takes to win, separate from the Steps ladder above. Instead of a fixed amount decided now,
            it adds your value or percentage on top of the item's price right when it fires.
        </div>
        <?php render_anyway_bid_panel($anywayView, $currency); ?>
    </div>
    </div>
    </div>

    <?php if ($lookupFailed): ?>
        <label for="end_time">Auction end time (since it couldn't be looked up automatically)</label>
        <input type="datetime-local" id="end_time" name="end_time" required>
        <input type="hidden" id="end_time_utc" name="end_time_utc">
        <div class="hint">In your own local time zone — the page converts it automatically.</div>
    <?php endif; ?>

    <button type="submit">Save</button>
</form>
<script>
    var bidwraithHomeCountry = <?= json_encode($homeCountry) ?>;
</script>
<?= app_scripts() ?>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
