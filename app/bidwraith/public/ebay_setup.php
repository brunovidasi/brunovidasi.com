<?php
/**
 * Admin helper for registering this app with eBay's Developer Program. eBay's
 * RuName form needs three exact URLs pasted in, and a wrong one (trailing slash,
 * wrong path) fails silently at connect time rather than when you save it — so
 * this renders them for copy/paste instead of retyping from memory.
 *
 * Also offers a live test of the ACTIVE credential set: fetches a real app-level
 * OAuth token from eBay, which is the first thing that has to work before bidding
 * can, and fails fast with eBay's own error text if the keys are wrong.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

require_admin();

$testResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_connection'])) {
    csrf_verify();
    try {
        // A fresh client re-reads config, so a key just pasted into the instance
        // config is picked up without needing a separate deploy or restart.
        $client = new EbayClient();
        $token = $client->getAppAccessToken();
        $testResult = ['ok' => true, 'message' => 'Got an app access token from eBay (' . strlen($token) . ' characters). Your App ID / Cert ID are valid and eBay is reachable.'];
    } catch (Throwable $e) {
        $testResult = ['ok' => false, 'message' => $e->getMessage()];
    }
}

$api = ebay_api();
$missing = ebay_keys_missing();
$callbackUrl = ebay_callback_url();
$declinedUrl = base_url() . '/connect_ebay.php';
$privacyUrl = base_url() . '/privacy.php';

$pageTitle = 'eBay developer setup';
require __DIR__ . '/../includes/layout_top.php';
?>
<h1>eBay developer setup</h1>
<p class="hint">Active eBay API side: <strong><?= htmlspecialchars($api) ?></strong>
   (set by <code>ebay_api</code> in the <?= htmlspecialchars(app_env()) ?> environment block).</p>

<?php if ($missing): ?>
    <div class="flash flash-error">
        Missing in <code>ebay_keys.<?= htmlspecialchars($api) ?></code>: <?= htmlspecialchars(implode(', ', $missing)) ?>.
    </div>
<?php endif; ?>

<h2>1. Create (or find) your RuName</h2>
<p>
    eBay Developer Program &rarr; My Account &rarr;
    <?= $api === 'production' ? 'Application Keys, using the Production keyset' : 'Sandbox Keys' ?>
    &rarr; &ldquo;Get a Token from eBay via Your Application&rdquo;.
    <?php if ($api === 'production'): ?>
        Production keys require account verification and accepting the API License
        Agreement first &mdash; do that before this step if you haven't already.
    <?php endif; ?>
</p>
<p>Paste these exactly as shown (no trailing slash):</p>

<table class="admin-table">
    <tr>
        <td><strong>Your auction privacy policy URL</strong></td>
        <td><code><?= htmlspecialchars($privacyUrl) ?></code></td>
    </tr>
    <tr>
        <td><strong>Your auction listing consent URL</strong></td>
        <td><code><?= htmlspecialchars($privacyUrl) ?></code> (same page &mdash; this app doesn't list items)</td>
    </tr>
    <tr>
        <td><strong>Accepted URL</strong></td>
        <td><code><?= htmlspecialchars($callbackUrl) ?></code></td>
    </tr>
    <tr>
        <td><strong>Declined URL</strong></td>
        <td><code><?= htmlspecialchars($declinedUrl) ?></code></td>
    </tr>
</table>

<h2>2. Copy the keys into config</h2>
<p>eBay shows App ID (Client ID), Dev ID, Cert ID (Client Secret), and the RuName it
   generated. Put them in <code>bidwraith-instance/config.php</code> on the server,
   under <code>ebay_keys.<?= htmlspecialchars($api) ?></code>.</p>

<h2>3. Test the connection</h2>
<p>Checks that App ID / Cert ID are valid and eBay's OAuth endpoint is reachable from
   this server &mdash; the first thing that has to work before anything else does.
   It does not test bidding itself, which additionally needs a connected eBay account
   (eBay account page) and, in Sandbox, a real Sandbox test listing.</p>

<form method="post">
    <?= csrf_field() ?>
    <button type="submit" name="test_connection" value="1">Test connection (<?= htmlspecialchars($api) ?>)</button>
</form>

<?php if ($testResult): ?>
    <div class="flash flash-<?= $testResult['ok'] ? 'success' : 'error' ?>">
        <?= htmlspecialchars($testResult['message']) ?>
    </div>
<?php endif; ?>

<?php if ($api === 'sandbox'): ?>
    <h2>Sandbox limitation</h2>
    <p>The Browse API in Sandbox only resolves items that exist in your own Sandbox
       seller test inventory &mdash; it will not find real ebay.com item IDs. When
       testing the UI with a real item ID, enter the end time manually; the actual
       bid call still needs a Sandbox test listing to succeed for real.</p>
<?php endif; ?>

<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
