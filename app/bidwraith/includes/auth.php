<?php

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;
    if ($user === null) {
        // Deactivated accounts are filtered out here too, so an admin switching a
        // user off ends their existing session on their next request.
        $stmt = db()->prepare('SELECT id, email, is_admin, created_at FROM users WHERE id = ? AND is_active = 1');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($user) {
            $user['is_admin'] = (bool) $user['is_admin'];
        } else {
            logout_user();
        }
    }

    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: login.php');
        exit;
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if (!$user['is_admin']) {
        http_response_code(403);
        die('Forbidden.');
    }
    return $user;
}

function register_user(string $email, string $password): array
{
    $email = trim(strtolower($email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Enter a valid email address.'];
    }
    if (strlen($password) < 8) {
        return [false, 'Password must be at least 8 characters.'];
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return [false, 'An account with that email already exists.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = db()->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
    $stmt->execute([$email, $hash]);

    return [true, (int) db()->lastInsertId()];
}

/** Returns null on success, or an error message to show the user. */
function attempt_login(string $email, string $password): ?string
{
    $email = trim(strtolower($email));

    $stmt = db()->prepare('SELECT id, password_hash, is_active FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($password, $row['password_hash'])) {
        return 'Invalid email or password.';
    }
    if (!$row['is_active']) {
        return 'This account has been deactivated.';
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $row['id'];

    return null;
}

function set_user_active(int $userId, bool $active): void
{
    db()->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$active ? 1 : 0, $userId]);
}

function logout_user(): void
{
    $_SESSION = [];
    session_destroy();
}
