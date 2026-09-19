<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$token = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string) ($_POST['token'] ?? '') : get_param('token');
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (($_POST['password'] ?? '') !== ($_POST['password_confirm'] ?? '')) {
        $error = 'The two passwords do not match.';
    } else {
        $error = reset_password_with_token($token, (string) ($_POST['password'] ?? ''));
        if ($error === null) {
            set_flash('success', 'Password updated. Log in with your new password.');
            redirect('login');
        }
    }
}

// Checked without using the link up: it is only spent when the new password is saved,
// so a mail scanner that opens links can't burn it before the person gets there.
$valid = peek_user_token($token, 'reset') !== null;

$pageTitle = 'Choose a new password';
require __DIR__ . '/../includes/layout_top.php';
?>
<div class="auth-box">
    <?php if (!$valid): ?>
        <h1>That link didn't work</h1>
        <p>It has expired or was already used.</p>
        <p><a class="btn" href="forgot_password">Request a new link</a></p>
    <?php else: ?>
        <h1>Choose a new password</h1>
        <?php if ($error): ?><div class="flash flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form class="stacked" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <label for="password">New password</label>
            <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password" autofocus>
            <div class="hint">At least 8 characters.</div>

            <label for="password_confirm">Confirm new password</label>
            <input type="password" id="password_confirm" name="password_confirm" minlength="8" required autocomplete="new-password">

            <button type="submit">Save password</button>
        </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
