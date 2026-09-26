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
    <svg class="disc" viewBox="0 0 100 100" aria-hidden="true">
      <circle cx="50" cy="50" r="48" fill="#0B0A08"/>
      <circle cx="50" cy="50" r="40" fill="none" stroke="#2a2620" stroke-width="1.5"/>
      <circle cx="50" cy="50" r="30" fill="none" stroke="#2a2620" stroke-width="1.5"/>
      <circle cx="50" cy="50" r="16" fill="#C99A2E"/>
      <circle cx="50" cy="50" r="3.5" fill="#0B0A08"/>
    </svg>

    <h1>Bruno's Music Collection</h1>
    <p class="sub">Sign in to manage the shelf.</p>

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
      <button type="submit" class="gold"<?= $cooldown > 0 ? ' disabled' : '' ?>>Sign in</button>
    </form>
<?php auth_footer(); ?>
