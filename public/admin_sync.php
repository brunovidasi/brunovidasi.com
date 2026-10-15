<?php

/**
 * One platform's sync, for the dashboard's buttons, which call it once per
 * platform (Sync all goes through them one after the other). Answers with the
 * run's summary as JSON.
 */

require_once __DIR__ . '/../includes/admin.php';

admin_json_request();

$platform = post('platform');
if (!isset(PLATFORMS[$platform])) {
    json_response(['error' => 'No such platform.'], 400);
}

// the session isn't needed while the sync runs, and holding it would keep every other admin page waiting
session_write_close();
ignore_user_abort(true);

$result = run_sync($platform, 'admin');
$last = latest_ok_sync_run($platform);

json_response($result + [
    'platform'  => $platform,
    'last'      => $last ? time_ago($last['finished_at']) : null,
    'run_url'   => $result['run_id'] ? url('admin/sync_run?id=' . $result['run_id']) : null,
], $result['status'] === 'error' ? 502 : 200);
