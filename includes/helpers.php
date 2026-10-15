<?php

/** Escape for HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* ---------- Requests and responses ---------- */

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

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Empty strings become NULL, so "cleared in the admin" and "never set" agree. */
function nullable(string $value): ?string
{
    return $value === '' ? null : $value;
}

/** Takes a page name ('admin/games?x=1') or a path already under the app ('/games/admin'). */
function redirect(string $path): never
{
    $isAbsolute = str_starts_with($path, 'http') || str_starts_with($path, app_path());
    header('Location: ' . ($isAbsolute ? $path : url($path)));
    exit;
}

/** Ends the request with the site's own 404 page. */
function not_found(): never
{
    http_response_code(404);
    require __DIR__ . '/../public/404.php';
    exit;
}

/** A one-shot message shown on the next page. */
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

/** The current page's name as its URL has it: public/admin_games.php is 'admin/games'. */
function current_page(): string
{
    return preg_replace('/^admin_/', 'admin/', basename($_SERVER['SCRIPT_NAME'] ?? '', '.php'));
}

function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * The public API's answer: JSON that is anonymous and cacheable, with an ETag of
 * data_version(), so a browser asks again cheaply and gets a 304 until
 * something changes. ?v= on the request (the page adds it) makes a fresh copy.
 */
function send_api_json(string $json): never
{
    $etag = '"' . data_version() . '"';
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: public, max-age=60, must-revalidate');
    header('ETag: ' . $etag);

    if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
        http_response_code(304);
        exit;
    }

    echo $json;
    exit;
}

function encode_json(mixed $data): string
{
    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

/**
 * A short token that changes whenever what a visitor sees does: a sync, an edit
 * in the admin, or a change to the code that shapes the data.
 */
function data_version(): string
{
    $shape = max(array_map(fn ($file) => (int) @filemtime(__DIR__ . "/$file"), ['fields.php', 'games.php', 'platforms.php', 'site.php']));

    return substr(md5(setting('data_changed_at', '') . '|' . $shape), 0, 12);
}

/* ---------- Text ---------- */

/** Loose enough that "Billy & Mandy" and "Billy and Mandy" agree; the sync tools' norm(). */
function norm_name(?string $s): string
{
    $s = preg_replace('/[™®©]/u', '', (string) $s);
    $s = preg_replace('/\s+/u', ' ', trim($s));
    $s = strip_accents($s);
    $s = mb_strtolower($s);
    $s = preg_replace("/['’`]/u", '', $s);
    $s = str_replace('&', ' and ', $s);
    $s = preg_replace('/[^a-z0-9]+/', ' ', $s);

    return trim($s);
}

function strip_accents(string $s): string
{
    if (class_exists('Normalizer')) {
        $n = Normalizer::normalize($s, Normalizer::FORM_KD);
        if ($n !== false) {
            return preg_replace('/\p{Mn}+/u', '', $n);
        }
    }
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);

    return $t === false ? $s : $t;
}

function slugify(string $text): string
{
    $slug = preg_replace('/[^a-z0-9]+/', '-', mb_strtolower(strip_accents(preg_replace("/['’`]/u", '', $text))));

    return trim($slug, '-') ?: 'game';
}

function plural(int $count, string $singular, ?string $plural = null): string
{
    return $count === 1 ? $singular : ($plural ?? $singular . 's');
}

/** Only ever hand a browser an http(s) link built from outside data. */
function safe_http_url(string $url): bool
{
    return (bool) preg_match('#^https?://#i', $url);
}

/** 1572 → "26 h 12 min" */
function format_minutes(?int $minutes): string
{
    if ($minutes === null) {
        return '—';
    }
    if ($minutes < 60) {
        return $minutes . ' min';
    }

    $h = intdiv($minutes, 60);
    $m = $minutes % 60;

    return number_format($h) . ' h' . ($m ? " $m min" : '');
}

function format_bytes(int $bytes): string
{
    foreach (['GB' => 1 << 30, 'MB' => 1 << 20, 'KB' => 1 << 10] as $unit => $size) {
        if ($bytes >= $size) {
            return round($bytes / $size, 1) . ' ' . $unit;
        }
    }

    return $bytes . ' B';
}

/* ---------- Dates ---------- */

/**
 * SQLite's datetime('now') is UTC with no zone, so those are read as UTC;
 * anything carrying its own offset is left alone.
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

function format_datetime(?string $value): string
{
    return $value ? format_date($value, 'j M Y, H:i') : '—';
}

/** "3 minutes ago" */
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

    foreach ([[86400 * 365, 'year'], [86400 * 30, 'month'], [86400 * 7, 'week'], [86400, 'day'], [3600, 'hour'], [60, 'minute']] as [$size, $unit]) {
        if ($seconds >= $size) {
            $count = (int) floor($seconds / $size);
            return "$count " . plural($count, $unit) . ' ago';
        }
    }

    return 'just now';
}

/** ISO 8601 in UTC, as the platform files have their dates. */
function iso_now(): string
{
    return gmdate('Y-m-d\TH:i:s\Z');
}

/**
 * The differences between two decoded JSON values, as "path: a ≠ b" lines in $diffs (20 at most).
 * The order of an object's keys doesn't count, nor {} against [], nor 1 against 1.0.
 */
function json_diff(mixed $a, mixed $b, string $path, array &$diffs): void
{
    if (count($diffs) > 20) {
        return;
    }
    if (is_array($a) && is_array($b)) {
        $listA = array_is_list($a);
        $listB = array_is_list($b);
        if ($a === [] || $b === []) {
            if ($a !== $b) {
                $diffs[] = "$path: " . json_encode($a) . ' ≠ ' . json_encode($b);
            }
            return;
        }
        if ($listA !== $listB) {
            $diffs[] = "$path: a list against an object";
            return;
        }
        foreach (array_unique([...array_keys($a), ...array_keys($b)]) as $k) {
            if (!array_key_exists($k, $a) || !array_key_exists($k, $b)) {
                $diffs[] = "$path.$k: " . (array_key_exists($k, $a) ? 'only in the file' : 'only in the API');
                continue;
            }
            json_diff($a[$k], $b[$k], "$path.$k", $diffs);
        }
        return;
    }
    // 1.0 in a file comes back as 1 from PHP, which JavaScript doesn't tell apart either
    if ((is_int($a) || is_float($a)) && (is_int($b) || is_float($b)) ? $a != $b : $a !== $b) {
        $diffs[] = "$path: " . json_encode($a) . ' ≠ ' . json_encode($b);
    }
}
