<?php
// This is the "Accepted URL" to configure for your RuName in the eBay Developer
// Sandbox Keys page. eBay redirects here after the user signs in and grants access.
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();

$sessionId = $_SESSION['ebay_session_id'] ?? null;
unset($_SESSION['ebay_session_id']);

if (!$sessionId) {
    set_flash('error', 'No pending eBay authorization found. Try connecting again.');
    redirect('connect_ebay.php');
}

try {
    $client = new EbayClient();
    $result = $client->fetchToken($sessionId);
    $environment = ebay_config()['environment'];

    db()->prepare('
        INSERT INTO ebay_accounts (user_id, environment, auth_token, token_expires_at, connected_at)
        VALUES (?, ?, ?, ?, datetime(\'now\'))
        ON CONFLICT(user_id) DO UPDATE SET
            environment = excluded.environment,
            auth_token = excluded.auth_token,
            token_expires_at = excluded.token_expires_at,
            connected_at = excluded.connected_at
    ')->execute([$user['id'], $environment, $result['token'], $result['expires_at']]);

    set_flash('success', 'eBay account connected.');
} catch (Throwable $e) {
    set_flash('error', 'Could not complete eBay authorization: ' . $e->getMessage());
}

redirect('connect_ebay.php');
