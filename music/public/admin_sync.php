<?php

/**
 * One slice of a sync, for the admin's Sync button.
 *
 * POST only, signed in only, CSRF-checked: starting a sync costs real API
 * calls against a rate limit, so it is not something a GET should ever do.
 * The browser calls this over and over until the answer says done.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'POST only.'], 405);
}

csrf_verify();

// Long enough to be worth a round trip, short enough that no host's execution
// limit or proxy timeout is anywhere near it.
set_time_limit(60);

try {
    $runId = (int) ($_POST['run_id'] ?? 0) ?: sync_start('admin');
    json_response(sync_step($runId, 12.0));
} catch (Throwable $e) {
    error_log('Vinyl sync failed: ' . $e);
    json_response([
        'done'    => true,
        'status'  => 'error',
        'phase'   => 'error',
        'message' => is_debug() ? $e->getMessage() : 'The sync stopped with an error. Check the log.',
        'progress' => ['collection' => 0, 'wantlist' => 0, 'added' => 0, 'removed' => 0, 'details' => 0, 'pending' => 0, 'api_calls' => 0],
        'log'     => '',
    ], 500);
}
