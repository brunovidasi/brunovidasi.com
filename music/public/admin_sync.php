<?php

/** One slice of a sync, for the dashboard's Sync button, which calls it until it says done. */

require_once __DIR__ . '/../includes/admin.php';

require_login();

if (!is_post()) {
    json_response(['error' => 'POST only.'], 405);
}

csrf_verify();

set_time_limit(60);

try {
    $runId = (int) ($_POST['run_id'] ?? 0) ?: sync_start('admin');
    json_response(sync_step($runId, 12.0));
} catch (Throwable $e) {
    error_log('Vinyl sync failed: ' . $e);
    json_response([
        'done'     => true,
        'status'   => 'error',
        'phase'    => 'error',
        'message'  => is_debug() ? $e->getMessage() : 'The sync stopped with an error. Check the log.',
        'progress' => ['collection' => 0, 'wantlist' => 0, 'added' => 0, 'removed' => 0, 'details' => 0, 'pending' => 0, 'api_calls' => 0],
        'log'      => '',
    ], 500);
}
