<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $stmt = db()->prepare('DELETE FROM watched_auctions WHERE id = ? AND user_id = ?');
    $stmt->execute([(int) ($_POST['id'] ?? 0), $user['id']]);
    set_flash('success', 'Removed from auction list.');
}

redirect('dashboard');
