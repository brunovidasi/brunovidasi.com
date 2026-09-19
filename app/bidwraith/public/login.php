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
    <p class="hero-eyebrow">For eBay auction snipers</p>
    <h1 class="hero-title">Win eBay auctions without watching the clock.</h1>
    <p class="hero-sub">Set a max bid once. Bidwraith places it in the auction's final
        seconds through eBay's own proxy bidding &mdash; automatically, precisely, and
        only as high as it needs to.</p>
    <div class="hero-actions">
        <a href="#pricing" class="btn">See membership</a>
        <a href="#mastheadLoginEmail" class="btn secondary">Log in</a>
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

<section class="landing-section" id="pricing">
    <h2>Membership</h2>
    <p class="hint">Preview only &mdash; sign-ups and billing aren't live yet. This is
        what it'll look like.</p>
    <div class="pricing-card">
        <div class="pricing-badge">Preview</div>
        <h3>Bidwraith Membership</h3>
        <p class="pricing-amount">$7<span>/month</span></p>
        <p class="hint">Placeholder pricing &mdash; nothing here is final.</p>
        <ul class="pricing-features">
            <li>Unlimited auctions &amp; scheduled bids</li>
            <li>Every bidding strategy, including custom step ladders</li>
            <li>Live landed-cost estimates before you commit</li>
            <li>Full bid history &amp; audit log per auction</li>
            <li>eBay watchlist import</li>
        </ul>
        <form class="stacked mock-waitlist" id="waitlistForm">
            <label for="waitlistEmail">Email</label>
            <input type="email" id="waitlistEmail" placeholder="you@example.com" required>
            <button type="submit">Request access</button>
        </form>
        <p class="mock-note" id="waitlistNote" hidden>Thanks for the interest &mdash;
            Bidwraith isn't taking paid members yet. Email me at
            <a href="https://brunovida.si/contact">brunovida.si/contact</a> and I'll
            let you know when it opens.</p>
    </div>
</section>

<script>
(function () {
    var form = document.getElementById('waitlistForm');
    var note = document.getElementById('waitlistNote');
    if (!form || !note) return;
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        form.hidden = true;
        note.hidden = false;
    });
})();
</script>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
