<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (current_user()) {
    redirect('dashboard');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $error = attempt_login($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($error === null) {
        redirect('dashboard');
    }
}

// The form lives in the masthead (includes/layout_top.php); it needs the outcome.
$mastheadLoginError = $error;
$mastheadLoginEmail = $_POST['email'] ?? '';

$pageTitle = 'Automatic eBay bidding';
require __DIR__ . '/../includes/layout_top.php';
?>
<section class="hero">
    <p class="hero-eyebrow">A private tool for eBay auction sniping</p>
    <h1 class="hero-title">Win eBay auctions without watching the clock.</h1>
    <p class="hero-sub">Set a max bid once. Bidwraith places it in the auction's final
        seconds through eBay's own proxy bidding &mdash; automatically, precisely, and
        only as high as it needs to.</p>
    <div class="hero-actions">
        <a href="#mastheadLoginEmail" class="btn">Log in</a>
    </div>
</section>

<section class="landing-section" id="features">
    <h2>Built for the last ten seconds</h2>
    <div class="feature-grid">
        <div class="feature-card">
            <h3>Timed sniping</h3>
            <p>Bids fire in the closing seconds of an auction through eBay's own
                proxy-bidding system &mdash; the same one behind "Place bid" on
                ebay.com. You're never the one hovering over the button at midnight.</p>
        </div>
        <div class="feature-card">
            <h3>Ready-made strategies</h3>
            <p>Pick a preset ladder of timed bids, or build your own down to the
                second. Each bid only fires if you haven't already won at an earlier,
                lower one.</p>
        </div>
        <div class="feature-card">
            <h3>Know your real cost</h3>
            <p>See an estimated total landed cost &mdash; price, shipping, eBay's
                buyer protection fee, and import tax &mdash; before you commit to a
                max bid.</p>
        </div>
        <div class="feature-card">
            <h3>A full paper trail</h3>
            <p>Every scheduled bid and every response eBay sends back is logged, so
                you always know whether you lost a bid war or a bid that simply
                didn't fire.</p>
        </div>
    </div>
</section>

<section class="landing-section" id="how-it-works">
    <h2>How it works</h2>
    <ol class="steps-list">
        <li>
            <span class="step-num">1</span>
            <div>
                <h3>Connect your eBay account</h3>
                <p>A one-time authorization through eBay's own sign-in. Bidwraith
                    never sees your password, and access can be revoked at any time.</p>
            </div>
        </li>
        <li>
            <span class="step-num">2</span>
            <div>
                <h3>Add an auction</h3>
                <p>Paste the item ID or listing URL and Bidwraith looks up the title,
                    end time, and current price for you.</p>
            </div>
        </li>
        <li>
            <span class="step-num">3</span>
            <div>
                <h3>Set your max and close the tab</h3>
                <p>Bidwraith places your bid in the closing seconds &mdash; even if
                    your browser is closed or you're asleep.</p>
            </div>
        </li>
    </ol>
</section>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
