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

$pageTitle = 'eBay account';
require __DIR__ . '/../includes/layout_top.php';
?>
<h1>eBay account</h1>
<?php if ($error): ?><div class="flash flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

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
        This requires a <strong>RuName</strong> to be configured in your eBay Developer account first
        (Sandbox Keys page &rarr; "Get a Token from eBay via Your Application"), pointing back to this app's
        callback URL. See the README for step-by-step setup.
    </p>
<?php endif; ?>

<script src="<?= asset_url('assets/js/app.js') ?>"></script>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
