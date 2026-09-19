<?php
/**
 * The words and look of every email. Each email_tpl_*() returns
 * ['subject' => …, 'text' => …, 'html' => …], ready for queue_email().
 *
 * Emails are written to stand on their own: someone reading one in a mail client
 * months later should still know what it is, why they got it, and what to do — with a
 * plain-text version that says the same thing, since some clients (and spam filters)
 * favour it.
 */

/** Absolute link to a page of the app, for use inside an email. */
function email_link(string $path): string
{
    return base_url() . '/' . ltrim($path, '/');
}

/** "26 Sep 2026" for a stored UTC time or an app-local time, in the app's timezone. */
function email_date(?string $dbTime, bool $utc = true): string
{
    $epoch = $utc ? db_time_epoch($dbTime) : ($dbTime !== null && $dbTime !== '' ? strtotime($dbTime) : null);
    return $epoch === null ? 'soon' : date('j M Y', $epoch);
}

/**
 * Lays an email out in both formats.
 *
 * @param string[] $paragraphs plain text; escaped for HTML here
 * @param ?array{label: string, url: string} $button the one thing to do next
 * @param array<int, array{0: string, 1: string}> $details label/value rows for a small table
 * @return array{text: string, html: string}
 */
function email_render(string $heading, array $paragraphs, ?array $button = null, array $details = [], string $footer = ''): array
{
    $e = fn (string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

    $text = $heading . "\n" . str_repeat('=', min(60, mb_strlen($heading))) . "\n\n";
    foreach ($paragraphs as $p) {
        $text .= wordwrap($p, 72) . "\n\n";
    }
    foreach ($details as [$label, $value]) {
        $text .= $label . ': ' . $value . "\n";
    }
    if ($details) {
        $text .= "\n";
    }
    if ($button) {
        $text .= $button['label'] . ":\n" . $button['url'] . "\n\n";
    }
    $text .= "--\nBidwraith · Automatic eBay bidding\n" . ($footer !== '' ? $footer . "\n" : '');

    $html = '<!doctype html><html><body style="margin:0;padding:0;background:#faf7f0;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#faf7f0;"><tr><td align="center" style="padding:32px 16px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">'
        . '<tr><td style="padding:0 0 18px;font-family:Georgia,\'Times New Roman\',serif;font-size:20px;color:#221f19;">'
        . '<span style="color:#a8432b;">&#9679;</span> Bidwraith</td></tr>'
        . '<tr><td style="background:#ffffff;border:1px solid #ddd5c4;border-radius:3px;padding:28px 30px;font-family:Georgia,\'Times New Roman\',serif;font-size:16px;line-height:1.55;color:#221f19;">'
        . '<h1 style="margin:0 0 16px;font-size:22px;line-height:1.25;font-weight:600;color:#221f19;">' . $e($heading) . '</h1>';

    foreach ($paragraphs as $p) {
        $html .= '<p style="margin:0 0 14px;">' . $e($p) . '</p>';
    }

    if ($details) {
        $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:6px 0 18px;border-top:1px solid #ddd5c4;">';
        foreach ($details as [$label, $value]) {
            $html .= '<tr><td style="padding:8px 12px 8px 0;border-bottom:1px solid #ddd5c4;font-family:Arial,Helvetica,sans-serif;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#8c8471;vertical-align:top;">' . $e($label) . '</td>'
                . '<td style="padding:8px 0;border-bottom:1px solid #ddd5c4;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#221f19;">' . $e($value) . '</td></tr>';
        }
        $html .= '</table>';
    }

    if ($button) {
        $html .= '<p style="margin:22px 0 6px;"><a href="' . $e($button['url']) . '" style="display:inline-block;background:#a8432b;color:#ffffff;text-decoration:none;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;letter-spacing:.04em;padding:12px 22px;border-radius:3px;">' . $e($button['label']) . '</a></p>'
            . '<p style="margin:14px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#8c8471;">Or paste this link into your browser:<br><span style="word-break:break-all;">' . $e($button['url']) . '</span></p>';
    }

    $html .= '</td></tr>'
        . '<tr><td style="padding:16px 4px 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;color:#8c8471;">'
        . 'Bidwraith &middot; Automatic eBay bidding' . ($footer !== '' ? '<br>' . $e($footer) : '')
        . '</td></tr></table></td></tr></table></body></html>';

    return ['text' => $text, 'html' => $html];
}

function email_tpl(string $subject, array $rendered): array
{
    return ['subject' => $subject, 'text' => $rendered['text'], 'html' => $rendered['html']];
}

// --------------------------------------------------------------------- account

function email_tpl_verify(string $url): array
{
    return email_tpl('Confirm your email address', email_render(
        'Confirm your email address',
        ['Welcome to Bidwraith. Confirm this is your email address to finish setting up your account.', 'This link works once and expires in 24 hours. If you didn\'t create an account, you can ignore this email.'],
        ['label' => 'Confirm email address', 'url' => $url]
    ));
}

function email_tpl_password_reset(string $url): array
{
    return email_tpl('Reset your Bidwraith password', email_render(
        'Reset your password',
        ['Someone asked to reset the password for this account. If that was you, choose a new one with the button below.', 'The link works once and expires in 1 hour. If you didn\'t ask for this, ignore this email — your password stays as it is.'],
        ['label' => 'Choose a new password', 'url' => $url]
    ));
}

function email_tpl_password_changed(): array
{
    return email_tpl('Your Bidwraith password was changed', email_render(
        'Your password was changed',
        ['The password for your Bidwraith account was just changed.', 'If that was you, there\'s nothing to do. If it wasn\'t, reset your password straight away and check that your eBay account is still connected only to you.'],
        ['label' => 'Reset password', 'url' => email_link('forgot_password')]
    ));
}

// --------------------------------------------------------------------- billing

function email_tpl_trial_started(int $days, ?string $endsAt): array
{
    return email_tpl("Your $days-day free trial has started", email_render(
        'Your free trial has started',
        [
            "You have full access to Bidwraith until " . email_date($endsAt) . '.',
            'Connect your eBay account, add an auction, set your max bid and close the tab — Bidwraith places the bid in the last seconds.',
            'We\'ll email you before the trial ends. You can cancel any time from the Billing page.',
        ],
        ['label' => 'Add your first auction', 'url' => email_link('add_auction')]
    ));
}

function email_tpl_subscribed(): array
{
    return email_tpl('Your Bidwraith plan is active', email_render(
        'Your plan is active',
        ['Thanks for subscribing to Bidwraith. Your plan renews monthly and you can change your card or cancel any time from the Billing page.', 'Stripe emails your receipt separately for each payment.'],
        ['label' => 'Open Billing', 'url' => email_link('billing')]
    ));
}

function email_tpl_trial_ending(?string $endsAt, bool $cardOnFile): array
{
    $date = email_date($endsAt);
    return email_tpl("Your Bidwraith trial ends on $date", email_render(
        'Your free trial ends soon',
        $cardOnFile
            ? ["Your free trial ends on $date. After that your plan renews monthly and the card you gave us is charged.", 'If you don\'t want to continue, cancel before then and you won\'t be charged.']
            : ["Your free trial ends on $date. After that you won't be able to add or change bids unless you subscribe.", 'Add a card now to keep going without a gap.'],
        ['label' => $cardOnFile ? 'Manage subscription' : 'Subscribe', 'url' => email_link('billing')]
    ));
}

function email_tpl_payment_failed(): array
{
    return email_tpl('Your Bidwraith payment didn\'t go through', email_render(
        'Your payment didn\'t go through',
        ['We couldn\'t charge your card for your Bidwraith plan. Stripe will try again over the next few days, and your bids keep working meanwhile.', 'To avoid losing access, update your card now.'],
        ['label' => 'Update payment method', 'url' => email_link('billing')]
    ));
}

function email_tpl_cancelling(?string $endsAt): array
{
    $date = email_date($endsAt);
    return email_tpl("Your Bidwraith plan ends on $date", email_render(
        'Your plan is set to end',
        ["Your plan won't renew. You keep full access until $date.", 'Changed your mind? You can resume it from the Billing page before then.'],
        ['label' => 'Open Billing', 'url' => email_link('billing')]
    ));
}

function email_tpl_ended(): array
{
    return email_tpl('Your Bidwraith plan has ended', email_render(
        'Your plan has ended',
        ['You can still log in and see your auctions, but you can\'t add or change bids until you subscribe again. Bids you already scheduled will still fire.'],
        ['label' => 'Subscribe again', 'url' => email_link('billing')]
    ));
}

// ------------------------------------------------------------------------ bids

/**
 * One email per auction summarising what just happened to its bids.
 *
 * @param array{id: int, title: ?string, item_id: string, end_time: ?string} $auction
 * @param array<int, array{seconds_before: int, ok: bool, amount: ?float, message: string}> $outcomes
 */
function email_tpl_bid_alert(array $auction, array $outcomes, string $currency): array
{
    $title = trim((string) ($auction['title'] ?? '')) !== '' ? $auction['title'] : 'item ' . $auction['item_id'];
    $short = mb_strlen($title) > 60 ? mb_substr($title, 0, 57) . '…' : $title;

    $placed = count(array_filter($outcomes, fn ($o) => $o['ok']));
    $failed = count($outcomes) - $placed;

    if ($failed === 0) {
        $subject = ($placed === 1 ? 'Bid placed' : "$placed bids placed") . ": $short";
        $heading = $placed === 1 ? 'Your bid was placed' : 'Your bids were placed';
        $intro = 'eBay accepted ' . ($placed === 1 ? 'your bid' : 'your bids') . ' on ' . $title . '. A placed bid is not a win — eBay decides that when the auction ends, and another bidder\'s maximum may be higher.';
    } elseif ($placed === 0) {
        $subject = 'Bid FAILED: ' . $short;
        $heading = ($failed === 1 ? 'Your bid didn\'t go through' : 'Your bids didn\'t go through');
        $intro = 'Bidwraith tried to bid on ' . $title . ' but it didn\'t work. The reason is below — if the auction is still running you may be able to fix it and bid yourself on eBay.';
    } else {
        $subject = "Bids on $short: $placed placed, $failed failed";
        $heading = 'Some of your bids didn\'t go through';
        $intro = "Bidwraith placed $placed bid" . ($placed === 1 ? '' : 's') . " on $title but $failed didn't work. Details below.";
    }

    $details = [];
    foreach ($outcomes as $o) {
        $amount = $o['amount'] !== null ? $currency . ' ' . number_format($o['amount'], 2) : 'amount not determined';
        $details[] = [
            format_seconds_before((int) $o['seconds_before']) . ' before end',
            $amount . ' — ' . ($o['ok'] ? 'placed' : 'FAILED: ' . $o['message']),
        ];
    }
    if (!empty($auction['end_time'])) {
        $details[] = ['Auction ends', date('j M Y, H:i T', strtotime($auction['end_time']))];
    }

    return email_tpl($subject, email_render(
        $heading,
        [$intro],
        ['label' => 'See the full bid log', 'url' => email_link('auction?id=' . (int) $auction['id'])],
        $details,
        'You get these because email alerts are on. Turn them off under eBay account → Email notifications.'
    ));
}

function email_tpl_ebay_expiring(int $daysLeft, ?string $expiresAt): array
{
    $date = $expiresAt !== null && strtotime($expiresAt) ? date('j M Y', strtotime($expiresAt)) : 'soon';

    if ($daysLeft <= 0) {
        return email_tpl('Your eBay connection has expired — bids will fail', email_render(
            'Your eBay connection has expired',
            ['Bidwraith can no longer bid for you on eBay, and you have bids scheduled that will fail.', 'Reconnect your eBay account now — it takes a minute.'],
            ['label' => 'Reconnect eBay', 'url' => email_link('connect_ebay')]
        ));
    }

    return email_tpl("Your eBay connection expires on $date", email_render(
        'Your eBay connection expires soon',
        ["The link between Bidwraith and your eBay account expires on $date, and you have bids scheduled. Once it expires they will fail.", 'Reconnect now to renew it.'],
        ['label' => 'Reconnect eBay', 'url' => email_link('connect_ebay')]
    ));
}

// ----------------------------------------------------------------------- admin

function email_tpl_admin_note(string $subject, string $body, ?array $button = null): array
{
    return email_tpl('[Bidwraith] ' . $subject, email_render($subject, [$body], $button));
}

function email_tpl_test(string $transportNote): array
{
    return email_tpl('Bidwraith test email', email_render(
        'Email is working',
        ['This is a test message from your Bidwraith installation. If you can read it, sending works.', 'Sent using: ' . $transportNote . '.']
    ));
}
