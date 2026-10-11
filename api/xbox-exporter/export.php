<?php
declare(strict_types=1);
$dir = __DIR__ . '/output';
if (!is_dir($dir)) { fwrite(STDERR, "Output folder not found. Run npm run export first.\n"); exit(1); }
$files = glob($dir . '/*.json') ?: [];
$summary = ['processed_at_utc' => gmdate('c'), 'json_files_found' => count($files), 'files' => []];
foreach ($files as $file) {
    $raw = file_get_contents($file);
    json_decode((string)$raw, true);
    $summary['files'][] = ['file' => basename($file), 'valid_json' => json_last_error() === JSON_ERROR_NONE, 'bytes' => filesize($file)];
}
file_put_contents($dir . '/php_validation_summary.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
echo 'Validated ' . count($files) . ' JSON files. Summary: output/php_validation_summary.json' . PHP_EOL;
