<?php

/**
 * The consoles themselves (the hardware table): what each machine's label says.
 * Admin only for now: nothing here is handed to the site.
 */

/**
 *   label, type (text | textarea | select | bool | json), help, options, required:
 *   as FIELDS has them. Every key is a column of its own.
 */
const HARDWARE_FIELDS = [
    'name'         => ['label' => 'Name', 'type' => 'text', 'required' => true, 'help' => 'As it is known: "Nintendo Switch OLED".'],
    'console'      => ['label' => 'Shelf', 'type' => 'select', 'required' => true, 'options' => CONSOLES, 'help' => 'The console its games are filed under.'],
    'model'        => ['label' => 'Model', 'type' => 'text', 'help' => 'The model number on the label: HEG-001, CECH-3002B.'],
    'edition'      => ['label' => 'Edition', 'type' => 'text'],
    'color'        => ['label' => 'Colour', 'type' => 'text'],
    'region'       => ['label' => 'Region', 'type' => 'select', 'options' => REGIONS],
    'video'        => ['label' => 'Video', 'type' => 'select', 'options' => ['PAL' => 'PAL', 'NTSC' => 'NTSC']],
    'region_free'  => ['label' => 'Region-free', 'type' => 'bool', 'help' => 'Plays games from any region.'],
    'year'         => ['label' => 'Year', 'type' => 'text', 'help' => 'When that model came out.'],
    'released'     => ['label' => 'Released', 'type' => 'text', 'help' => 'The model\'s first release, YYYY-MM-DD.'],
    'manufacturer' => ['label' => 'Manufacturer', 'type' => 'text'],
    'storage'      => ['label' => 'Storage', 'type' => 'text'],
    'serial'       => ['label' => 'Serial number', 'type' => 'text'],
    'manufactured' => ['label' => 'Made on', 'type' => 'text', 'help' => 'The date on the label, YYYY-MM-DD.'],
    'label_text'   => ['label' => 'Label text', 'type' => 'textarea', 'help' => 'Everything the label says, as written.'],
    'specs'        => ['label' => 'Specs', 'type' => 'json', 'help' => 'What the model has inside, as JSON: {"CPU": "…", "Memory": "…"}. From Wikipedia.'],
    'photo'        => ['label' => 'Photo', 'type' => 'json', 'help' => 'A photo of the model in public/covers/consoles/: {"file": "…", "source": "…", "author": "…", "license": "…"}.'],
    'extra'        => ['label' => 'Other markings', 'type' => 'json', 'help' => 'Anything else, as JSON: {"productId": "…", "fccId": "…"}.'],
    'note'         => ['label' => 'Note', 'type' => 'textarea'],
];

/** The fields kept as JSON, each in a "<key>_json" column. */
const HARDWARE_JSON = ['specs', 'photo', 'extra'];

/** Where the console photos are, under public/. */
const HARDWARE_PHOTO_DIR = 'covers/consoles';

/** A console's photo, small, for the list and the edit page. */
function hardware_photo(array $hw, string $class = 'thumb'): string
{
    $file = $hw['photo']['file'] ?? null;
    $path = $file ? HARDWARE_PHOTO_DIR . '/' . $file : null;
    if ($path && is_file(public_dir() . '/' . $path)) {
        return '<img class="' . e($class) . '" src="' . e(url($path)) . '" alt="" loading="lazy">';
    }

    return '<span class="' . e($class) . ' none" title="' . e($file ? "$path isn't on this server yet" : 'No photo') . '">' . ($file ? '?' : '—') . '</span>';
}

/** Every console, in the site's console order, then as they were added. */
function all_hardware(): array
{
    $rows = db()->query('SELECT * FROM hardware ORDER BY position, name')->fetchAll();
    $order = array_flip(array_keys(CONSOLES));
    usort($rows, fn ($a, $b) => [$order[$a['console']] ?? 99, $a['position']] <=> [$order[$b['console']] ?? 99, $b['position']]);

    return array_map('hardware_from_row', $rows);
}

function find_hardware(string $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM hardware WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ? hardware_from_row($row) : null;
}

function hardware_from_row(array $row): array
{
    foreach (HARDWARE_JSON as $key) {
        $row[$key] = from_json($row[$key . '_json'] ?? null);
        unset($row[$key . '_json']);
    }
    $row['region_free'] = $row['region_free'] === null ? null : (bool) $row['region_free'];

    return $row;
}

/** An id no other console has, from its name. */
function new_hardware_id(string $name): string
{
    $base = slugify($name);
    $id = $base;
    for ($n = 2; find_hardware($id); $n++) {
        $id = "$base-$n";
    }

    return $id;
}

/** Adds a console, or changes the one with $hw['id']. */
function save_hardware(array $hw, bool $isNew): void
{
    $cols = array_keys(HARDWARE_FIELDS);
    $values = [];
    foreach ($cols as $key) {
        $v = $hw[$key] ?? null;
        $json = in_array($key, HARDWARE_JSON, true);
        $values[$json ? $key . '_json' : $key] = match (true) {
            $json            => to_json($v),
            is_bool($v)      => (int) $v,
            default          => $v,
        };
    }

    if ($isNew) {
        $values['id'] = $hw['id'];
        $values['position'] = $hw['position'] ?? (int) db()->query('SELECT COALESCE(MAX(position), -1) + 1 FROM hardware')->fetchColumn();
        $names = array_keys($values);
        db()->prepare('INSERT INTO hardware (' . implode(', ', $names) . ') VALUES (' . implode(', ', array_fill(0, count($names), '?')) . ')')
            ->execute(array_values($values));
        return;
    }

    $set = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($values)));
    db()->prepare("UPDATE hardware SET $set, updated_at = datetime('now') WHERE id = ?")
        ->execute([...array_values($values), $hw['id']]);
}

function delete_hardware(string $id): void
{
    db()->prepare('DELETE FROM hardware WHERE id = ?')->execute([$id]);
}

/** One field of the edit form. */
function hardware_input(string $key, mixed $value): string
{
    $f = HARDWARE_FIELDS[$key];
    $id = 'h-' . $key;
    $attrs = ' id="' . e($id) . '" name="h[' . e($key) . ']"' . (!empty($f['required']) ? ' required' : '');

    $input = match ($f['type']) {
        'textarea' => '<textarea' . $attrs . ' rows="' . max(3, min(14, substr_count((string) $value, "\n") + 2)) . '">' . e($value) . '</textarea>',
        'select'   => '<select' . $attrs . '>' . options_html($f['options'] + ($value !== null && !isset($f['options'][$value]) ? [$value => $value] : []), $value, '—') . '</select>',
        'bool'     => '<select' . $attrs . '>' . options_html(['1' => 'Yes', '0' => 'No'], $value === null ? null : ($value ? '1' : '0'), '—') . '</select>',
        'json'     => '<textarea class="code"' . $attrs . ' rows="' . ($value === null ? 2 : min(12, 2 + substr_count(json_encode($value, JSON_PRETTY_PRINT), "\n"))) . '" spellcheck="false">'
            . e($value === null ? '' : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '</textarea>',
        default    => '<input type="text"' . $attrs . ' value="' . e($value) . '">',
    };

    return '<div class="field' . (in_array($f['type'], ['json', 'textarea'], true) ? ' wide' : '') . '">'
        . '<label for="' . e($id) . '">' . e($f['label']) . '</label>'
        . $input
        . (isset($f['help']) ? '<div class="hint">' . e($f['help']) . '</div>' : '')
        . '</div>';
}

/**
 * What a field's input posted, or an error. Empty is null.
 *
 * @return array{0: mixed, 1: ?string} [value, error]
 */
function hardware_from_post(string $key, mixed $raw): array
{
    $f = HARDWARE_FIELDS[$key];
    $raw = is_string($raw) ? trim($raw) : $raw;
    if ($raw === '' || $raw === null) {
        return [null, !empty($f['required']) ? "{$f['label']} is needed." : null];
    }

    switch ($f['type']) {
        case 'bool':
            return [$raw === '1', null];
        case 'json':
            $value = json_decode($raw, true);
            return $value === null ? [null, "{$f['label']} isn't valid JSON: " . json_last_error_msg() . '.'] : [$value, null];
        case 'select':
            return isset($f['options'][$raw]) ? [$raw, null] : [null, "{$f['label']}: pick one of the list."];
        default:
            return [str_replace("\r\n", "\n", $raw), null];
    }
}
