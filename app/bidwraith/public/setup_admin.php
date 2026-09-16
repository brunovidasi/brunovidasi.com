<?php
/**
 * One-time creation of the first admin account.
 *
 * Needed because public registration is closed in production, and the server has no
 * shell to seed a user from. It disables itself permanently the moment any account
 * exists, so it can stay in the repo — there is no file to remember to delete, and
 * a redeploy can't silently reopen it.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$userCount = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();

if ($userCount > 0) {
    http_response_code(404);
    $pageTitle = 'Not found';
    require __DIR__ . '/../includes/layout_top.php';
    echo '<p>Page not found.</p>';
    require __DIR__ . '/../includes/layout_bottom.php';
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($password !== ($_POST['password_confirm'] ?? '')) {
        $error = 'The two passwords do not match.';
    } else {
        [$ok, $result] = register_user($email, $password);

        if (!$ok) {
            $error = $result;
        } else {
            db()->prepare('UPDATE users SET is_admin = 1, is_active = 1 WHERE id = ?')->execute([$result]);
            attempt_login($email, $password);
            set_flash('success', 'Admin account created. Public sign-up stays closed; this setup page is now disabled.');
            redirect('dashboard.php');
        }
    }
}

$pageTitle = 'Create admin account';
require __DIR__ . '/../includes/layout_top.php';
?>
<div class="auth-box">
    <h1>Create admin account</h1>
    <p class="hint">No accounts exist yet. This page creates the first one as an
       administrator, then disables itself permanently.</p>
    <?php if ($error): ?><div class="flash flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form class="stacked" method="post">
        <?= csrf_field() ?>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="8">

        <label for="password_confirm">Confirm password</label>
        <input type="password" id="password_confirm" name="password_confirm" required minlength="8">

        <button type="submit">Create account</button>
    </form>
</div>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
