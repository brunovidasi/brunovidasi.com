<?php

/** The admin's page frame and the pieces of markup its pages share. */

const ADMIN_NAV = [
    'admin'           => 'Dashboard',
    'admin_games'     => 'Collection',
    'admin_sync_runs' => 'Sync History',
    'admin_settings'  => 'Settings',
];

/** Sub-pages light up their parent's tab. */
const ADMIN_NAV_PARENTS = [
    'admin_game'     => 'admin_games',
    'admin_sync_run' => 'admin_sync_runs',
];

/* ---------- page frame ---------- */

function admin_header(string $title, string $intro = '', string $actions = ''): void
{
    require __DIR__ . '/templates/admin_header.php';
}

function admin_footer(): void
{
    require __DIR__ . '/templates/admin_footer.php';
}

function admin_not_found(string $message): never
{
    http_response_code(404);
    admin_header('Not found');
    echo '<div class="card"><p class="empty">' . e($message) . '</p></div>';
    admin_footer();
    exit;
}

function auth_header(string $title): void
{
    require __DIR__ . '/templates/auth_top.php';
}

function auth_footer(): void
{
    require __DIR__ . '/templates/auth_bottom.php';
}

function flash_box(string $message, string $kind = 'ok'): string
{
    return '<div class="flash ' . e($kind) . '" role="status">' . e($message) . '</div>';
}

/** A JSON answer for the admin's own scripts, after the sign-in and CSRF checks. */
function admin_json_request(): void
{
    if (!current_user()) {
        json_response(['error' => 'Signed out: sign in again.'], 401);
    }
    if (!is_post()) {
        json_response(['error' => 'POST only.'], 405);
    }
    csrf_verify();
}

/* ---------- form controls ---------- */

/** <option>s from [value => label]. */
function options_html(array $options, mixed $selected = null, ?string $blank = null): string
{
    $html = $blank !== null ? '<option value="">' . e($blank) . '</option>' : '';
    foreach ($options as $value => $label) {
        $html .= '<option value="' . e($value) . '"' . ((string) $value === (string) $selected && $selected !== null ? ' selected' : '') . '>' . e($label) . '</option>';
    }

    return $html;
}

/** A labelled checkbox that posts $value ("1") when ticked. */
function check_box(string $name, bool $checked, string $label, string $class = '', string $value = '1'): string
{
    return '<label class="check' . ($class !== '' ? " $class" : '') . '">'
        . '<input type="checkbox" name="' . e($name) . '" value="' . e($value) . '"' . ($checked ? ' checked' : '') . '> '
        . '<span>' . e($label) . '</span></label>';
}

/** The little badge a value from a sync wears. */
function sync_badge(string $platform, ?string $when, string $extra = ''): string
{
    return '<span class="synced" title="' . e('Written by the ' . platform_name($platform) . ' sync' . ($when ? ' on ' . format_datetime($when) : '')) . '">'
        . '<span class="dot ' . e($platform) . '"></span>Synced from ' . e(platform_name($platform))
        . ($when ? ' · ' . e(format_date($when)) : '') . $extra . '</span>';
}

function status_pill(string $status): string
{
    $label = ['ok' => 'OK', 'error' => 'Failed', 'running' => 'Running', 'reverted' => 'Undone', 'skipped' => 'Skipped', 'unset' => 'Not connected'][$status] ?? $status;

    return '<span class="pill ' . e($status) . '">' . e($label) . '</span>';
}

/** A copy's cover, small, for the lists. */
function cover_thumb(array $game, string $class = 'thumb'): string
{
    $file = $game['cover']['file'] ?? null;
    if ($file && is_file(public_dir() . '/' . $file)) {
        return '<img class="' . e($class) . '" src="' . e(url($file)) . '" alt="" loading="lazy">';
    }

    return '<span class="' . e($class) . ' none" title="' . e($file ? "$file isn't on this server yet" : 'No cover') . '">' . ($file ? '?' : '—') . '</span>';
}

/**
 * One field of the edit form, as FIELDS describes it. $state is its field_state
 * row when a sync wrote it: the field then says so, and offers to let later
 * syncs keep it up to date or to keep the value as it is.
 */
function field_input(string $key, mixed $value, ?array $state = null): string
{
    $f = FIELDS[$key];
    $name = "f[$key]";
    $id = 'f-' . $key;
    $type = $f['type'];
    $attrs = ' id="' . e($id) . '" name="' . e($name) . '"' . (!empty($f['required']) ? ' required' : '');

    $input = match ($type) {
        'textarea' => '<textarea' . $attrs . ' rows="3">' . e($value) . '</textarea>',
        'select' => '<select' . $attrs . '>' . options_html(field_options($key) + ($value !== null && !isset(field_options($key)[$value]) ? [$value => $value] : []), $value, '—') . '</select>',
        'bool' => '<select' . $attrs . '>' . options_html(['1' => 'Yes', '0' => 'No'], $value === null ? null : ($value ? '1' : '0'), '—') . '</select>',
        'int' => '<input type="number"' . $attrs . ' value="' . e($value) . '">',
        'color' => '<span class="color-pair"><input type="color" value="' . e(preg_match('/^#[0-9a-f]{6}$/i', (string) $value) ? $value : '#000000') . '" data-color-for="' . e($id) . '" aria-label="Pick a colour"><input type="text"' . $attrs . ' value="' . e($value) . '" placeholder="#rrggbb" pattern="#[0-9a-fA-F]{3,8}|"></span>',
        'idlist' => '<input type="text"' . $attrs . ' value="' . e(is_array($value) ? implode(', ', $value) : $value) . '">'
            . (is_array($value) ? '<input type="hidden" name="shape[' . e($key) . ']" value="list">' : ''),
        'list' => '<input type="text"' . $attrs . ' value="' . e(is_array($value) ? implode(', ', $value) : $value) . '" placeholder="comma separated">',
        'json' => '<textarea class="code"' . $attrs . ' rows="' . ($value === null ? 2 : min(10, 2 + substr_count(json_encode($value, JSON_PRETTY_PRINT), "\n"))) . '" spellcheck="false">'
            . e($value === null ? '' : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '</textarea>',
        default => '<input type="text"' . $attrs . ' value="' . e($value) . '">',
    };

    $sync = '';
    if ($state) {
        $synced = from_json($state['synced_value']);
        $differs = $synced !== $value && $synced !== null;
        $sync = '<div class="field-sync' . ($state['locked'] ? ' locked' : '') . '" data-field="' . e($key) . '">'
            . sync_badge($state['source'], $state['synced_at'])
            . check_box("keep_sync[$key]", !$state['locked'], 'Let future syncs update it', 'keep')
            . ($state['locked'] && $differs ? '<span class="was">' . e(platform_name($state['source'])) . ' says: <code>' . e(is_scalar($synced) ? (is_bool($synced) ? ($synced ? 'yes' : 'no') : $synced) : json_encode($synced, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '</code>'
                . (is_scalar($synced) ? ' <button type="button" class="link" data-use="' . e($id) . '" data-value="' . e(is_bool($synced) ? ($synced ? '1' : '0') : $synced) . '">Use it</button>' : '') . '</span>' : '')
            . '</div>';
    }

    return '<div class="field' . ($type === 'json' || $type === 'textarea' ? ' wide' : '') . '">'
        . '<label for="' . e($id) . '">' . e($f['label']) . '</label>'
        . $input
        . (isset($f['help']) ? '<div class="hint">' . e($f['help']) . '</div>' : '')
        . $sync
        . '</div>';
}

/**
 * The value a field's input posted, as the site keeps it, or an error. Empty is
 * null (the field is left out).
 *
 * @return array{0: mixed, 1: ?string} [value, error]
 */
function field_from_post(string $key, mixed $raw, ?string $shape = null): array
{
    $f = FIELDS[$key];
    $raw = is_string($raw) ? trim($raw) : $raw;
    if ($raw === '' || $raw === null) {
        return [null, !empty($f['required']) ? "{$f['label']} is needed." : null];
    }

    switch ($f['type']) {
        case 'bool':
            return [$raw === '1', null];
        case 'int':
            return is_numeric($raw) ? [(int) $raw, null] : [null, "{$f['label']} must be a number."];
        case 'idlist':
            $ids = array_values(array_filter(array_map('trim', preg_split('/[,\s]+/', $raw))));
            $value = count($ids) === 1 && $shape !== 'list' ? $ids[0] : $ids;
            if ($key === 'steamAppId') {
                return [(int) $ids[0], null];
            }
            return [$value, null];
        case 'list':
            return [array_values(array_filter(array_map('trim', explode(',', $raw)), fn ($x) => $x !== '')), null];
        case 'json':
            $value = json_decode($raw, true);
            if ($value === null && strtolower($raw) !== 'null') {
                return [null, "{$f['label']} isn't valid JSON: " . json_last_error_msg() . '.'];
            }
            return [$value, null];
        case 'color':
            return preg_match('/^#[0-9a-f]{3,8}$/i', $raw) ? [strtolower($raw), null] : [null, "{$f['label']} must be a colour like #1a2b3c."];
        case 'select':
            $options = field_options($key);
            return [$raw, isset($options[$raw]) || $key === 'pack' ? null : "{$f['label']}: pick one of the list."];
        default:
            return [$raw, null];
    }
}

/** Minutes for an hours box ("26.2" hours), and back. */
function minutes_to_hours(?int $minutes): string
{
    return $minutes === null ? '' : rtrim(rtrim(number_format($minutes / 60, 2, '.', ''), '0'), '.');
}

/** An ISO date as a datetime-local box wants it (in the config's timezone, as the admin shows dates), and back. */
function iso_to_local_input(?string $iso): string
{
    $t = to_timestamp($iso);

    return $t === null ? '' : date('Y-m-d\TH:i', $t);
}

function local_input_to_iso(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    $t = strtotime($value);

    return $t === false ? null : gmdate('Y-m-d\TH:i:s', $t) . '.000Z';
}

/** How a synced value reads in the admin. */
function format_platform_value(mixed $value, string $type): string
{
    if ($value === null) {
        return '—';
    }

    return match ($type) {
        'minutes'  => format_minutes((int) $value),
        'datetime' => format_datetime((string) $value),
        default    => is_scalar($value) ? (string) $value : json_encode($value),
    };
}

/** What a sync_changes row says happened. */
function change_kind_label(string $kind): string
{
    return [
        'added'      => 'added the copy',
        'linked'     => 'tied it',
        'filled'     => 'filled',
        'kept'       => 'kept your value of',
        'record'     => 'changed',
        'new_record' => 'new in the platform',
        'missing'    => 'gone from the platform',
        'ambiguous'  => 'could be several copies',
        'unmatched'  => 'no copy found',
    ][$kind] ?? $kind;
}
