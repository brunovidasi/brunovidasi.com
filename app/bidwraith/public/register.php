<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Public sign-up is controlled per environment by 'allow_registration' in config:
// open in development, closed in production so a live, money-spending app doesn't
// carry an open sign-up form. register_user() below is left intact either way.
if (!registration_allowed()) {
    http_response_code(404);
    require __DIR__ . '/../includes/layout_top.php';
    echo '<p>Page not found.</p>';
    require __DIR__ . '/../includes/layout_bottom.php';
    exit;
}

if (current_user()) {
    redirect('dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    [$ok, $result] = register_user($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($ok) {
        $_SESSION['user_id'] = $result;
        redirect('dashboard.php');
    }
    $error = $result;
}

$pageTitle = 'Create account';
require __DIR__ . '/../includes/layout_top.php';
?>
<div class="auth-box">
    <h1>Create account</h1>
    <?php if ($error): ?><div class="flash flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form class="stacked" method="post">
        <?= csrf_field() ?>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" minlength="8" required>
        <div class="hint">At least 8 characters.</div>

        <button type="submit">Create account</button>
    </form>
    <div class="switch">Already have an account? <a href="login.php">Log in</a></div>
</div>
<?php require __DIR__ . '/../includes/layout_bottom.php'; ?>
