<?php

/**
 * Creates the one account, once.
 *
 * This page is reachable by anyone in the window between a fresh deploy and the
 * account existing, so it will only ever create an account for the address in
 * the config's owner_email — which lives outside the repo. After that it
 * refuses and sends people to the sign-in page.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

if (owner_account_exists()) {
    redirect('login');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $error = create_owner_account(post('email'), $_POST['password'] ?? '', $_POST['password_confirm'] ?? '');

    if ($error === null) {
        flash('Account created. This is the only account that will ever exist.');
        redirect('admin');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Set up — Bruno's Music Collection</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="<?= e(url('../assets/favicon.ico')) ?>">
<link rel="stylesheet" href="<?= e(url('css/admin.css')) ?>">
</head>
<body>

<div class="auth">
  <div class="card">
    <h1>Claim the collection</h1>
    <p class="sub">One account, created once, for the address set in the config. Nobody else can register.</p>

    <?php if ($error !== null): ?>
      <div class="flash error"><?= e($error) ?></div>
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
  </div>
</div>

</body>
</html>
