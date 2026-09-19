<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
$currency = user_currency($user);

$pageTitle = 'Guide';
require __DIR__ . '/../includes/layout_top.php';
?>
<h1>Guide</h1>
<p class="hint">How Bidwraith works and how to get the most out of it.</p>

<div class="settings-section">
    <h2>What Bidwraith does</h2>
    <p>Bidwraith is an auction sniper: instead of watching an eBay auction and bidding
       manually as it closes, you set up one or more timed bids in advance, and a cron
       job fires them at the exact right moment &mdash; even if your browser is closed
       or you're asleep. Bidding itself goes through eBay's own proxy-bidding mechanism
       (the same one behind "place bid" on ebay.com): you set a max, and eBay
       automatically raises your bid only as far as needed to stay ahead of other
       bidders, up to that max.</p>
</div>

<div class="settings-section">
    <h2>Quick start</h2>
    <ol>
        <li><a href="connect_ebay">Connect your eBay account</a> once, so the app
            can place bids on your behalf.</li>
        <li><a href="add_auction">Add an auction</a> by its eBay item ID or listing
            URL.</li>
        <li>Enter your max bid and pick a strategy (or set your own timing).</li>
        <li>Watch it on your <a href="dashboard">auction list</a> &mdash; Bidwraith
            takes it from there.</li>
    </ol>
</div>

<div class="settings-section">
    <h2>Connecting your eBay account</h2>
    <p>Go to <a href="connect_ebay">eBay account</a> and click
       <strong>Connect eBay account</strong>. You'll be redirected to eBay to sign in
       and authorize Bidwraith &mdash; the same "connect this app" flow eBay uses for
       any third-party tool. Bidwraith never sees or stores your eBay password, only a
       token eBay issues that can be revoked at any time, either by disconnecting from
       this page or from eBay's own
       <a href="https://www.ebay.com/help/account/protecting-account/third-party-app-access" target="_blank" rel="noopener">third-party app access settings</a>.
       Bidding stops working the moment it's disconnected, so reconnect before your
       next auction closes if you ever do.</p>
    <p>The same page also sets your <strong>currency</strong> &mdash; the currency bids,
       prices, and estimates are shown in, and what's sent to eBay when a bid is
       placed. It's set automatically from your eBay account the first time you
       connect, and can be changed any time.</p>
</div>

<div class="settings-section">
    <h2>Adding an auction</h2>
    <p>On <a href="add_auction">Add auction</a>, paste the eBay item ID or the full
       listing URL. Bidwraith looks the item up automatically to fill in its title, end
       time, and current price. If a lookup fails (this can happen for some listings),
       you'll be asked to enter the auction's end time yourself.</p>
    <p>Enter your <strong>max bid</strong> &mdash; the most you're willing to pay,
       excluding shipping and fees &mdash; then choose how your bids should be timed:</p>
    <ul>
        <li><strong>Strategies:</strong> ready-made timing presets, scaled from your max
            bid:
            <ul>
                <li><em>3 Steps Strategy</em> &mdash; three bids at 5s, 3s, and 2s before
                    the end, each one only firing if you haven't already won at an
                    earlier, lower bid.</li>
                <li><em>Last Second Strategy</em> &mdash; a single bid 2s before the end.</li>
                <li><em>High Risk Strategy</em> &mdash; a single bid 1s before the end.
                    Cutting it this close risks eBay responding too slowly for the bid
                    to register in time.</li>
                <li><em>I Want The Item Anyway</em> and <em>Scheduled Bid</em> jump to
                    their own tabs, described below.</li>
            </ul>
        </li>
        <li><strong>Steps:</strong> build your own ladder of up to 5 bids by hand, each
            with its own "seconds before end" (1&ndash;60) and max bid. Use
            <strong>Add random cents</strong> to bump an amount just over a round number
            &mdash; a less common ending that can be the difference in a tie.</li>
        <li><strong>Scheduled Bid:</strong> a single bid timed separately from the Steps
            ladder &mdash; either hours/minutes before the end, or at an exact date and
            time. Useful for auctions ending far enough out that a last-second snipe
            isn't the point (e.g. bidding as soon as you're back online).</li>
        <li><strong>I Want The Item Anyway:</strong> for when you want to win no matter
            what. It's still a single bid (<?= ANYWAY_DEFAULT_SECONDS_BEFORE ?>s before the
            end by default), but its amount isn't decided now: when it fires, it reads the
            item's live price and adds a flat value or percentage on top of that. Because
            that price depends on how far others have pushed it by then, you're committing
            to an amount you can't see in advance &mdash; the optional <strong>max
            value</strong> is what caps it. Only use this if you genuinely want the item
            at whatever that comes to.</li>
    </ul>
    <p class="hint">You can combine a Steps ladder with one Scheduled Bid and one
        Anyway bid on the same auction (up to 5 bids total) &mdash; each one only fires
        if the auction hasn't already been won at an earlier, lower bid.</p>
</div>

<div class="settings-section">
    <h2>Your auction list</h2>
    <p>The <a href="dashboard">auction list</a> shows every auction you're
       watching, with its live current price, countdown, and every bid you've
       scheduled for it. A few things worth knowing:</p>
    <ul>
        <li><strong>Outbid</strong> &mdash; shown when the current price has already
            reached or passed your highest scheduled bid. Raise your max bid (via
            <em>Edit bids</em>) if you still want a shot at winning.</li>
        <li><strong>Est. total if you win</strong> is a worst-case estimate: your
            highest scheduled bid, plus shipping, plus eBay's published Buyer
            Protection fee, plus GST on low-value imports where relevant. Proxy
            bidding may still win the item for less.</li>
        <li>Auctions stay in this list for a day after they close so you can see the
            result, then move down into the <strong>Past auctions</strong> table.</li>
    </ul>
    <p>Status values you'll see on a bid or auction: <span class="status-pending">pending</span>
       (not fired yet), <span class="status-bid_placed">bid_placed</span> (sent to
       eBay successfully), <span class="status-failed">failed</span> (eBay rejected
       it or it errored), <span class="status-won">won</span> and
       <span class="status-lost">lost</span> once the auction is settled.</p>
</div>

<div class="settings-section">
    <h2>Auction details &amp; the bid log</h2>
    <p>Click any auction's <strong>Details &amp; log</strong> link for its full history:
       a timeline of what happened, the bids that were scheduled, and the raw eBay
       bid attempts. <strong>Scheduled bids</strong> is what the cron job was told to
       do; <strong>eBay bid attempts</strong> is what was actually sent to eBay and
       what it said back. A scheduled bid with no matching attempt never left the app
       &mdash; useful for confirming whether a loss was a real bid war or a bid that
       simply didn't fire.</p>
</div>

<div class="settings-section">
    <h2>Editing &amp; removing auctions</h2>
    <p>Any auction that hasn't ended yet can be edited &mdash; adjust amounts, timing,
       or add another bid, from the auction list's <strong>Edit bids</strong> link. A
       bid that has already fired is locked in as read-only so the record of what
       actually happened stays accurate. Remove an auction entirely with the trash
       icon or the <strong>Remove</strong> link on its details page; this only removes
       it from Bidwraith, not from eBay.</p>
</div>

<div class="settings-section">
    <h2>Watchlist</h2>
    <p>The <a href="watchlist">Watchlist</a> page shows auctions you're watching on
       eBay itself (via the star icon on ebay.com), separate from your Bidwraith
       auction list. It's a quick way to find items you've already flagged on eBay and
       add timed bids for them without hunting down the item ID.</p>
</div>

<div class="settings-section">
    <h2>Tips</h2>
    <ul>
        <li>The cron job only looks about a minute ahead, so it needs to run every
            minute without fail to catch every ending auction &mdash; if bids seem to
            be silently not firing, that's the first thing to check (an admin can see
            the last cron run time on the admin dashboard).</li>
            <li>Bidding under 2&ndash;3 seconds before the end carries real risk: if
            eBay responds slowly, there may not be enough time left for the bid to
            register before the auction closes.</li>
        <li>A higher max bid doesn't mean you pay more &mdash; proxy bidding only raises
            your bid as far as needed to beat the next competing bid, up to your max.</li>
        <li>Use <strong>Add random cents</strong> on close bids; round numbers are a
            common ending, so a few extra cents can be the difference between winning
            and losing a tie.</li>
    </ul>
</div>

<?= app_scripts() ?>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
