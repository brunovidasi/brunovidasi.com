<?php
/**
 * Subscription billing: a monthly plan with a free trial, sold through Stripe
 * Checkout and managed by users in Stripe's hosted Customer Portal.
 *
 * Stripe is the source of truth for everything about a subscription. The users table
 * only keeps a copy of the few fields the app needs to decide "may this person add
 * bids?" and to show the admin who is in trial / paying. That copy is refreshed from
 * two places: the webhook (public/stripe_webhook.php) and the page Checkout returns
 * to (public/billing.php), so a user is never left waiting on webhook delivery.
 *
 * Two things sit outside Stripe:
 *  - admins always have access, and
 *  - users.free_access, which an admin grants by hand for people who should use the
 *    app without paying (friends, testers, comps).
 *
 * Until stripe.secret_key and stripe.price_id are set, billing is off and nothing is
 * gated. That keeps a fresh deploy, and local development, from locking anyone out.
 */

/** Stripe subscription statuses that keep the app usable. past_due is a grace period
 *  while Stripe retries the card, so a single failed payment doesn't cut off bidding. */
const BILLING_ACCESS_STATUSES = ['trialing', 'active', 'past_due'];

function billing_config(): array
{
    return app_config()['stripe'] ?? [];
}

function billing_enabled(): bool
{
    $config = billing_config();
    return !empty($config['secret_key']) && !empty($config['price_id']);
}

function billing_trial_days(): int
{
    return max(0, (int) (billing_config()['trial_days'] ?? 7));
}

/** Whether Checkout asks for a card up front to start the trial. */
function billing_trial_requires_card(): bool
{
    return (bool) (billing_config()['trial_requires_card'] ?? true);
}

/**
 * Whether this user may create or change bids. Takes the row from current_user(),
 * which carries the billing columns.
 */
function user_has_access(array $user): bool
{
    // An unconfirmed address can't be used to add bids, plan or no plan: it is what
    // stops throwaway sign-ups farming trials, and it is how we reach people when a
    // bid fails.
    if (!user_email_verified($user)) {
        return false;
    }
    if (!billing_enabled()) {
        return true;
    }
    if (!empty($user['is_admin']) || !empty($user['free_access'])) {
        return true;
    }

    return in_array($user['subscription_status'] ?? null, BILLING_ACCESS_STATUSES, true);
}

/**
 * Sends a user without access to the billing page. Called at the top of the pages
 * that create or change bids. Deleting an auction, and viewing the list, stay open,
 * and bids already scheduled still fire — lapsing stops new work, it doesn't abandon
 * auctions someone is already counting on.
 */
function require_access(array $user): void
{
    if (!user_email_verified($user)) {
        set_flash('error', 'Confirm your email address first — we sent you a link.');
        redirect('verify_email');
    }
    if (user_has_access($user)) {
        return;
    }

    set_flash('error', 'Start a plan to add or change bids.');
    redirect('billing');
}

/**
 * The single classification used everywhere a person's plan is shown or filtered,
 * so the users list, the subscriptions page and the user detail page can't disagree.
 * Order matters: admin, then free access, then whatever Stripe says.
 *
 *   admin | free | trialing | active | past_due | ended | none
 *
 * 'ended' is any Stripe status that no longer grants access (canceled, unpaid,
 * incomplete, …); 'none' is someone who never started a subscription.
 */
function billing_group(array $user): string
{
    if (!empty($user['is_admin'])) {
        return 'admin';
    }
    if (!empty($user['free_access'])) {
        return 'free';
    }

    $status = $user['subscription_status'] ?? null;
    if ($status === null || $status === '') {
        return 'none';
    }

    return in_array($status, BILLING_ACCESS_STATUSES, true) ? $status : 'ended';
}

/** SQL twin of billing_group() for queries that count or filter by it. */
function billing_group_sql(string $alias = 'u'): string
{
    $accessList = "'" . implode("','", BILLING_ACCESS_STATUSES) . "'";

    return "CASE
        WHEN $alias.is_admin = 1 THEN 'admin'
        WHEN $alias.free_access = 1 THEN 'free'
        WHEN $alias.subscription_status IS NULL OR $alias.subscription_status = '' THEN 'none'
        WHEN $alias.subscription_status IN ($accessList) THEN $alias.subscription_status
        ELSE 'ended'
    END";
}

const BILLING_GROUP_LABELS = [
    'admin'    => 'Admin',
    'free'     => 'Free access',
    'trialing' => 'In trial',
    'active'   => 'Subscribed',
    'past_due' => 'Payment failed',
    'ended'    => 'Ended',
    'none'     => 'No plan',
];

function billing_group_label(string $group): string
{
    return BILLING_GROUP_LABELS[$group] ?? $group;
}

/** A <span> styled like the other status words, e.g. "● In trial". */
function billing_group_badge(string $group): string
{
    return '<span class="status-' . htmlspecialchars($group) . '">' . htmlspecialchars(billing_group_label($group)) . '</span>';
}

/** Storage format for a Stripe unix timestamp: UTC, like every datetime('now') column. */
function billing_db_time($timestamp): ?string
{
    return is_numeric($timestamp) ? gmdate('Y-m-d H:i:s', (int) $timestamp) : null;
}

/** Link into the Stripe dashboard, on the test-mode side when running on a test key. */
function stripe_dashboard_url(string $path): string
{
    $test = str_starts_with((string) (billing_config()['secret_key'] ?? ''), 'sk_test');
    return 'https://dashboard.stripe.com/' . ($test ? 'test/' : '') . ltrim($path, '/');
}

/** Whole days from now until a stored UTC time, rounded up; 0 once it has passed. */
function billing_days_until(?string $dbTime): int
{
    $epoch = db_time_epoch($dbTime);
    return $epoch === null ? 0 : max(0, (int) ceil(($epoch - time()) / 86400));
}

/**
 * Copies a Stripe Subscription object onto the user it belongs to.
 *
 * Returns false when the update was deliberately skipped: a user who re-subscribes
 * gets a new subscription id, and the old one's late "canceled" event must not
 * overwrite the live one. Stripe doesn't promise events arrive in order.
 */
function apply_subscription(int $userId, array $subscription): bool
{
    $subscriptionId = (string) ($subscription['id'] ?? '');
    $status = (string) ($subscription['status'] ?? '');
    if ($subscriptionId === '' || $status === '') {
        return false;
    }

    $current = db()->prepare('SELECT stripe_subscription_id, subscription_status, cancel_at_period_end FROM users WHERE id = ?');
    $current->execute([$userId]);
    $row = $current->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return false;
    }

    $incomingLive = in_array($status, BILLING_ACCESS_STATUSES, true);
    $currentLive = in_array($row['subscription_status'], BILLING_ACCESS_STATUSES, true);
    if ($row['stripe_subscription_id'] !== null && $row['stripe_subscription_id'] !== $subscriptionId && $currentLive && !$incomingLive) {
        return false;
    }

    $customer = $subscription['customer'] ?? null;
    $customerId = is_array($customer) ? ($customer['id'] ?? null) : $customer;

    // Newer Stripe API versions moved the billing period from the subscription onto
    // its items; older ones have it on the subscription. Read both.
    $periodEnd = $subscription['current_period_end'] ?? ($subscription['items']['data'][0]['current_period_end'] ?? null);

    $cancelling = !empty($subscription['cancel_at_period_end']) || !empty($subscription['cancel_at']);

    db()->prepare('
        UPDATE users
        SET stripe_customer_id = COALESCE(?, stripe_customer_id),
            stripe_subscription_id = ?,
            subscription_status = ?,
            trial_ends_at = ?,
            current_period_end = ?,
            cancel_at_period_end = ?
        WHERE id = ?
    ')->execute([
        $customerId !== null ? (string) $customerId : null,
        $subscriptionId,
        $status,
        billing_db_time($subscription['trial_end'] ?? null),
        billing_db_time($periodEnd),
        $cancelling ? 1 : 0,
        $userId,
    ]);

    try {
        billing_send_transition_emails($userId, $row, $subscriptionId, $status, $cancelling, billing_db_time($periodEnd));
    } catch (Throwable $e) {
        // The subscription is already saved; a mail problem must not undo or hide that.
        error_log('Bidwraith billing email: ' . $e->getMessage());
    }

    return true;
}

/**
 * Tells the user (and the owner) about what just changed, judged by comparing the row
 * as it was against what Stripe now says. Only real changes send anything, and each
 * message also carries a dedupe key, so replayed webhooks and the Checkout return page
 * racing the webhook can't double up.
 *
 * @param array $before the user's stripe_subscription_id, subscription_status and cancel_at_period_end
 */
function billing_send_transition_emails(int $userId, array $before, string $subscriptionId, string $status, bool $cancelling, ?string $periodEnd): void
{
    $stmt = db()->prepare('SELECT email, trial_ends_at FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        return;
    }

    $sameSubscription = $before['stripe_subscription_id'] === $subscriptionId;
    $was = $sameSubscription ? $before['subscription_status'] : null;
    $wasLive = in_array($was, BILLING_ACCESS_STATUSES, true);
    $isLive = in_array($status, BILLING_ACCESS_STATUSES, true);
    $wasCancelling = $sameSubscription && !empty($before['cancel_at_period_end']);
    $to = $user['email'];

    if ($status === 'trialing' && $was !== 'trialing') {
        queue_email($to, email_tpl_trial_started(billing_trial_days(), $user['trial_ends_at']), 'trial_started', $userId, "trial_started:$subscriptionId");
        notify_admin('New trial started', "$to started a free trial.", "admin_trial:$subscriptionId");
    }

    if ($status === 'active' && !in_array($was, ['active', 'past_due'], true)) {
        queue_email($to, email_tpl_subscribed(), 'subscribed', $userId, "subscribed:$subscriptionId");
        notify_admin('New paying subscriber', "$to now has an active subscription.", "admin_subscribed:$subscriptionId");
    }

    if ($status === 'past_due' && $was !== 'past_due') {
        queue_email($to, email_tpl_payment_failed(), 'payment_failed', $userId, "payment_failed:$subscriptionId:$periodEnd");
        notify_admin('A payment failed', "$to's subscription payment failed.", "admin_payment_failed:$subscriptionId:$periodEnd");
    }

    if ($isLive && $cancelling && !$wasCancelling && $wasLive) {
        queue_email($to, email_tpl_cancelling($periodEnd), 'cancelling', $userId, "cancelling:$subscriptionId:$periodEnd");
    }

    if (!$isLive && $wasLive) {
        queue_email($to, email_tpl_ended(), 'ended', $userId, "ended:$subscriptionId");
        notify_admin('A subscription ended', "$to's subscription is now $status.", "admin_ended:$subscriptionId");
    }
}

/**
 * Stripe's "trial ends in 3 days" event. Nothing to tell someone who has already
 * cancelled, so those are skipped.
 */
function billing_notify_trial_ending(array $subscription): void
{
    if (!empty($subscription['cancel_at_period_end']) || !empty($subscription['cancel_at'])) {
        return;
    }

    $userId = billing_find_user_id($subscription);
    if ($userId === null) {
        return;
    }

    $stmt = db()->prepare('SELECT email FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $email = $stmt->fetchColumn();
    if ($email === false) {
        return;
    }

    $cardOnFile = billing_trial_requires_card() || !empty($subscription['default_payment_method']);
    queue_email($email, email_tpl_trial_ending(billing_db_time($subscription['trial_end'] ?? null), $cardOnFile), 'trial_ending', $userId, 'trial_ending:' . $subscription['id']);
}

/** Which user a Stripe subscription belongs to, or null if it can't be matched. */
function billing_find_user_id(array $subscription): ?int
{
    $candidates = [
        ['SELECT id FROM users WHERE id = ?', $subscription['metadata']['user_id'] ?? null],
        ['SELECT id FROM users WHERE stripe_subscription_id = ?', $subscription['id'] ?? null],
        ['SELECT id FROM users WHERE stripe_customer_id = ?', is_array($subscription['customer'] ?? null) ? ($subscription['customer']['id'] ?? null) : ($subscription['customer'] ?? null)],
    ];

    foreach ($candidates as [$sql, $value]) {
        if ($value === null || $value === '') {
            continue;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute([(string) $value]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }
    }

    return null;
}

/**
 * Re-reads a subscription from Stripe and stores it. The webhook does this instead
 * of trusting the event's own copy, so a late or out-of-order event still leaves the
 * user matching what Stripe says right now.
 *
 * @return ?int the user updated, or null when no user matched or the update was skipped
 * @throws RuntimeException when Stripe can't be reached
 */
function sync_subscription(string $subscriptionId, ?int $userId = null): ?int
{
    $subscription = (new StripeClient())->retrieveSubscription($subscriptionId);
    $userId ??= billing_find_user_id($subscription);

    return $userId !== null && apply_subscription($userId, $subscription) ? $userId : null;
}

/**
 * Finishes a return from Stripe Checkout: looks the session up, checks it was
 * started by this user, and stores the subscription it created.
 *
 * @return bool false when the session hasn't produced a subscription (yet)
 * @throws RuntimeException on a Stripe error or a session that isn't this user's
 */
function billing_complete_checkout(string $sessionId, int $userId): bool
{
    $session = (new StripeClient())->retrieveCheckoutSession($sessionId);

    // The session id arrives in the URL, so without this anyone could paste in
    // someone else's and be credited with their subscription.
    if ((string) ($session['client_reference_id'] ?? '') !== (string) $userId) {
        throw new RuntimeException('That checkout belongs to a different account.');
    }

    $subscription = $session['subscription'] ?? null;
    $subscriptionId = is_array($subscription) ? ($subscription['id'] ?? null) : $subscription;
    if (!$subscriptionId) {
        return false;
    }

    sync_subscription((string) $subscriptionId, $userId);
    return true;
}

/** Whether this user has ever had a Stripe subscription — and so has used their trial. */
function billing_trial_used(array $user): bool
{
    return !empty($user['stripe_subscription_id']);
}

/** The Checkout Session parameters for this user. Split out so they can be checked without calling Stripe. */
function billing_checkout_params(array $user): array
{
    $trialDays = billing_trial_days();

    $subscriptionData = ['metadata' => ['user_id' => (string) $user['id']]];
    $params = [
        'mode' => 'subscription',
        'client_reference_id' => (string) $user['id'],
        'line_items' => [['price' => billing_config()['price_id'], 'quantity' => 1]],
        'success_url' => base_url() . '/billing?checkout=success&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => base_url() . '/billing?checkout=cancelled',
        'allow_promotion_codes' => 'true',
    ];

    // One trial per person: someone who cancelled and comes back pays from day one,
    // otherwise cancelling and re-subscribing is a free month, forever.
    if ($trialDays > 0 && !billing_trial_used($user)) {
        $subscriptionData['trial_period_days'] = $trialDays;

        if (!billing_trial_requires_card()) {
            $params['payment_method_collection'] = 'if_required';
            $subscriptionData['trial_settings'] = ['end_behavior' => ['missing_payment_method' => 'cancel']];
        }
    }
    $params['subscription_data'] = $subscriptionData;

    // Checkout takes either an existing customer or an email to create one from.
    if (!empty($user['stripe_customer_id'])) {
        $params['customer'] = $user['stripe_customer_id'];
    } else {
        $params['customer_email'] = $user['email'];
    }

    return $params;
}

/** Creates a Checkout Session for the user and returns the Stripe URL to send them to. */
function billing_checkout_url(array $user): string
{
    $session = (new StripeClient())->createCheckoutSession(billing_checkout_params($user));
    if (empty($session['url'])) {
        throw new RuntimeException('Stripe did not return a checkout URL.');
    }

    return $session['url'];
}

/** Creates a Customer Portal session (change card, cancel, invoices) and returns its URL. */
function billing_portal_url(array $user): string
{
    if (empty($user['stripe_customer_id'])) {
        throw new RuntimeException('This account has no Stripe customer yet.');
    }

    $session = (new StripeClient())->createPortalSession($user['stripe_customer_id'], base_url() . '/billing');
    if (empty($session['url'])) {
        throw new RuntimeException('Stripe did not return a portal URL.');
    }

    return $session['url'];
}

function set_user_free_access(int $userId, bool $free, string $note): void
{
    $note = trim($note);
    db()->prepare('UPDATE users SET free_access = ?, free_access_note = ? WHERE id = ?')
        ->execute([$free ? 1 : 0, $free && $note !== '' ? mb_substr($note, 0, 200) : null, $userId]);
}

/**
 * The one-line notice shown across the top of the app to someone whose plan needs
 * attention, or null when there's nothing to say (paying, free, admin, billing off).
 *
 * @return ?array{type: string, message: string}
 */
function billing_notice(array $user): ?array
{
    if (!billing_enabled()) {
        return null;
    }

    $link = ['href' => 'billing', 'label' => 'Billing →'];
    $notice = billing_notice_text($user);

    return $notice === null ? null : $notice + $link;
}

/** @return ?array{type: string, message: string} */
function billing_notice_text(array $user): ?array
{
    switch (billing_group($user)) {
        case 'trialing':
            $days = billing_days_until($user['trial_ends_at'] ?? null);
            return ['type' => 'info', 'message' => $days > 1
                ? "Your free trial ends in $days days."
                : ($days === 1 ? 'Your free trial ends tomorrow.' : 'Your free trial ends today.')];
        case 'past_due':
            return ['type' => 'error', 'message' => "Your last payment didn't go through. Update your card to keep your plan."];
        case 'ended':
            return ['type' => 'error', 'message' => "You don't have an active plan, so you can't add or change bids."];
        case 'none':
            return ['type' => 'error', 'message' => billing_trial_days() > 0 && !billing_trial_used($user)
                ? 'Start your ' . billing_trial_days() . '-day free trial to begin adding bids.'
                : 'Start a plan to begin adding bids.'];
        default:
            return null;
    }
}
