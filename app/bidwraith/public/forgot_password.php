<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (current_user()) {
    redirect('dashboard');
}

$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim((string) ($_POST['email'] ?? ''));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        send_password_reset_email($email);
    }
    // The same answer whether or not an account exists, so this form can't be used to
    // find out who is a customer.
    $sent = true;
}

$pageTitle = 'Reset password';
require __DIR__ . '/../includes/layout_top.php';
?>
<div class="auth-box">
    <h1>Reset your password</h1>
    <?php if ($sent): ?>
        <p>If there's an account for that address, a reset link is on its way. It's good for one hour.</p>
        <p class="hint">Nothing after a few minutes? Check spam.</p>
        <div class="switch"><a href="login">Back to log in</a></div>
    <?php else: ?>
        <p class="hint">Enter your account's email and we'll send you a link to choose a new password.</p>
        <form class="stacked" method="post">
            <?= csrf_field() ?>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autofocus>
            <button type="submit">Send reset link</button>
        </form>
        <div class="switch"><a href="login">Back to log in</a></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
