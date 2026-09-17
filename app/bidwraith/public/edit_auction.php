<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();

$auctionId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM watched_auctions WHERE id = ? AND user_id = ?');
$stmt->execute([$auctionId, $user['id']]);
$auction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$auction) {
    set_flash('error', 'Auction not found.');
    redirect('dashboard.php');
}

$hasEnded = $auction['end_time'] && strtotime($auction['end_time']) <= time();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if ($hasEnded) {
        set_flash('error', 'This auction has already ended — bids can no longer be changed.');
        redirect('dashboard.php');
    }

    $existingStmt = db()->prepare('SELECT * FROM bid_steps WHERE watched_auction_id = ?');
    $existingStmt->execute([$auctionId]);
    $existingById = [];
    foreach ($existingStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $existingById[(int) $row['id']] = $row;
    }

    $stepIdInput = $_POST['step_id'] ?? [];
    $secondsInput = $_POST['step_seconds'] ?? [];
    $maxBidInput = $_POST['step_max_bid'] ?? [];

    $toDelete = [];
    $toUpdate = [];
    $toInsert = [];
    $finalSecondsBefore = [];
    $finalSteps = [];

    // Steps that already fired are fixed regardless of what was submitted for them.
    foreach ($existingById as $id => $row) {
        if ($row['status'] !== 'pending') {
            $finalSecondsBefore[] = (int) $row['seconds_before'];
            $finalSteps[] = [
                'seconds_before' => (int) $row['seconds_before'],
                'max_bid' => $row['max_bid'] !== null ? (float) $row['max_bid'] : null,
                'bid_mode' => $row['bid_mode'] ?? 'fixed',
            ];
        }
    }

    // The scheduled bid (seconds_before > 60) is edited on its own tab rather than as
    // a Steps row, so its still-pending row (if any) is found here and kept out of
    // the Steps loop below entirely. A fired one was already added to $finalSteps
    // above and is never reprocessed from the form — its fields render readonly with
    // the mode radios disabled, so the form wouldn't even submit a $_POST value for them.
    $existingScheduled = null;
    $hasFiredScheduled = false;
    foreach ($existingById as $row) {
        if ((int) $row['seconds_before'] >= SCHEDULED_BID_MIN_SECONDS_BEFORE) {
            if ($row['status'] === 'pending') {
                $existingScheduled = $row;
            } else {
                $hasFiredScheduled = true;
            }
            break;
        }
    }

    // Same idea for "I want the item anyway" — told apart from a Steps/Scheduled row
    // by bid_mode rather than by seconds_before, since it shares the Steps tab's
    // 1-60s range.
    $existingAnyway = null;
    $hasFiredAnyway = false;
    foreach ($existingById as $row) {
        if (($row['bid_mode'] ?? 'fixed') === 'anyway') {
            if ($row['status'] === 'pending') {
                $existingAnyway = $row;
            } else {
                $hasFiredAnyway = true;
            }
            break;
        }
    }

    foreach ($secondsInput as $i => $secondsRaw) {
        $stepId = trim((string) ($stepIdInput[$i] ?? ''));
        $secondsRaw = trim((string) $secondsRaw);
        $maxBidRaw = trim((string) ($maxBidInput[$i] ?? ''));
        $existing = $stepId !== '' ? ($existingById[(int) $stepId] ?? null) : null;

        // Ignore edits to a step that already fired — the form marks these readonly,
        // this is just a defensive backstop against a tampered request.
        if ($existing && $existing['status'] !== 'pending') {
            continue;
        }

        if ($secondsRaw === '' && $maxBidRaw === '') {
            if ($existing) {
                $toDelete[] = (int) $stepId;
            }
            continue;
        }

        if (!is_numeric($secondsRaw) || (int) $secondsRaw < 1 || (int) $secondsRaw > 60) {
            $error = 'Seconds before end must be between 1 and 60.';
            break;
        }
        if (!is_numeric($maxBidRaw) || (float) $maxBidRaw <= 0) {
            $error = 'Each max bid must be greater than 0.';
            break;
        }

        $seconds = (int) $secondsRaw;
        $maxBid = (float) $maxBidRaw;

        if (in_array($seconds, $finalSecondsBefore, true)) {
            $error = 'Each bid must use a different number of seconds before the end.';
            break;
        }
        $finalSecondsBefore[] = $seconds;
        $finalSteps[] = ['seconds_before' => $seconds, 'max_bid' => $maxBid];

        if ($existing) {
            $toUpdate[] = ['id' => (int) $stepId, 'seconds_before' => $seconds, 'max_bid' => $maxBid];
        } else {
            $toInsert[] = ['seconds_before' => $seconds, 'max_bid' => $maxBid];
        }
    }

    if (!$error && !$hasFiredScheduled) {
        $scheduledResult = resolve_scheduled_bid_input($_POST, $auction['end_time']);
        if (isset($scheduledResult['error'])) {
            $error = $scheduledResult['error'];
        } elseif (isset($scheduledResult['seconds_before'])) {
            if (in_array($scheduledResult['seconds_before'], $finalSecondsBefore, true)) {
                $error = 'Each bid must use a different number of seconds before the end.';
            } else {
                $finalSecondsBefore[] = $scheduledResult['seconds_before'];
                $finalSteps[] = $scheduledResult;
                if ($existingScheduled) {
                    $toUpdate[] = ['id' => (int) $existingScheduled['id'], 'seconds_before' => $scheduledResult['seconds_before'], 'max_bid' => $scheduledResult['max_bid']];
                } else {
                    $toInsert[] = ['seconds_before' => $scheduledResult['seconds_before'], 'max_bid' => $scheduledResult['max_bid']];
                }
            }
        } elseif ($existingScheduled) {
            // Scheduled tab left blank: clear a still-pending scheduled bid, same as
            // clearing a Steps row removes it.
            $toDelete[] = (int) $existingScheduled['id'];
        }
    }

    if (!$error && !$hasFiredAnyway) {
        $anywayResult = resolve_anyway_bid_input($_POST);
        if (isset($anywayResult['error'])) {
            $error = $anywayResult['error'];
        } elseif (isset($anywayResult['seconds_before'])) {
            if (in_array($anywayResult['seconds_before'], $finalSecondsBefore, true)) {
                $error = 'Each bid must use a different number of seconds before the end.';
            } else {
                $finalSecondsBefore[] = $anywayResult['seconds_before'];
                $finalSteps[] = $anywayResult;
                if ($existingAnyway) {
                    $toUpdate[] = ['id' => (int) $existingAnyway['id']] + $anywayResult;
                } else {
                    $toInsert[] = $anywayResult;
                }
            }
        } elseif ($existingAnyway) {
            // Anyway tab left blank: clear a still-pending one, same as clearing a
            // Steps row removes it.
            $toDelete[] = (int) $existingAnyway['id'];
        }
    }

    if (!$error && count($finalSecondsBefore) > 5) {
        $error = 'You can have at most 5 bids per auction.';
    }

    if (!$error) {
        $error = validate_bid_step_ordering($finalSteps);
    }

    if (!$error) {
        db()->beginTransaction();
        foreach ($toDelete as $id) {
            db()->prepare('DELETE FROM bid_steps WHERE id = ? AND watched_auction_id = ?')->execute([$id, $auctionId]);
        }
        foreach ($toUpdate as $u) {
            db()->prepare('UPDATE bid_steps SET seconds_before = ?, max_bid = ?, bid_mode = ?, increment_type = ?, increment_amount = ? WHERE id = ? AND watched_auction_id = ?')
                ->execute([
                    $u['seconds_before'], $u['max_bid'] ?? null, $u['bid_mode'] ?? 'fixed',
                    $u['increment_type'] ?? null, $u['increment_amount'] ?? null, $u['id'], $auctionId,
                ]);
        }
        foreach ($toInsert as $ins) {
            db()->prepare('INSERT INTO bid_steps (watched_auction_id, seconds_before, max_bid, bid_mode, increment_type, increment_amount) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([
                    $auctionId, $ins['seconds_before'], $ins['max_bid'] ?? null,
                    $ins['bid_mode'] ?? 'fixed', $ins['increment_type'] ?? null, $ins['increment_amount'] ?? null,
                ]);
        }
        db()->commit();

        set_flash('success', 'Bids updated.');
        redirect('dashboard.php');
    }
}

$stepsStmt = db()->prepare('SELECT * FROM bid_steps WHERE watched_auction_id = ? ORDER BY seconds_before DESC');
$stepsStmt->execute([$auctionId]);
$dbSteps = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);

// The scheduled bid (seconds_before > 60) and the "anyway" bid (told apart by
// bid_mode, since it shares the Steps tab's 1-60s range) each live on their own
// tab, so they're split out of the Steps rows here rather than shown as one of them.
$regularDbSteps = array_values(array_filter(
    $dbSteps,
    fn ($s) => (int) $s['seconds_before'] < SCHEDULED_BID_MIN_SECONDS_BEFORE && ($s['bid_mode'] ?? 'fixed') !== 'anyway'
));
$scheduledDbStep = null;
foreach ($dbSteps as $s) {
    if ((int) $s['seconds_before'] >= SCHEDULED_BID_MIN_SECONDS_BEFORE) {
        $scheduledDbStep = $s;
        break;
    }
}
$anywayDbStep = null;
foreach ($dbSteps as $s) {
    if (($s['bid_mode'] ?? 'fixed') === 'anyway') {
        $anywayDbStep = $s;
        break;
    }
}

$viewSteps = array_map(fn ($s) => [
    'id' => $s['id'],
    'seconds_before' => $s['seconds_before'],
    'max_bid' => $s['max_bid'],
    'readonly' => $hasEnded || $s['status'] !== 'pending',
    'status' => $s['status'],
], $regularDbSteps);

if ($scheduledDbStep) {
    $scheduledSeconds = (int) $scheduledDbStep['seconds_before'];
    // Reconstructed as an hours/minutes offset regardless of how it was originally
    // entered (offset or exact date) — bid_steps only stores the resulting
    // seconds-before-end number, not which mode produced it.
    $scheduledView = [
        'mode' => 'offset',
        'hours' => (string) intdiv($scheduledSeconds, 3600),
        'minutes' => (string) intdiv($scheduledSeconds % 3600, 60),
        'date_local' => '',
        'max_bid' => $scheduledDbStep['max_bid'],
        'readonly' => $hasEnded || $scheduledDbStep['status'] !== 'pending',
        'status' => $scheduledDbStep['status'],
        'exists' => true,
    ];
} else {
    $scheduledView = [
        'mode' => '', 'hours' => '', 'minutes' => '', 'date_local' => '', 'max_bid' => '',
        'readonly' => $hasEnded, 'status' => null, 'exists' => false,
    ];
}

if ($anywayDbStep) {
    $anywayView = [
        'seconds_before' => $anywayDbStep['seconds_before'],
        'increment_type' => $anywayDbStep['increment_type'] ?? '',
        'increment_amount' => $anywayDbStep['increment_amount'],
        'max_bid' => $anywayDbStep['max_bid'],
        'readonly' => $hasEnded || $anywayDbStep['status'] !== 'pending',
        'status' => $anywayDbStep['status'],
        'exists' => true,
    ];
} else {
    $anywayView = [
        'seconds_before' => '', 'increment_type' => '', 'increment_amount' => '', 'max_bid' => '',
        'readonly' => $hasEnded, 'status' => null, 'exists' => false,
    ];
}

$pageTitle = 'Edit bids';
$currency = ebay_config()['currency'];
require __DIR__ . '/../includes/layout_top.php';
?>
<h1>Edit bids — <?= htmlspecialchars($auction['title'] ?? $auction['item_id']) ?></h1>
<p class="hint">Item <?= htmlspecialchars($auction['item_id']) ?> · Ends <?= $auction['end_time'] !== null ? local_time((int) strtotime($auction['end_time']), $auction['end_time']) : 'unknown' ?></p>

<?php if ($error): ?><div class="flash flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if ($hasEnded): ?>
    <div class="flash flash-error">This auction has already ended — bids can no longer be changed.</div>
<?php endif; ?>

<form class="stacked" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $auction['id'] ?>">
    <label>Bids (up to 5, timed before the auction ends)</label>
    <div class="hint">Bids that already fired are shown for reference and can't be changed. Clear a still-pending bid's fields to remove it.</div>

    <div class="bid-tabs" role="tablist">
        <button type="button" class="bid-tab is-active" data-bid-tab="custom" role="tab" aria-selected="true">Steps</button>
        <button type="button" class="bid-tab" data-bid-tab="scheduled" role="tab" aria-selected="false">Scheduled Bid</button>
        <button type="button" class="bid-tab" data-bid-tab="anyway" role="tab" aria-selected="false">I Want The Item Anyway</button>
    </div>

    <div class="bid-tab-panel" data-bid-panel="custom">
        <?php render_bid_step_rows($viewSteps, $currency, !$hasEnded); ?>
    </div>

    <div class="bid-tab-panel" data-bid-panel="scheduled" hidden>
        <div class="hint">A single bid, timed separately from the Steps ladder above — either hours/minutes before the auction ends, or at an exact date and time.</div>
        <?php render_scheduled_bid_panel($scheduledView, $currency); ?>
    </div>

    <div class="bid-tab-panel" data-bid-panel="anyway" hidden>
        <div class="hint">
            Bids whatever it takes to win, separate from the Steps ladder above. Instead of a fixed amount decided now,
            it adds your value or percentage on top of the item's price right when it fires.
        </div>
        <?php render_anyway_bid_panel($anywayView, $currency); ?>
    </div>

    <?php if (!$hasEnded): ?>
        <button type="submit">Save</button>
    <?php endif; ?>
</form>
<p><a href="dashboard.php">&larr; Back to auction list</a></p>
<script src="<?= asset_url('assets/js/app.js') ?>"></script>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
