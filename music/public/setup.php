<?php

/**
 * Creates the one account, once, and only for the config's owner_email.
 * Afterwards it sends everyone to sign in.
 */

require_once __DIR__ . '/../includes/admin.php';

if (owner_account_exists()) {
    redirect('login');
}

$error = null;

if (is_post()) {
    csrf_verify();
    $error = create_owner_account(post('email'), $_POST['password'] ?? '', $_POST['password_confirm'] ?? '');

    if ($error === null) {
        flash('Account created. This is the only account that will ever exist.');
        redirect('admin');
    }
}

auth_header('Set up');
?>
    <h1>Claim the collection</h1>
    <p class="sub">One account, created once, for the address set in the config. Nobody else can register.</p>

    <?php if ($error !== null): ?>
      <?= flash_box($error, 'error') ?>
    <?php endif; ?>

    <?php if (owner_email() === null): ?>
      <div class="flash error">No <code>owner_email</code> is set in the config, so there is no address to create an account for. Add one and reload.</div>
    <?php else: ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="field">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" autocomplete="username" value="<?= e(post('email')) ?>" required autofocus>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" autocomplete="new-password" required>
          <div class="hint">At least 10 characters.</div>
        </div>
        <div class="field">
          <label for="password_confirm">Password again</label>
          <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
        </div>
        <button type="submit" class="gold">Create the account</button>
      </form>
    <?php endif; ?>
<?php auth_footer(); ?>
