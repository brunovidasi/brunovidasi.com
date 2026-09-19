<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (!billing_enabled()) {
        redirect('billing');
    }

    try {
        if (($_POST['action'] ?? '') === 'subscribe') {
            if (!user_email_verified($user)) {
                set_flash('error', 'Confirm your email address before starting a plan.');
                redirect('verify_email');
            }
            // A live subscription means Checkout would sell a second one alongside it.
            if (in_array($user['subscription_status'] ?? null, BILLING_ACCESS_STATUSES, true)) {
                redirect('billing');
            }
            $target = billing_checkout_url($user);
        } elseif (($_POST['action'] ?? '') === 'portal') {
            $target = billing_portal_url($user);
        } else {
            redirect('billing');
        }
    } catch (Throwable $e) {
        error_log('Bidwraith billing: ' . $e->getMessage());
        set_flash('error', "Couldn't reach the payment provider. Try again in a moment.");
        redirect('billing');
    }

    // 303 so the browser follows with a GET, whatever it just POSTed.
    header('Location: ' . $target, true, 303);
    exit;
}

// Stripe sends the customer back here after Checkout. Reading the result now, rather
// than waiting for the webhook, means the plan is live by the time the page renders.
if (billing_enabled() && get_param('checkout') === 'success' && get_param('session_id') !== '') {
    try {
        $completed = billing_complete_checkout(get_param('session_id'), (int) $user['id']);
        set_flash($completed ? 'success' : 'error', $completed
            ? 'You are all set — your plan is active.'
            : 'Your payment is still being confirmed. Refresh this page in a moment.');
    } catch (Throwable $e) {
        error_log('Bidwraith billing: ' . $e->getMessage());
        set_flash('error', "We couldn't confirm your checkout yet. If you were charged, it will appear here shortly.");
    }
    redirect('billing');
}

if (get_param('checkout') === 'cancelled') {
    set_flash('error', 'Checkout cancelled — you have not been charged.');
    redirect('billing');
}

// Re-read: the block above may have just changed this user's plan.
$stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

$group = billing_group($account);
$live = in_array($account['subscription_status'], BILLING_ACCESS_STATUSES, true);
$trialDays = billing_trial_days();
$canTrial = $trialDays > 0 && !billing_trial_used($account);

$fmt = fn (?string $dbTime) => $dbTime === null ? '—' : local_time(db_time_epoch($dbTime), substr($dbTime, 0, 10));

$pageTitle = 'Billing';
require __DIR__ . '/../includes/layout_top.php';
?>
<h1>Billing</h1>

<?php if (!billing_enabled()): ?>
    <p class="hint">Plans aren't switched on for this installation, so there's nothing to pay for.</p>
<?php else: ?>

<div class="detail-grid">
    <div><span class="detail-label">Plan</span><?= billing_group_badge($group) ?></div>

    <?php if ($group === 'trialing'): ?>
        <div><span class="detail-label">Trial ends</span><?= $fmt($account['trial_ends_at']) ?></div>
        <div>
            <span class="detail-label">Then</span>
            <?= $account['cancel_at_period_end'] ? 'Cancels — you won\'t be charged' : 'Charged monthly' ?>
        </div>
    <?php elseif ($live): ?>
        <div>
            <span class="detail-label"><?= $account['cancel_at_period_end'] ? 'Access ends' : 'Renews' ?></span>
            <?= $fmt($account['current_period_end']) ?>
        </div>
    <?php elseif ($group === 'ended'): ?>
        <div><span class="detail-label">Last period ended</span><?= $fmt($account['current_period_end']) ?></div>
    <?php endif; ?>
</div>

<?php if ($group === 'admin'): ?>
    <p class="hint">Admin accounts don't need a plan.</p>

<?php elseif ($group === 'free'): ?>
    <p class="hint">Your account has free access, so there's nothing to pay.</p>

<?php elseif ($group === 'past_due'): ?>
    <p class="hint">Your last payment didn't go through. Update your card and Stripe will try again — your bids keep working meanwhile.</p>

<?php elseif ($group === 'trialing' && $account['cancel_at_period_end']): ?>
    <p class="hint">Your trial is set to end without charging you. You can change your mind before <?= $fmt($account['trial_ends_at']) ?>.</p>

<?php elseif ($group === 'active' && $account['cancel_at_period_end']): ?>
    <p class="hint">Your plan is set to cancel. You keep full access until <?= $fmt($account['current_period_end']) ?>.</p>

<?php elseif (!$live): ?>
    <div class="settings-section">
        <h2><?= $canTrial ? 'Start your free trial' : 'Start a plan' ?></h2>
        <p>
            <?php if ($canTrial): ?>
                Try Bidwraith free for <?= $trialDays ?> days<?= billing_trial_requires_card() ? '. You\'ll enter a card now, but you\'re not charged until the trial ends, and you can cancel any time before then.' : '. No card needed to start.' ?>
            <?php else: ?>
                A monthly plan gives you unlimited auctions and bids. Cancel any time.
            <?php endif; ?>
        </p>
        <?php if (user_email_verified($account)): ?>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="subscribe">
                <button type="submit"><?= $canTrial ? "Start {$trialDays}-day free trial" : 'Subscribe' ?></button>
            </form>
        <?php else: ?>
            <p class="hint">Confirm your email address first — <a href="verify_email">we'll send you a link</a>.</p>
        <?php endif; ?>
        <p class="hint">Payments are handled by Stripe. Bidwraith never sees your card details.</p>
    </div>
<?php endif; ?>

<?php if (!empty($account['stripe_customer_id']) && $group !== 'admin'): ?>
    <div class="settings-section">
        <h2>Manage subscription</h2>
        <p class="hint">Update your card, download invoices, or cancel — on Stripe's secure page.</p>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="portal">
            <button type="submit" class="secondary">Manage subscription</button>
        </form>
    </div>
<?php endif; ?>

<?php endif; ?>

<?= app_scripts() ?>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
