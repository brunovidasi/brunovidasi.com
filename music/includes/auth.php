<?php

/**
 * One account, one owner: the only address that can ever hold an account is
 * owner_email in the config. /setup creates it once and refuses afterwards.
 */

const DUMMY_PASSWORD_HASH = '$2y$12$usesomesillystringfoeioaXjJQFHqfuz7cFwsw.0qNBHyzGXi3Lm';

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

        // The account was deleted, or the owner address changed in the config.
        if ($user && !is_owner_email($user['email'])) {
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
        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'] ?? url('admin');
        header('Location: ' . url('login'));
        exit;
    }

    return $user;
}

function is_owner_email(string $email): bool
{
    return owner_email() === null || strtolower($email) === owner_email();
}

function owner_account_exists(): bool
{
    return (bool) db()->query('SELECT 1 FROM users LIMIT 1')->fetchColumn();
}

/** Returns null on success, or the error to show. */
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
        // Never echo the configured address: anyone can reach this page before the account exists.
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

/** Returns null on success, or the error to show. */
function attempt_login(string $email, string $password): ?string
{
    $email = strtolower(trim($email));

    $stmt = db()->prepare('SELECT id, email, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    // Verified against a dummy hash when there's no such account, so a wrong
    // address takes as long to answer as a wrong password.
    $hash = $row['password_hash'] ?? DUMMY_PASSWORD_HASH;

    if (!password_verify($password, $hash) || !$row) {
        return 'Wrong email or password.';
    }
    if (!is_owner_email($row['email'])) {
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

/** Seconds left before another sign-in may be tried: five free tries, then a doubling wait. */
function login_cooldown(): int
{
    $fails = (int) ($_SESSION['login_fails'] ?? 0);
    $last = (int) ($_SESSION['login_last_fail'] ?? 0);

    if ($fails < 5) {
        return 0;
    }

    $wait = min(300, 5 * (2 ** ($fails - 5)));

    return max(0, $last + $wait - time());
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
