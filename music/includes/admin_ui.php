<?php

/** The admin's page frame and the pieces of markup its pages share. */

const ADMIN_NAV = [
    'admin'           => 'Dashboard',
    'admin_items'     => 'Collection',
    'admin_wantlist'  => 'Wantlist',
    'admin_selling'   => 'Selling',
    'admin_artists'   => 'Artists & eras',
    'admin_fields'    => 'Drawer fields',
    'admin_sync_runs' => 'Sync history',
    'admin_settings'  => 'Settings',
];

/** Sub-pages light up their parent's tab. */
const ADMIN_NAV_PARENTS = [
    'admin_item'         => 'admin_items',
    'admin_selling_item' => 'admin_selling',
    'admin_eras'         => 'admin_artists',
];

const BLANK_GIF = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';

/* ---------- Page frame ---------- */

function admin_header(string $title, string $intro = '', string $actions = ''): void
{
    require __DIR__ . '/templates/admin_header.php';
}

function admin_footer(string ...$scripts): void
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
    return '<div class="flash ' . e($kind) . '">' . e($message) . '</div>';
}

/* ---------- Form controls ---------- */

/** <option>s from [value => label]. */
function options_html(array $options, ?string $selected = null): string
{
    $html = '';
    foreach ($options as $value => $label) {
        $html .= '<option value="' . e($value) . '"' . ((string) $value === $selected ? ' selected' : '') . '>' . e($label) . '</option>';
    }

    return $html;
}

/** A labelled checkbox that posts "1" when ticked. */
function check_box(string $name, bool $checked, string $label, string $class = ''): string
{
    return '<label class="check' . ($class !== '' ? " $class" : '') . '">'
        . '<input type="checkbox" name="' . e($name) . '" value="1"' . ($checked ? ' checked' : '') . '> '
        . e($label) . '</label>';
}

/** The Type box: blank means Discogs' guess. */
function item_type_select(string $id, array $item): string
{
    $types = item_type_options($item['item_type']);
    $types = array_combine($types, array_map('ucfirst', $types));

    return '<select id="' . e($id) . '" name="item_type"><option value="">— from Discogs —</option>'
        . options_html($types, $item['item_type'])
        . '</select>';
}

/** The Format box, with the checkbox that keeps it through a sync. */
function media_kind_field(string $kind, bool $locked, string $lockLabel): string
{
    return '<div class="field">'
        . '<label for="media_kind">Format</label>'
        . '<select id="media_kind" name="media_kind">' . options_html(MEDIA_KINDS, $kind) . '</select>'
        . check_box('media_kind_locked', $locked, $lockLabel, 'spaced')
        . '</div>';
}

/* ---------- Image pickers ---------- */

/** One picture to pick, as a radio or a checkbox around its thumbnail. */
function picker_choice(string $type, string $name, string $value, bool $checked, string $image, string $caption, string $extra = '', string $title = ''): string
{
    return '<label' . ($title !== '' ? ' title="' . e($title) . '"' : '') . '>'
        . '<input type="' . $type . '" name="' . e($name) . '" value="' . e($value) . '"' . ($checked ? ' checked' : '') . $extra . '>'
        . '<img src="' . e($image) . '" alt="" loading="lazy">'
        . '<small>' . e($caption) . '</small>'
        . '</label>';
}

/** The "no picture" choice at the start of a picker. */
function picker_none(string $name, bool $checked, string $text, string $caption, string $extra = ''): string
{
    return '<label>'
        . '<input type="radio" name="' . e($name) . '" value=""' . ($checked ? ' checked' : '') . $extra . '>'
        . '<span class="none">' . e($text) . '</span>'
        . '<small>' . e($caption) . '</small>'
        . '</label>';
}

/** Discogs' images to choose from, as radios, after a "none" choice. */
function image_picker(string $name, array $images, string $current, string $noneText, string $noneCaption, string $extra = ''): string
{
    $html = picker_none($name, $current === '', $noneText, $noneCaption, $extra);
    foreach ($images as $image) {
        if (!empty($image['uri'])) {
            $html .= picker_choice('radio', $name, $image['uri'], $current === $image['uri'], $image['uri150'] ?? $image['uri'], $image['type'] ?? '', $extra);
        }
    }

    return '<div class="picker">' . $html . '</div>';
}

/** The cover picker on the edit pages. */
function cover_picker(array $item, array $images): string
{
    return image_picker('cover_url', $images, (string) $item['cover_url'], "Discogs' own", 'default');
}

/* ---------- Record pages ---------- */

/**
 * The buttons in an edit page's heading. Sync and Delete are forms of their
 * own, so Enter in a box can't set them off; Save belongs to the page's form.
 */
function record_actions(array $item, string $selfUrl, string $formId, string $cancelUrl, string $syncTitle, string $deleteConfirm, bool $canDelete = true): string
{
    $actions = [];
    if ($item['discogs_id']) {
        $actions[] = '<a class="btn ghost" target="_blank" rel="noopener" href="' . discogs_release_url((int) $item['discogs_id']) . '">On Discogs ↗</a>';
        $actions[] = action_form($selfUrl, 'sync', 'syncForm', '<button type="submit" class="ghost" title="' . e($syncTitle) . '">Sync with Discogs</button>');
    }
    if ($canDelete) {
        $actions[] = action_form($selfUrl, 'delete', 'deleteForm', '<button type="submit" class="danger">Delete</button>', $deleteConfirm);
    }
    $actions[] = '<a class="btn ghost" href="' . e($cancelUrl) . '">Cancel</a>';
    $actions[] = '<button type="submit" form="' . e($formId) . '" class="gold">Save</button>';

    return implode("\n", $actions);
}

function action_form(string $url, string $action, string $id, string $button, string $confirm = ''): string
{
    return '<form method="post" action="' . e($url) . '" id="' . e($id) . '"' . ($confirm !== '' ? ' data-confirm="' . e($confirm) . '"' : '') . '>'
        . csrf_field()
        . '<input type="hidden" name="action" value="' . e($action) . '">'
        . $button . '</form>';
}

/** The read-only Discogs facts on an edit page, leaving out the empty ones. */
function facts_table(array $facts): string
{
    $rows = '';
    foreach ($facts as $label => $value) {
        if ($value !== null && $value !== '') {
            $rows .= '<tr><td class="label">' . e($label) . '</td><td class="right">' . e($value) . '</td></tr>';
        }
    }

    return '<table class="table facts"><tbody>' . $rows . '</tbody></table>';
}

/** A correction box for one of OVERRIDE_BOXES: a line for a list, a textarea for lines. */
function override_input(string $key, string $value, string $placeholder = ''): string
{
    $name = 'id="o_' . e($key) . '" name="' . e($key) . '"';
    $placeholder = $placeholder !== '' ? ' placeholder="' . e($placeholder) . '"' : '';

    return OVERRIDE_FIELDS[$key] === 'list'
        ? '<input type="text" ' . $name . ' value="' . e($value) . '"' . $placeholder . '>'
        : '<textarea ' . $name . ' rows="2" class="short"' . $placeholder . '>' . e($value) . '</textarea>';
}

function discogs_hint(string $value): string
{
    return '<div class="inherited">Discogs: <b>' . e($value) . '</b></div>';
}

/* ---------- Tables ---------- */

function none_mark(): string
{
    return '<span class="none">—</span>';
}

function kind_badge(string $kind): string
{
    return '<span class="kind ' . e($kind) . '">' . e(media_kind_label($kind)) . '</span>';
}

/** A record's thumbnail cell; with $placeholder, an empty image keeps the row height without one. */
function thumb_cell(array $row, bool $placeholder = true): string
{
    $thumb = item_thumb($row);
    $image = match (true) {
        $thumb !== ''  => '<img src="' . e($thumb) . '" alt="" loading="lazy">',
        $placeholder   => '<img src="' . BLANK_GIF . '" alt="">',
        default        => '',
    };

    return '<td class="thumb">' . $image . '</td>';
}

/** The year, with the full release date in a tooltip (or a note that only the year is known). */
function year_cell(array $row): string
{
    $year = item_year($row);
    if (!$year) {
        return none_mark();
    }

    $full = release_date_label($row);
    $tip = $full !== '' && $full !== (string) $year ? $full : 'Only the year is known';

    return '<span class="hover-date" tabindex="0" data-tip="' . e($tip) . '" aria-label="' . e($year . ', ' . $tip) . '">' . $year . '</span>';
}

function edit_button(string $url): string
{
    return '<a class="btn ghost small" href="' . e($url) . '">Edit</a>';
}

/* ---------- Lists ---------- */

/**
 * A list's filters, sort and page, remembered in the settings between visits.
 * A request that names any of them sets them; a bare visit gets them back;
 * ?reset=1 forgets them.
 */
function remembered_list_state(string $list, array $keys, string $page): array
{
    $saved = setting('admin_list_filters', []);

    if (query('reset') !== '') {
        unset($saved[$list]);
        set_setting('admin_list_filters', $saved);
        redirect($page);
    }

    if (!isset($_GET['f']) && !array_intersect_key($_GET, array_flip($keys))) {
        return $saved[$list] ?? [];
    }

    $state = array_filter(array_combine($keys, array_map('query', $keys)), fn ($value) => $value !== '');
    if ($state !== ($saved[$list] ?? [])) {
        $saved[$list] = $state;
        set_setting('admin_list_filters', $saved);
    }

    return $state;
}

/** A list page's URL: its state with some of it changed. Empty values are dropped. */
function list_url(string $page, array $state, array $changes = []): string
{
    $params = array_filter(array_merge($state, $changes), fn ($value) => $value !== '' && $value !== null);

    return url($page) . ($params ? '?' . http_build_query($params) : '');
}

/** What is filtering a list, as chips that remove themselves: [[name, value, url], …]. */
function filter_chips(array $chips, string $clearUrl, string $clearTitle): string
{
    if (!$chips) {
        return '';
    }

    $html = '';
    foreach ($chips as [$name, $value, $removeUrl]) {
        $html .= '<a class="chip" href="' . e($removeUrl) . '" title="Remove this filter">'
            . '<span class="k">' . e($name) . '</span> ' . e($value) . ' <span class="x" aria-hidden="true">×</span></a>';
    }

    return '<div class="filter-chips" aria-label="What is filtering this list">' . $html
        . '<a class="clear-all" href="' . e($clearUrl) . '" title="' . e($clearTitle) . '">Clear all</a></div>';
}

/** A filter box: a select with "Any" first, marked when it is filtering. */
function filter_select(string $name, string $label, array $options, string $current): string
{
    return '<div class="field' . ($current !== '' ? ' is-active' : '') . '">'
        . '<label for="' . e($name) . '">' . e($label) . '</label>'
        . '<select id="' . e($name) . '" name="' . e($name) . '"><option value="">Any</option>' . options_html($options, $current) . '</select>'
        . '</div>';
}

function filter_search(string $current, string $placeholder): string
{
    return '<div class="field search' . ($current !== '' ? ' is-active' : '') . '">'
        . '<label for="q">Search</label>'
        . '<input type="search" id="q" name="q" value="' . e($current) . '" autocomplete="off" enterkeyhint="search" placeholder="' . e($placeholder) . '">'
        . '</div>';
}

/** Previous and next under a list, with $url giving each page's address. */
function pagination(int $pageNo, int $pages, callable $url): string
{
    if ($pages <= 1) {
        return '';
    }

    return '<div class="form-actions">'
        . ($pageNo > 1 ? '<a class="btn ghost small" href="' . e($url($pageNo - 1)) . '">← Previous</a>' : '')
        . '<span class="page-count">Page ' . $pageNo . ' of ' . $pages . '</span>'
        . ($pageNo < $pages ? '<a class="btn ghost small" href="' . e($url($pageNo + 1)) . '">Next →</a>' : '')
        . '</div>';
}

/** Sync run status as a pill; $warn are the statuses drawn as warnings. */
function run_status_pill(array $run, string $label, array $warn): string
{
    $class = match (true) {
        $run['status'] === 'ok'                  => 'good',
        in_array($run['status'], $warn, true)    => 'warn',
        default                                  => '',
    };

    return '<span class="pill ' . $class . '">' . e($label) . '</span>';
}

function run_details_cell(array $run): string
{
    return (int) $run['details_fetched'] . ($run['details_pending'] ? ' (+' . (int) $run['details_pending'] . ' to go)' : '');
}

/** "set" or "missing" as a coloured pill. */
function state_pill(bool $good, string $goodText, string $badText): string
{
    return $good ? '<span class="pill good">' . $goodText . '</span>' : '<span class="pill warn">' . $badText . '</span>';
}
