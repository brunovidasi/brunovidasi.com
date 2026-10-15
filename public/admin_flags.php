<?php

/**
 * The collection's quick marks: Favourite, Faces out and Hidden, set on one copy
 * from its row (toggle=field:id flips it) or on every copy ticked (set=field:value
 * with ids[]). The list's script asks for JSON and gets each copy's marks back;
 * without it the form posts here and comes back to the list as it was filtered.
 */

require_once __DIR__ . '/../includes/admin.php';

/** The marks, and what each may be set to: fav and hidden are on or left out, faceOut can also be No. */
const QUICK_FLAGS = ['fav' => ['1', ''], 'faceOut' => ['1', '0', ''], 'hidden' => ['1', '']];

$json = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
if ($json) {
    admin_json_request();
} else {
    require_login();
    if (!is_post()) {
        redirect('admin/games');
    }
    csrf_verify();
}

$fail = function (string $message, int $status = 422) use ($json): never {
    if ($json) {
        json_response(['error' => $message], $status);
    }
    flash($message, 'error');
    redirect('admin/games' . (post('back') !== '' ? '?' . post('back') : ''));
};

// what to change, and on which copies ($value null: flip it)
$value = null;
if (post('toggle') !== '') {
    [$field, $id] = array_pad(explode(':', post('toggle'), 2), 2, '');
    $ids = [$id];
} else {
    [$field, $value] = array_pad(explode(':', post('set'), 2), 2, '');
    $ids = array_values(array_unique(array_filter(array_map('strval', (array) ($_POST['ids'] ?? [])))));
    if (!$ids) {
        $fail('Tick the copies to change first.');
    }
}
if (!isset(QUICK_FLAGS[$field]) || ($value !== null && !in_array($value, QUICK_FLAGS[$field], true))) {
    $fail('That isn\'t a mark the list can change.', 400);
}

$games = [];
foreach ($ids as $id) {
    $game = find_game($id);
    if (!$game) {
        $fail("There is no copy with the id \"$id\".", 404);
    }
    $games[] = $game;
}

$changed = [];
db_transaction(function () use ($games, $field, $value, &$changed) {
    foreach ($games as $game) {
        // a toggle turns it on, or off when it is on (Faces out that is No turns on)
        $new = $value !== null ? ['1' => true, '0' => false, '' => null][$value] : (!empty($game[$field]) ? null : true);
        if (($game[$field] ?? null) !== $new) {
            $game[$field] = $new;
            update_game($game);
        }
        $changed[$game['id']] = ['fav' => !empty($game['fav']), 'faceOut' => $game['faceOut'] ?? null, 'hidden' => !empty($game['hidden'])];
    }
});

if ($json) {
    json_response(['games' => $changed]);
}

$label = ['fav' => 'Favourite', 'faceOut' => 'Faces out', 'hidden' => 'Hidden'][$field];
flash(count($changed) === 1 ? reset($games)['title'] . ": $label changed." : "$label changed on " . count($changed) . ' copies.');
redirect('admin/games' . (post('back') !== '' ? '?' . post('back') : ''));
