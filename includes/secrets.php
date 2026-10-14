<?php

/**
 * The API keys and sign-ins each platform needs (credentials), kept in the
 * database encrypted with the config's app_key, so a copy of the database alone
 * gives none of them away. The admin only ever says whether one is set and when
 * it was saved, never shows it.
 */

function secrets_ready(): bool
{
    return function_exists('sodium_crypto_secretbox') && strlen(secrets_key() ?? '') === SODIUM_CRYPTO_SECRETBOX_KEYBYTES;
}

function secrets_key(): ?string
{
    $hex = app_key();
    if ($hex === '' || !ctype_xdigit($hex) || strlen($hex) !== 64) {
        return null;
    }

    return hex2bin($hex);
}

function encrypt_secret(string $plain): string
{
    $key = secrets_key();
    if ($key === null) {
        throw new RuntimeException('No app_key in the config, so sign-ins cannot be saved. See config.example.php.');
    }

    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

    return base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, $key));
}

function decrypt_secret(string $stored): ?string
{
    $key = secrets_key();
    $raw = base64_decode($stored, true);
    if ($key === null || $raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
        return null;
    }

    $plain = sodium_crypto_secretbox_open(
        substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
        substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
        $key
    );

    return $plain === false ? null : $plain;
}

/** A platform's saved value ('api_key', 'refresh_token'…), or null. */
function credential(string $platform, string $name): ?string
{
    $stmt = db()->prepare('SELECT value_enc FROM credentials WHERE platform = ? AND name = ?');
    $stmt->execute([$platform, $name]);
    $enc = $stmt->fetchColumn();

    return $enc === false ? null : decrypt_secret($enc);
}

/** Saves one, or forgets it when $value is null or ''. Renewed tokens go through here the moment they arrive. */
function set_credential(string $platform, string $name, ?string $value): void
{
    if ($value === null || $value === '') {
        db()->prepare('DELETE FROM credentials WHERE platform = ? AND name = ?')->execute([$platform, $name]);
        return;
    }

    db()->prepare("
        INSERT INTO credentials (platform, name, value_enc) VALUES (?, ?, ?)
        ON CONFLICT(platform, name) DO UPDATE SET value_enc = excluded.value_enc, updated_at = datetime('now')
    ")->execute([$platform, $name, encrypt_secret($value)]);
}

/** name => updated_at of what is saved for a platform, without the values. */
function credential_dates(string $platform): array
{
    $stmt = db()->prepare('SELECT name, updated_at FROM credentials WHERE platform = ?');
    $stmt->execute([$platform]);

    return array_column($stmt->fetchAll(), 'updated_at', 'name');
}

function forget_credentials(string $platform): void
{
    db()->prepare('DELETE FROM credentials WHERE platform = ?')->execute([$platform]);
}

/** A random token for the cron URL. */
function new_token(): string
{
    return bin2hex(random_bytes(24));
}
