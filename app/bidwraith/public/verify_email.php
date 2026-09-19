<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$token = get_param('token');

// The link from the email. Works whether or not they're logged in — it's often opened
// on a different device from the one they signed up on.
if ($token !== '') {
    $userId = consume_user_token($token, 'verify');

    if ($userId !== null) {
        mark_email_verified($userId);
        set_flash('success', 'Email confirmed. Thanks!');
        redirect(current_user() ? (billing_enabled() ? 'billing' : 'dashboard') : 'login');
    }

    $pageTitle = 'Confirm email';
    require __DIR__ . '/../includes/layout_top.php';
    ?>
    <div class="auth-box">
        <h1>That link didn't work</h1>
        <p>It has expired or was already used. <?= current_user() ? 'Request a new one below.' : 'Log in and request a new one.' ?></p>
        <p><a class="btn" href="<?= current_user() ? 'verify_email' : 'login' ?>"><?= current_user() ? 'Send a new link' : 'Log in' ?></a></p>
    </div>
    <?php
    require __DIR__ . '/../includes/layout_bottom.php';
    exit;
}

$user = require_login();

if (user_email_verified($user)) {
    redirect('dashboard');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (send_verification_email($user)) {
        // Only replace the message if nothing (the dev-mode link) has already set one.
        if (empty($_SESSION['flash'])) {
            set_flash('success', 'A new link is on its way.');
        }
    } else {
        set_flash('error', 'A link was sent a moment ago. Give it a minute, and check your spam folder.');
    }
    redirect('verify_email');
}

$pageTitle = 'Confirm your email';
require __DIR__ . '/../includes/layout_top.php';
?>
<div class="auth-box">
    <h1>Check your email</h1>
    <p>We sent a confirmation link to <strong><?= htmlspecialchars($user['email']) ?></strong>. Open it to finish setting up your account. It's good for 24 hours.</p>
    <p class="hint">Nothing there? Look in spam, and make sure the address above is right.</p>
    <form method="post">
        <?= csrf_field() ?>
        <button type="submit" class="secondary">Send a new link</button>
    </form>
</div>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
