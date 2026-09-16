<?php
/**
 * Required by eBay's RuName form (Sandbox Keys / Production Keys -> "Get a Token
 * from eBay via Your Application") as the privacy policy URL. Public — no login,
 * since eBay's own review and any visitor may need to read it before you've
 * connected an account yourself.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$owner = owner_email();
$pageTitle = 'Privacy policy';
require __DIR__ . '/../includes/layout_top.php';
?>
<h1>Privacy policy</h1>
<p class="hint">Last updated <?= htmlspecialchars(date('F Y')) ?>.</p>

<p>Bidwraith is a personal tool for automatically placing timed bids on eBay auctions
   on behalf of its own user(s). This page describes what it stores and why.</p>

<h2>What is stored</h2>
<ul>
    <li><strong>Account:</strong> your email address and a hashed password (never the
        password itself).</li>
    <li><strong>eBay authorization:</strong> when you connect your eBay account, eBay
        issues this app an auth token scoped to placing bids on your behalf — the same
        mechanism eBay's own "buy it now" and proxy bidding use. This app never sees
        or stores your eBay password.</li>
    <li><strong>Watched auctions:</strong> the eBay item IDs you add, their end times,
        your scheduled bid amounts, and the outcome of each bid attempt.</li>
</ul>

<h2>What it is used for</h2>
<p>Solely to look up auction details and place the bids you configure, at the times
   you configure, via eBay's official APIs. Nothing here is sold, shared, or used
   for advertising.</p>

<h2>Who can see it</h2>
<p>Only the account holder and the app's administrator. Passwords are hashed and
   never visible to anyone, including the administrator.</p>

<h2>Revoking access</h2>
<p>Disconnect your eBay account at any time from the eBay account page in this app,
   which deletes the stored auth token immediately. You can also revoke this
   application's access directly from
   <a href="https://www.ebay.com/help/account/protecting-account/third-party-app-access" target="_blank" rel="noopener">your eBay account settings</a>.</p>

<h2>Contact</h2>
<p><?= $owner ? htmlspecialchars($owner) : 'Contact the site administrator.' ?></p>

<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
