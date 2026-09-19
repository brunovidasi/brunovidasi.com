<?php
/**
 * Email verification and password reset: the emailed single-use links, and the
 * functions that send them.
 *
 * A link's token is 32 random bytes; only its SHA-256 goes in the database, so a copy
 * of the database can't be used to take over an account. Each is single-use and
 * expires, and asking for a new one voids the old.
 */

const VERIFY_TOKEN_TTL = 24 * 3600;
const RESET_TOKEN_TTL = 3600;

/** Admins are exempt: the first one is created by hand, before mail may even be set up. */
function user_email_verified(array $user): bool
{
    return !empty($user['is_admin']) || !empty($user['email_verified_at']);
}

function mark_email_verified(int $userId): void
{
    db()->prepare("UPDATE users SET email_verified_at = COALESCE(email_verified_at, datetime('now')) WHERE id = ?")
        ->execute([$userId]);
}

function create_user_token(int $userId, string $purpose, int $ttlSeconds): string
{
    db()->prepare('DELETE FROM user_tokens WHERE user_id = ? AND purpose = ? AND used_at IS NULL')
        ->execute([$userId, $purpose]);

    $token = bin2hex(random_bytes(32));
    db()->prepare('INSERT INTO user_tokens (user_id, purpose, token_hash, expires_at) VALUES (?, ?, ?, ?)')
        ->execute([$userId, $purpose, hash('sha256', $token), gmdate('Y-m-d H:i:s', time() + $ttlSeconds)]);

    return $token;
}

/** The user a still-valid token belongs to, without using it up. */
function peek_user_token(string $token, string $purpose): ?int
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }

    $stmt = db()->prepare('SELECT user_id FROM user_tokens WHERE token_hash = ? AND purpose = ? AND used_at IS NULL AND expires_at > ?');
    $stmt->execute([hash('sha256', $token), $purpose, gmdate('Y-m-d H:i:s')]);
    $id = $stmt->fetchColumn();

    return $id === false ? null : (int) $id;
}

/** Uses a token up. Returns its user, or null if it was invalid, expired or already used. */
function consume_user_token(string $token, string $purpose): ?int
{
    $userId = peek_user_token($token, $purpose);
    if ($userId === null) {
        return null;
    }

    // Guarded on used_at so two simultaneous clicks can't both succeed.
    $stmt = db()->prepare("UPDATE user_tokens SET used_at = datetime('now') WHERE token_hash = ? AND purpose = ? AND used_at IS NULL");
    $stmt->execute([hash('sha256', $token), $purpose]);

    return $stmt->rowCount() === 1 ? $userId : null;
}

/**
 * Stops the forms that send an email being used to flood someone's inbox: at most one
 * per cooldown and a handful per hour, per person and kind.
 */
function email_rate_limited(int $userId, string $kind, int $cooldownSeconds = 60, int $perHour = 5): bool
{
    $stmt = db()->prepare("
        SELECT COUNT(*) AS hour_count,
               SUM(CASE WHEN created_at > datetime('now', ?) THEN 1 ELSE 0 END) AS recent
        FROM email_outbox
        WHERE user_id = ? AND kind = ? AND created_at > datetime('now', '-1 hour')
    ");
    $stmt->execute(['-' . $cooldownSeconds . ' seconds', $userId, $kind]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (int) $row['recent'] > 0 || (int) $row['hour_count'] >= $perHour;
}

/**
 * While developing with the 'log' transport nothing is delivered, so surface the link
 * where the developer can click it instead of making them dig through mail.log.
 */
function dev_mail_hint(string $what, string $url): void
{
    if (mail_transport() === 'log' && !is_production()) {
        set_flash('success', "Development mode — no email was sent. $what link: $url");
    }
}

/** @return bool true if an email was queued; false if rate-limited or already verified */
function send_verification_email(array $user): bool
{
    if (user_email_verified($user) || email_rate_limited((int) $user['id'], 'verify')) {
        return false;
    }

    $url = email_link('verify_email?token=' . create_user_token((int) $user['id'], 'verify', VERIFY_TOKEN_TTL));
    queue_email($user['email'], email_tpl_verify($url), 'verify', (int) $user['id']);
    dev_mail_hint('Verification', $url);

    return true;
}

/**
 * Emails a reset link if the address belongs to an active account. Says nothing about
 * whether it did — the page answers identically either way, so it can't be used to
 * find out who has an account.
 */
function send_password_reset_email(string $email): void
{
    $stmt = db()->prepare('SELECT id, email FROM users WHERE email = ? AND is_active = 1');
    $stmt->execute([trim(strtolower($email))]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || email_rate_limited((int) $user['id'], 'reset')) {
        return;
    }

    $url = email_link('reset_password?token=' . create_user_token((int) $user['id'], 'reset', RESET_TOKEN_TTL));
    queue_email($user['email'], email_tpl_password_reset($url), 'reset', (int) $user['id']);
    dev_mail_hint('Reset', $url);
}

/** Sets a new password from a reset link. Returns null on success, else why not. */
function reset_password_with_token(string $token, string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }

    $userId = consume_user_token($token, 'reset');
    if ($userId === null) {
        return 'That link has expired or was already used. Request a new one.';
    }

    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
    // Reading the link proves they control the address.
    mark_email_verified($userId);
    db()->prepare("UPDATE user_tokens SET used_at = datetime('now') WHERE user_id = ? AND used_at IS NULL")->execute([$userId]);

    $email = db()->prepare('SELECT email FROM users WHERE id = ?');
    $email->execute([$userId]);
    queue_email((string) $email->fetchColumn(), email_tpl_password_changed(), 'password_changed', $userId);

    return null;
}

/** Everything that should happen when someone new signs up. */
function on_user_registered(int $userId, string $email): void
{
    send_verification_email(['id' => $userId, 'email' => $email, 'is_admin' => 0, 'email_verified_at' => null]);
    notify_admin('New sign-up', "$email just created a Bidwraith account.");
}

/** Emails the owner about something worth knowing. Quietly does nothing without an owner address. */
function notify_admin(string $subject, string $body, ?string $dedupeKey = null): void
{
    $owner = owner_email();
    if ($owner === null || (mail_config()['admin_notifications'] ?? true) === false) {
        return;
    }

    queue_email($owner, email_tpl_admin_note($subject, $body, ['label' => 'Open the admin dashboard', 'url' => email_link('admin_subscriptions')]), 'admin', null, $dedupeKey);
}
