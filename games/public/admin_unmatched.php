<?php

/** Ties a game a sync couldn't place to a copy, or dismisses it (a game played but not owned). */

require_once __DIR__ . '/../includes/admin.php';

require_login();

if (!is_post()) {
    redirect('admin');
}
csrf_verify();

$platform = post('platform');
$collection = post('collection');
$extId = post('ext_id');
$field = PLATFORMS[$platform]['collections'][$collection] ?? null;
if ($field === null || $extId === '') {
    flash('That is not a game a sync reported.', 'error');
    redirect('admin');
}

if (post('action') === 'dismiss') {
    db()->prepare('UPDATE sync_unmatched SET dismissed = 1 WHERE platform = ? AND collection = ? AND ext_id = ?')->execute([$platform, $collection, $extId]);
    flash('Dismissed. It stays out of this list, even when a sync finds it again.');
    redirect('admin');
}

$game = find_game(post('game_id'));
if (!$game) {
    flash('There is no copy with the id "' . post('game_id') . '".', 'error');
    redirect('admin');
}

// a copy with several games in it (a trilogy) takes a list of ids
$current = game_links($game, $field);
if (in_array($extId, $current, true)) {
    flash("{$game['title']} is already tied to it.");
} else {
    $ids = [...$current, $extId];
    $game[$field] = FIELDS[$field]['type'] === 'idlist'
        ? (count($ids) === 1 ? $extId : $ids)
        : ($field === 'steamAppId' ? (int) $extId : $extId);
    if (FIELDS[$field]['type'] !== 'idlist' && $current) {
        flash("{$game['title']} was tied to " . implode(', ', $current) . ' and is now tied to this one instead.', 'warn');
    }
    update_game($game);
    flash("Tied to {$game['title']} (" . console_name($game['console']) . '). Its numbers show on the site now.');
}

db()->prepare('DELETE FROM sync_unmatched WHERE platform = ? AND collection = ? AND ext_id = ?')->execute([$platform, $collection, $extId]);
redirect('admin');
