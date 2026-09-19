<?php
require_once __DIR__ . '/../includes/bootstrap.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $userId = (int) ($_POST['id'] ?? 0);

    $stmt = db()->prepare('SELECT stripe_subscription_id FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        set_flash('error', 'User not found.');
    } elseif (($_POST['action'] ?? '') === 'refresh') {
        // Re-reads the subscription from Stripe — for when a webhook was missed.
        try {
            if (!billing_enabled() || empty($row['stripe_subscription_id'])) {
                throw new RuntimeException('This user has no Stripe subscription to refresh.');
            }
            sync_subscription($row['stripe_subscription_id'], $userId);
            set_flash('success', 'Subscription refreshed from Stripe.');
        } catch (Throwable $e) {
            set_flash('error', $e->getMessage());
        }
    } else {
        $free = ($_POST['free_access'] ?? '') === '1';
        set_user_free_access($userId, $free, (string) ($_POST['note'] ?? ''));
        set_flash('success', $free ? 'Free access granted.' : 'Free access removed.');
    }
}

// Strict whitelist rather than trusting the posted path — anything else goes to the dashboard.
$return = (string) ($_POST['return'] ?? '');
redirect(preg_match('#^admin_user\?id=\d+$#', $return) ? $return : 'admin');
