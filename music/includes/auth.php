<?php

/**
 * One account, one owner. There is no registration page and no second user:
 * this admin manages one person's shelf, and the only address that may ever
 * hold an account is owner_email in the config (which lives outside the repo).
 *
 * /setup creates that account the first time, and refuses once it exists.
 */

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT id, email, created_at, last_login_at FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;

        // The owner address changed in the config, or the account was deleted:
        // either way this session no longer belongs to anyone who may be here.
        if ($user && owner_email() !== null && strtolower($user['email']) !== owner_email()) {
            logout_user();
            return null;
        }
        if (!$user) {
            logout_user();
        }
    }

    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        // Come back to where they were headed once they've signed in.
        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'] ?? url('admin');
        header('Location: ' . url('login'));
        exit;
    }
    return $user;
}

function owner_account_exists(): bool
{
    return (bool) db()->query('SELECT 1 FROM users LIMIT 1')->fetchColumn();
}

/** Creates the owner account. Returns null on success, or an error to show. */
function create_owner_account(string $email, string $password, string $confirm): ?string
{
    $email = strtolower(trim($email));

    if (owner_account_exists()) {
        return 'An account already exists. Sign in instead.';
    }
    if (owner_email() === null) {
        return "No owner_email is set in the config, so there is no address to create an account for.";
    }
    if ($email !== owner_email()) {
        // Deliberately does not echo the configured address back: this page is
        // reachable by anyone in the window before the account exists.
        return 'That is not the address this collection belongs to.';
    }
    if (strlen($password) < 10) {
        return 'Password must be at least 10 characters.';
    }
    if ($password !== $confirm) {
        return 'The two passwords do not match.';
    }

    db()->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)')
        ->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) db()->lastInsertId();

    return null;
}

/** Returns null on success, or an error message to show the user. */
function attempt_login(string $email, string $password): ?string
{
    $email = strtolower(trim($email));

    $stmt = db()->prepare('SELECT id, email, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    // password_verify against a dummy hash when there is no such account, so a
    // wrong address and a wrong password take the same time to answer and the
    // page can't be used to discover which address owns the collection.
    $hash = $row['password_hash'] ?? '$2y$12$usesomesillystringfoeioaXjJQFHqfuz7cFwsw.0qNBHyzGXi3Lm';

    if (!password_verify($password, $hash) || !$row) {
        return 'Wrong email or password.';
    }
    if (owner_email() !== null && strtolower($row['email']) !== owner_email()) {
        return 'That account is no longer the owner of this collection.';
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $row['id'];
    db()->prepare("UPDATE users SET last_login_at = datetime('now') WHERE id = ?")->execute([$row['id']]);

    return null;
}

function logout_user(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Rate-limits sign-in attempts per session, so the login form can't be hammered
 * from one browser. Returns the seconds left to wait, or 0 when it may proceed.
 */
function login_cooldown(): int
{
    $fails = (int) ($_SESSION['login_fails'] ?? 0);
    $last = (int) ($_SESSION['login_last_fail'] ?? 0);

    if ($fails < 5) {
        return 0;
    }

    $wait = min(300, 5 * (2 ** ($fails - 5)));
    $left = $last + $wait - time();

    return $left > 0 ? $left : 0;
}

function record_login_failure(): void
{
    $_SESSION['login_fails'] = (int) ($_SESSION['login_fails'] ?? 0) + 1;
    $_SESSION['login_last_fail'] = time();
}

function clear_login_failures(): void
{
    unset($_SESSION['login_fails'], $_SESSION['login_last_fail']);
}
