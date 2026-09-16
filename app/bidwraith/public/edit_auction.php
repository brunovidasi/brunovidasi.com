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
            $finalSteps[] = ['seconds_before' => (int) $row['seconds_before'], 'max_bid' => (float) $row['max_bid']];
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
            db()->prepare('UPDATE bid_steps SET seconds_before = ?, max_bid = ? WHERE id = ? AND watched_auction_id = ?')
                ->execute([$u['seconds_before'], $u['max_bid'], $u['id'], $auctionId]);
        }
        foreach ($toInsert as $ins) {
            db()->prepare('INSERT INTO bid_steps (watched_auction_id, seconds_before, max_bid) VALUES (?, ?, ?)')
                ->execute([$auctionId, $ins['seconds_before'], $ins['max_bid']]);
        }
        db()->commit();

        set_flash('success', 'Bids updated.');
        redirect('dashboard.php');
    }
}

$stepsStmt = db()->prepare('SELECT * FROM bid_steps WHERE watched_auction_id = ? ORDER BY seconds_before DESC');
$stepsStmt->execute([$auctionId]);
$dbSteps = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);

$viewSteps = array_map(fn ($s) => [
    'id' => $s['id'],
    'seconds_before' => $s['seconds_before'],
    'max_bid' => $s['max_bid'],
    'readonly' => $hasEnded || $s['status'] !== 'pending',
    'status' => $s['status'],
], $dbSteps);

$pageTitle = 'Edit bids';
$currency = ebay_config()['currency'];
require __DIR__ . '/../includes/layout_top.php';
?>
<h1>Edit bids — <?= htmlspecialchars($auction['title'] ?? $auction['item_id']) ?></h1>
<p class="hint">Item <?= htmlspecialchars($auction['item_id']) ?> · Ends <?= htmlspecialchars($auction['end_time'] ?? 'unknown') ?></p>

<?php if ($error): ?><div class="flash flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if ($hasEnded): ?>
    <div class="flash flash-error">This auction has already ended — bids can no longer be changed.</div>
<?php endif; ?>

<form class="stacked" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $auction['id'] ?>">
    <label>Bids (up to 5, timed before the auction ends)</label>
    <div class="hint">Bids that already fired are shown for reference and can't be changed. Clear a still-pending bid's fields to remove it.</div>
    <?php render_bid_step_rows($viewSteps, $currency, !$hasEnded); ?>

    <?php if (!$hasEnded): ?>
        <button type="submit">Save</button>
    <?php endif; ?>
</form>
<p><a href="dashboard.php">&larr; Back to auction list</a></p>
<script src="assets/js/app.js"></script>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
