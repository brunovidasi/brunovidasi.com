<?php

/**
 * Brings the consoles into the hardware table:
 *
 *   php cron/import_hardware.php                 (data/hardware.json)
 *   php cron/import_hardware.php path/to.json
 *   php cron/import_hardware.php --update    (also fill the empty fields of consoles already there)
 *
 * The file is {"hardware": [{id, console, name, model, …}]}, with the keys of
 * HARDWARE_FIELDS. Only consoles whose id isn't in the database yet are added,
 * and --update only fills fields that are still empty, so anything changed in
 * the admin stays as it is. The file is gitignored: it
 * has the serial numbers, and the repo is public.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Command line only.\n");
}

require_once __DIR__ . '/../includes/bootstrap.php';

$args = array_slice($argv, 1);
$update = in_array('--update', $args, true);
$file = current(array_filter($args, fn ($a) => !str_starts_with($a, '--'))) ?: __DIR__ . '/../data/hardware.json';
$data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
if (!is_array($data['hardware'] ?? null)) {
    fwrite(STDERR, "$file is missing or has no \"hardware\" list.\n");
    exit(1);
}

$added = $filled = 0;
db_transaction(function () use ($data, $update, &$added, &$filled) {
    foreach ($data['hardware'] as $i => $hw) {
        if (empty($hw['id']) || empty($hw['name']) || !isset(CONSOLES[$hw['console'] ?? ''])) {
            fwrite(STDERR, "Skipped #$i: it needs an id, a name and a console.\n");
            continue;
        }
        $old = find_hardware($hw['id']);
        if ($old) {
            $empty = array_filter(array_keys(HARDWARE_FIELDS), fn ($k) => $old[$k] === null && ($hw[$k] ?? null) !== null);
            if ($update && $empty) {
                save_hardware(array_intersect_key($hw, array_flip($empty)) + $old, false);
                $filled += count($empty);
            }
            continue;
        }
        save_hardware($hw + ['position' => $i], true);
        $added++;
    }
});

echo "Added $added " . plural($added, 'console') . ($update ? ", filled $filled empty " . plural($filled, 'field') : '') . ".\n";
