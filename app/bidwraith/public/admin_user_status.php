<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$admin = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $userId = (int) ($_POST['id'] ?? 0);
    $active = ($_POST['active'] ?? '') === '1';

    if ($userId === (int) $admin['id']) {
        set_flash('error', "You can't deactivate your own account.");
    } else {
        set_user_active($userId, $active);
        set_flash('success', $active ? 'Account activated.' : 'Account deactivated.');
    }
}

// Strict whitelist rather than trusting the posted path — anything else goes to the dashboard.
$return = (string) ($_POST['return'] ?? '');
redirect(preg_match('#^admin_user\.php\?id=\d+$#', $return) ? $return : 'admin.php');
