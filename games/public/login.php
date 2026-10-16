<?php

require_once __DIR__ . '/../includes/admin.php';

if (!owner_account_exists()) {
    redirect('setup');
}

if (current_user()) {
    redirect('admin');
}

$error = null;
$cooldown = login_cooldown();

if (is_post()) {
    csrf_verify();

    if ($cooldown > 0) {
        $error = "Too many attempts. Try again in $cooldown seconds.";
    } else {
        $error = attempt_login(post('email'), $_POST['password'] ?? '');

        if ($error === null) {
            clear_login_failures();
            $next = $_SESSION['login_redirect'] ?? url('admin');
            unset($_SESSION['login_redirect']);
            // Only back inside this app, never to another site.
            redirect(str_starts_with($next, app_path()) ? $next : url('admin'));
        }

        record_login_failure();
        $cooldown = login_cooldown();
    }
}

auth_header('Sign in');
?>
    <div class="auth-mark" aria-hidden="true">
      <svg viewBox="0 0 64 40" width="64" height="40"><rect x="1" y="6" width="62" height="28" rx="14" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M16 14v12M10 20h12" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><circle cx="44" cy="16" r="2.6" fill="currentColor"/><circle cx="50" cy="22" r="2.6" fill="currentColor"/></svg>
    </div>
    <h1><?= e(site_settings()['name']) ?></h1>
    <p class="sub">Sign in to look after the shelf.</p>

    <?php if ($error !== null): ?>
      <?= flash_box($error, 'error') ?>
    <?php endif; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" autocomplete="username" value="<?= e(post('email')) ?>" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="primary"<?= $cooldown > 0 ? ' disabled' : '' ?>>Sign in</button>
    </form>
<?php auth_footer(); ?>
