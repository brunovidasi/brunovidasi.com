<?php

/** Escape for HTML. Short name because it appears on nearly every line of markup. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * $path is normally a bare page name ('admin', 'admin_items?x=1') and gets run
 * through url() to become a full app path. But login.php also passes this an
 * already-absolute path recovered from $_SERVER['REQUEST_URI'] (the page the
 * user was on before being sent to log in) — running that through url() again
 * would double the mount prefix, e.g. '/music/music/admin' in production. The
 * app_path() check tells the two cases apart without needing a second
 * parameter at every other call site.
 */
function redirect(string $path): never
{
    $isAbsolute = str_starts_with($path, 'http') || str_starts_with($path, app_path());
    header('Location: ' . ($isAbsolute ? $path : url($path)));
    exit;
}

/** A one-shot message shown after a redirect ("Saved.", "Couldn't do that."). */
function flash(string $message, string $kind = 'ok'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'kind' => $kind];
}

function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $flashes;
}

/** The current page's name, used to light up the admin nav. */
function current_page(): string
{
    return basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
}

function post(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function query(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

/** Empty strings become NULL, so "cleared in the admin" and "never set" agree. */
function nullable(string $value): ?string
{
    return $value === '' ? null : $value;
}

function slugify(string $text): string
{
    $slug = strtolower(trim($text));
    // Strip accents so "Beyoncé" becomes "beyonce" rather than "beyonc".
    $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
    if ($transliterated !== false) {
        $slug = $transliterated;
    }
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($slug));

    return trim($slug, '-') ?: 'item';
}

/**
 * Timestamps written by SQLite's datetime('now') are UTC and carry no zone, so
 * strtotime() would read them as local and put every sync hours into the past
 * or future. Anything with its own offset (date('c'), Discogs' dates) is left
 * alone. Returns a Unix timestamp, or null if it can't be read.
 */
function to_timestamp(?string $value): ?int
{
    if (!$value) {
        return null;
    }

    $bareDateTime = (bool) preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/', $value);
    $time = strtotime($bareDateTime ? $value . ' UTC' : $value);

    return $time === false ? null : $time;
}

function format_date(?string $value, string $format = 'j M Y'): string
{
    $time = to_timestamp($value);

    return $time === null ? (string) $value : date($format, $time);
}

/** "3 minutes ago" — used for the last-sync line in the admin. */
function time_ago(?string $value): string
{
    $time = to_timestamp($value);
    if ($time === null) {
        return 'never';
    }

    $seconds = time() - $time;
    if ($seconds < 60) {
        return 'just now';
    }

    foreach ([[86400 * 30, 'month'], [86400 * 7, 'week'], [86400, 'day'], [3600, 'hour'], [60, 'minute']] as [$size, $unit]) {
        if ($seconds >= $size) {
            $count = (int) floor($seconds / $size);
            return "$count $unit" . ($count === 1 ? '' : 's') . ' ago';
        }
    }

    return 'just now';
}

/** Ends the request with a JSON body. Used by the API and the sync poller. */
function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Public API responses may be cached briefly by the browser: the data only
 * changes when a sync runs, and the shelf page asks for all of it at once.
 */
function json_cache_headers(int $seconds = 300): void
{
    header('Cache-Control: public, max-age=' . $seconds);
}

/**
 * A short token that changes whenever what a visitor sees does: a sync, or an
 * edit to any item in the admin. The pages carry it and the browser keys its
 * cache and its API requests on it, so a new cover or disc image shows up on
 * the next page load instead of when the old copy expires.
 *
 * The card-building code counts too: a new field on the cards changes what the
 * API returns without any data changing, and the old copy would otherwise be
 * served for as long as the browser kept it.
 */
function data_version(): string
{
    $latest = db()->query('SELECT MAX(updated_at) FROM items')->fetchColumn();
    $shape = (int) @filemtime(__DIR__ . '/items.php');

    return substr(md5(setting('last_successful_sync', '') . '|' . $latest . '|' . $shape), 0, 10);
}

/* ---------- Lookups the admin pages share ---------- */

function all_artists(): array
{
    return db()->query('SELECT * FROM artists ORDER BY position, name')->fetchAll();
}

function artist_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM artists WHERE slug = ?');
    $stmt->execute([$slug]);

    return $stmt->fetch() ?: null;
}

function artist_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM artists WHERE id = ?');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

function eras_for_artist(int $artistId): array
{
    $stmt = db()->prepare('SELECT * FROM eras WHERE artist_id = ? ORDER BY position, name');
    $stmt->execute([$artistId]);

    return $stmt->fetchAll();
}

function era_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM eras WHERE id = ?');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

/** How many items sit in each era, keyed by era id. */
function era_counts(int $artistId): array
{
    $stmt = db()->prepare("
        SELECT era_id, COUNT(*) AS n
          FROM items
         WHERE artist_id = ? AND source = 'collection' AND missing_since IS NULL
         GROUP BY era_id
    ");
    $stmt->execute([$artistId]);

    return array_column($stmt->fetchAll(), 'n', 'era_id');
}
