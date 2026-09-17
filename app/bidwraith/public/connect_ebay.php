<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
$error = null;

$stmt = db()->prepare('SELECT * FROM ebay_accounts WHERE user_id = ?');
$stmt->execute([$user['id']]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (isset($_GET['start'])) {
    try {
        $client = new EbayClient();
        $sessionId = $client->getSessionId();
        $_SESSION['ebay_session_id'] = $sessionId;
        redirect($client->signInUrl($sessionId));
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

if (isset($_POST['disconnect'])) {
    csrf_verify();
    db()->prepare('DELETE FROM ebay_accounts WHERE user_id = ?')->execute([$user['id']]);
    set_flash('success', 'Disconnected your eBay account.');
    redirect('connect_ebay.php');
}

if (isset($_POST['update_currency'])) {
    csrf_verify();
    $newCurrency = $_POST['currency'] ?? '';
    if (in_array($newCurrency, SUPPORTED_CURRENCIES, true)) {
        db()->prepare('UPDATE users SET currency = ? WHERE id = ?')->execute([$newCurrency, $user['id']]);
        set_flash('success', 'Currency updated.');
    } else {
        set_flash('error', 'Choose a valid currency.');
    }
    redirect('connect_ebay.php');
}

$pageTitle = 'eBay account';
require __DIR__ . '/../includes/layout_top.php';
?>
<h1>eBay account</h1>
<?php if ($error): ?><div class="flash flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="settings-section">
    <h2>Account</h2>
    <?php if ($account): ?>
        <p>Connected (<?= htmlspecialchars($account['environment']) ?> environment) since <?= local_time(db_time_epoch($account['connected_at']), $account['connected_at']) ?>.</p>
        <form method="post" data-confirm="Disconnect your eBay account? Bidding will stop working until you reconnect.">
            <?= csrf_field() ?>
            <input type="hidden" name="disconnect" value="1">
            <button type="submit" class="danger">Disconnect</button>
        </form>
    <?php else: ?>
        <p>Connect your eBay account so this app can place bids on your behalf, the same way you would manually on eBay.</p>
        <a class="btn" href="connect_ebay.php?start=1">Connect eBay account</a>
        <p class="hint">
            You will be redirected to eBay to log in and authorize this app. After that, you'll be redirected back here.
            If you have two-factor authentication enabled on your eBay account, you may need to enter a code during the login process.
        </p>
    <?php endif; ?>
</div>

<div class="settings-section">
    <h2>Currency</h2>
    <p class="hint">Bids and prices are shown in this currency, and it's what's sent to eBay when placing a bid. Set from your eBay account's country the first time you connect; change it any time.</p>
    <form method="post" class="stacked">
        <?= csrf_field() ?>
        <input type="hidden" name="update_currency" value="1">
        <label for="currency">Currency</label>
        <select id="currency" name="currency">
            <?php foreach (SUPPORTED_CURRENCIES as $code): ?>
                <option value="<?= htmlspecialchars($code) ?>" <?= $code === user_currency($user) ? 'selected' : '' ?>><?= htmlspecialchars($code) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Save currency</button>
    </form>
</div>

<?= app_scripts() ?>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
