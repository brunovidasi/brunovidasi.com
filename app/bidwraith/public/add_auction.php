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
    } elseif (empty($steps)) {
        $error = 'Add at least one bid: how many seconds before the end, and the max amount.';
    } elseif (count($steps) > 5) {
        $error = 'You can have at most 5 bids per auction.';
    } else {
        $secondsSeen = [];
        foreach ($steps as $s) {
            if ($s['seconds_before'] < 1 || $s['seconds_before'] > 60) {
                $error = 'Seconds before end must be between 1 and 60.';
                break;
            }
            if ($s['max_bid'] <= 0) {
                $error = 'Each max bid must be greater than 0.';
                break;
            }
            if (in_array($s['seconds_before'], $secondsSeen, true)) {
                $error = 'Each bid must use a different number of seconds before the end.';
                break;
            }
            $secondsSeen[] = $s['seconds_before'];
        }
        if (!$error) {
            $error = validate_bid_step_ordering($steps);
        }
    }

    if (!$error) {
        $title = null;
        // end_time_utc is the browser's own reading of the local end_time field, converted
        // to an instant using the visitor's actual timezone (see app.js) — that's what
        // decides when the sniper fires, so it takes priority over the raw field, which
        // would otherwise be misread as being in the server's configured timezone.
        if ($manualEndTimeUtc !== '' && ctype_digit($manualEndTimeUtc)) {
            $endTime = date('Y-m-d H:i:s', (int) $manualEndTimeUtc);
        } else {
            $endTime = $manualEndTime !== '' ? date('Y-m-d H:i:s', strtotime($manualEndTime)) : null;
        }
        $lookup = null;

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

        if (!$endTime) {
            $error = "Couldn't find that item automatically (this is expected in the eBay Sandbox for real item IDs). Enter the auction end time manually below and save again.";
            $lookupFailed = true;
        } else {
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

            $stepStmt = db()->prepare('INSERT INTO bid_steps (watched_auction_id, seconds_before, max_bid) VALUES (?, ?, ?)');
            foreach ($steps as $s) {
                $stepStmt->execute([$auctionId, $s['seconds_before'], $s['max_bid']]);
            }
            db()->commit();

            set_flash('success', 'Auction added to your auction list.');
            redirect('dashboard.php');
        }
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

$pageTitle = 'Add auction';
$currency = ebay_config()['currency'];
require __DIR__ . '/../includes/layout_top.php';
?>
<h1>Add an auction</h1>
<?php if ($error): ?><div class="flash flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form class="stacked" method="post">
    <?= csrf_field() ?>
    <label for="item_id">eBay item ID or listing URL</label>
    <input type="text" id="item_id" name="item_id" required placeholder="e.g. 123456789012 or listing url" value="<?= htmlspecialchars($_POST['item_id'] ?? $_GET['item_id'] ?? '') ?>" autocomplete="off">
    <div class="hint">Paste the item ID or the full listing URL — e.g. 123456789012.</div>
    <div class="item-lookup-result" id="itemLookupResult" hidden></div>

    <label>Bids (up to 5, timed before the auction ends)</label>
    <div class="hint">
        E.g. <?= htmlspecialchars($currency) ?> 50 at 10s before, <?= htmlspecialchars($currency) ?> 60 at 3s before,
        <?= htmlspecialchars($currency) ?> 70 at 1s before (the last second) — each one only fires if you haven't
        already won at an earlier, lower bid.
    </div>

    <?php $initialTab = $repopulateSteps ? 'custom' : 'strategies'; ?>
    <div class="bid-tabs" role="tablist">
        <button type="button" class="bid-tab<?= $initialTab === 'strategies' ? ' is-active' : '' ?>" data-bid-tab="strategies" role="tab" aria-selected="<?= $initialTab === 'strategies' ? 'true' : 'false' ?>">Strategies</button>
        <button type="button" class="bid-tab<?= $initialTab === 'custom' ? ' is-active' : '' ?>" data-bid-tab="custom" role="tab" aria-selected="<?= $initialTab === 'custom' ? 'true' : 'false' ?>">Steps</button>
    </div>

    <div class="bid-tab-panel" data-bid-panel="strategies"<?= $initialTab === 'strategies' ? '' : ' hidden' ?>>
        <div class="strategy-cards">
            <button type="button" class="strategy-card" data-strategy="[2]">
                <span class="strategy-name">Last Second Strategy</span>
                <span class="strategy-desc">1 bid &mdash; 2s before the end</span>
            </button>
            <button type="button" class="strategy-card" data-strategy="[5,3,2]">
                <span class="strategy-name">3 Seconds Strategy</span>
                <span class="strategy-desc">3 bids &mdash; 5s, 3s, and 2s before the end</span>
            </button>
            <button type="button" class="strategy-card" data-strategy="[1]">
                <span class="strategy-name">High Risk Strategy</span>
                <span class="strategy-desc">1 bid &mdash; 1s before the end</span>
                <span class="strategy-warning">&#9888; If eBay responds slowly, there may not be enough time left for the bid to register &mdash; you could lose the auction.</span>
            </button>
            <button type="button" class="strategy-card strategy-card-danger" data-strategy="[2]" data-strategy-confirm="eBay will automatically keep raising your bid above whoever else bids &mdash; all the way up to the maximum you set, no matter how far past the item's real value that goes. It will never bid more than that number, but it WILL go that high if that's what it takes to win. Only continue if you truly want this item at any price up to your maximum.">
                <span class="strategy-name">I Want The Item Anyway</span>
                <span class="strategy-desc">1 bid &mdash; 2s before the end, at whatever maximum you set</span>
                <span class="strategy-warning">&#9888; Dangerous: this sets your ceiling, not a fixed price. eBay's proxy bidding keeps outbidding everyone else automatically, all the way up to the maximum you enter &mdash; only set a number you're genuinely willing to pay.</span>
            </button>
        </div>
        <div class="hint">Pick a strategy to prefill the timing, then set your own max bid amount(s) on the Steps tab.</div>
    </div>

    <div class="bid-tab-panel" data-bid-panel="custom"<?= $initialTab === 'custom' ? '' : ' hidden' ?>>
        <div class="flash flash-error anyway-mode-notice" id="anywayModeNotice" hidden>
            You're set to bid whatever it takes, no matter the price. The amount below is your absolute maximum &mdash;
            eBay will bid up to it automatically, but never more.
        </div>
        <?php render_bid_step_rows($repopulateSteps, $currency); ?>
    </div>

    <?php if ($lookupFailed): ?>
        <label for="end_time">Auction end time (since it couldn't be looked up automatically)</label>
        <input type="datetime-local" id="end_time" name="end_time" required>
        <input type="hidden" id="end_time_utc" name="end_time_utc">
        <div class="hint">In your own local time zone — the page converts it automatically.</div>
    <?php endif; ?>

    <button type="submit">Save</button>
</form>
<script src="assets/js/app.js"></script>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
