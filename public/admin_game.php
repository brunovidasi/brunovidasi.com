<?php

/**
 * Adds a copy, or changes one: every field the site draws it with (FIELDS), its
 * cover, and what the platforms say about it. A field a sync wrote says so, and
 * changing it keeps it from being synced again, unless "Let future syncs update
 * it" is ticked. A synced number (hours played, trophies…) can be corrected too:
 * kept for good, or until the platform says something new.
 */

require_once __DIR__ . '/../includes/admin.php';

$user = require_login();

$id = query('id', post('original_id'));
$row = $id !== '' ? find_game_row($id) : null;
if ($id !== '' && !$row) {
    admin_not_found("There is no copy with the id \"$id\".");
}
$isNew = $row === null;
$game = $row ? game_from_row($row) : ['id' => '', 'console' => query('console', 'pc'), 'title' => '', 'region' => 'PAL', 'released' => null, 'dateSource' => 'manual', 'publisher' => null, 'developer' => null, 'genre' => null, 'format' => 'boxed', 'cover' => null, 'colors' => null];
$states = $isNew ? [] : field_states($id);
$errors = [];

/* ---------- saving ---------- */

if (is_post()) {
    csrf_verify();
    $action = post('action', 'save');

    if ($action === 'delete' && !$isNew) {
        delete_game($id);
        flash("{$game['title']} was deleted." . (game_platform_links($game) ? ' It was tied to a platform: the next sync may add it back (hide it instead to keep it off the site).' : ''));
        redirect('admin/games');
    }

    $old = $game;
    $new = $isNew ? [] : $game;

    // the fields, as FIELDS describes them
    foreach (FIELDS as $key => $f) {
        if (in_array($f['type'], ['cover', 'colors'], true) || !array_key_exists($key, $_POST['f'] ?? [])) {
            continue;
        }
        [$value, $error] = field_from_post($key, $_POST['f'][$key], $_POST['shape'][$key] ?? null);
        if ($error) {
            $errors[] = $error;
        }
        $new[$key] = $value;
    }

    // the id: typed, or made from the console and title
    $newId = strtolower(post('game_id'));
    if ($newId === '') {
        $newId = ($new['console'] ?? 'pc') . '-' . slugify((string) ($new['title'] ?? ''));
        if ($isNew) {
            $newId = unique_game_id($newId);
        }
    }
    if (!preg_match('/^[a-z0-9][a-z0-9-]*$/', $newId)) {
        $errors[] = 'The id may only have lower-case letters, digits and dashes.';
    } elseif ($newId !== $id && game_exists($newId)) {
        $errors[] = "Another copy already has the id \"$newId\".";
    }
    $new['id'] = $newId;

    // the bundle's packs must be copies
    foreach ($new['contains'] ?? [] as $packId) {
        if (!game_exists($packId) && $packId !== $newId) {
            $errors[] = "Packs in it: there is no copy with the id \"$packId\".";
        }
    }

    // the cover: what is typed, and the pictures of it beyond the front
    $cover = null;
    if (trim(post('cover_file')) !== '') {
        $cover = ['file' => trim(post('cover_file'))];
        if (post('cover_ratio') !== '') {
            $cover['ratio'] = round((float) post('cover_ratio'), 4);
        }
        $cover['source'] = post('cover_source') ?: 'manual';
        if (post('cover_ref') !== '') {
            $cover['ref'] = post('cover_ref');
        }
        if (trim(post('cover_more')) !== '') {
            $more = json_decode(post('cover_more'), true);
            if (!is_array($more) || array_is_list($more)) {
                $errors[] = 'The cover\'s other pictures must be a JSON object, like {"label": "covers/…-label.jpg"}.';
            } else {
                $cover += array_diff_key($more, array_flip(['file', 'ratio', 'source', 'ref']));
            }
        }
    }
    $new['cover'] = $cover;

    $colors = array_values(array_filter(array_map(fn ($c) => strtolower(trim((string) $c)), (array) ($_POST['colors'] ?? [])), fn ($c) => $c !== ''));
    foreach ($colors as $c) {
        if (!preg_match('/^#[0-9a-f]{6}$/', $c)) {
            $errors[] = "Spine colours: \"$c\" isn't a colour like #1a2b3c.";
        }
    }
    $new['colors'] = $colors ?: null;

    // a new picture: uploaded, or from an address
    try {
        if (!$errors && !empty($_FILES['cover_upload']['tmp_name']) && is_uploaded_file($_FILES['cover_upload']['tmp_name'])) {
            $new = apply_cover($new, (string) file_get_contents($_FILES['cover_upload']['tmp_name']), 'manual', post('cover_ref') ?: 'Uploaded in the admin');
        } elseif (!$errors && post('cover_url') !== '') {
            $new = apply_cover($new, download_image(post('cover_url')), parse_url(post('cover_url'), PHP_URL_HOST) ?: 'manual', post('cover_url'));
        } elseif (!$errors && post('recolor') === '1' && !empty($new['cover']['file'])) {
            $m = measure_cover_file($new['cover']['file']);
            if ($m) {
                $new['cover']['ratio'] = $m['ratio'];
                $new['colors'] = $m['colors'];
            } else {
                $errors[] = "The cover file {$new['cover']['file']} isn't on this server, so its colours can't be worked out.";
            }
        }
        if (!$errors && !empty($_FILES['label_upload']['tmp_name']) && is_uploaded_file($_FILES['label_upload']['tmp_name'])) {
            $label = save_cover_image((string) file_get_contents($_FILES['label_upload']['tmp_name']), cover_path_for($new, '-label'), LABEL_HEIGHT);
            $new['cover'] ??= ['file' => null];
            $new['cover']['label'] = $label['file'];
            $new['cover']['labelRef'] = post('label_ref') ?: 'Photo of my own copy';
        }
    } catch (Throwable $e) {
        $errors[] = 'Cover: ' . $e->getMessage();
    }

    if (!$errors) {
        db_transaction(function () use ($isNew, $new, $id, $states) {
            if ($isNew) {
                insert_game($new);
            } else {
                update_game($new, $id);
            }

            // a synced field: kept as it is from now on, or left for the next sync to update, as ticked
            foreach (array_keys($states) as $field) {
                lock_field($new['id'], $field, !isset($_POST['keep_sync'][$field]));
            }

            // the synced numbers corrected by hand
            foreach ((array) ($_POST['o'] ?? []) as $platform => $collections) {
                foreach ((array) $collections as $collection => $records) {
                    foreach ((array) $records as $extId => $paths) {
                        $record = platform_record($platform, $collection, (string) $extId);
                        $types = PLATFORM_FIELDS[$platform][$collection] ?? [];
                        foreach ((array) $paths as $path => $o) {
                            if (!isset($types[$path]) || $record === null) {
                                continue;
                            }
                            $mode = $o['mode'] ?? '';
                            if ($mode === '') {
                                clear_override($platform, $collection, (string) $extId, $path);
                                continue;
                            }
                            $raw = trim((string) ($o['value'] ?? ''));
                            $value = match ($types[$path][1]) {
                                'minutes'  => $raw === '' ? null : (int) round((float) str_replace(',', '.', $raw) * 60),
                                'datetime' => local_input_to_iso($raw),
                                default    => $raw === '' ? null : (int) $raw,
                            };
                            set_override($platform, $collection, (string) $extId, $path, $value, $mode, path_get($record, $path));
                        }
                    }
                }
            }
        });

        flash($isNew ? "{$new['title']} was added." : "{$new['title']} was saved.");
        $tab = post('tab');
        redirect('admin/game?id=' . rawurlencode($new['id']) . (preg_match('/^g-[a-z]+$/', $tab) ? "#$tab" : ''));
    }

    // show the form again with what was typed
    $game = $new + ['id' => $id];
}

/* ---------- the page ---------- */

$links = game_platform_links($game);
$coverFile = $game['cover']['file'] ?? null;
$coverMore = array_diff_key($game['cover'] ?? [], array_flip(['file', 'ratio', 'source', 'ref']));
$history = [];
if (!$isNew) {
    $stmt = db()->prepare('SELECT c.*, r.platform, r.started_at, r.status FROM sync_changes c JOIN sync_runs r ON r.id = c.run_id WHERE c.game_id = ? ORDER BY c.id DESC LIMIT 40');
    $stmt->execute([$id]);
    $history = $stmt->fetchAll();
}

// the form's parts, as FIELD_GROUPS has them; the cover's are in the cover card
$groupKeys = fn (string $group) => array_keys(array_filter(FIELDS, fn ($f) => $f['group'] === $group));
$groups = [];
foreach (FIELD_GROUPS as $group => $groupLabel) {
    if ($group !== 'cover') {
        $groups[$group] = [$groupLabel, $groupKeys($group)];
    }
}

$title = $isNew ? 'Add a game' : $game['title'];
$intro = $isNew ? 'A copy for the shelf. The id is made from the console and the title unless you type one.' : console_name($game['console']) . ' · ' . ($game['id']);
// Save, Back, See it on the site and Delete, beside the title; the buttons belong to the form below
// (form="gameForm"). Save comes first in the page so Enter saves; the CSS sets them out Delete first.
$actions = '<span class="edit-actions">'
    . '<button type="submit" form="gameForm" name="action" value="save" class="primary">' . ($isNew ? 'Add the game' : 'Save') . '</button>'
    . ($isNew ? '' : '<a class="button ghost" href="' . e(url('#' . rawurlencode($id))) . '" target="_blank" rel="noopener">See it on the site ↗</a>')
    . '<a class="button ghost" href="' . e(url('admin/games')) . '">Back to the collection</a>'
    . ($isNew ? '' : '<button type="submit" form="gameForm" name="action" value="delete" class="danger" formnovalidate data-confirm="' . e("Delete {$game['title']}? " . ($links ? 'It is tied to a platform, so the next sync may add it back: hiding it keeps it off the site instead.' : 'This cannot be undone.')) . '">Delete</button>')
    . '</span>';
admin_header($title, $intro, $actions);

foreach ($errors as $error) {
    echo flash_box($error, 'error');
}
?>

<form method="post" enctype="multipart/form-data" class="game-form" id="gameForm" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="original_id" value="<?= e($isNew ? '' : $id) ?>">

  <div class="edit-layout">
    <aside class="edit-side">
      <section class="card cover-card" id="g-cover" data-find="<?= e(url('admin/cover')) ?>" data-id="<?= e($isNew ? '' : $id) ?>">
        <h2>Cover</h2>
        <div class="cover-preview">
          <?php if ($coverFile && is_file(public_dir() . '/' . $coverFile)): ?>
            <img src="<?= e(url($coverFile)) ?>?v=<?= (int) @filemtime(public_dir() . '/' . $coverFile) ?>" alt="The cover">
          <?php elseif ($coverFile): ?>
            <div class="no-cover">The file <code><?= e($coverFile) ?></code> isn't on this server yet.</div>
          <?php else: ?>
            <div class="no-cover">No cover yet.</div>
          <?php endif; ?>
        </div>
        <?php if (isset($states['cover'])): ?>
          <div class="field-sync<?= $states['cover']['locked'] ? ' locked' : '' ?>" data-field="cover">
            <?= sync_badge($states['cover']['source'], $states['cover']['synced_at']) ?>
            <?= check_box('keep_sync[cover]', !$states['cover']['locked'], 'Let future syncs update it', 'keep') ?>
          </div>
        <?php endif; ?>

        <div class="cover-tools">
          <?php if (!$isNew): ?>
            <button type="button" class="wide" data-find-cover>Find cover</button>
          <?php endif; ?>
          <label class="field">
            <span class="label">Upload a picture</span>
            <input type="file" name="cover_upload" accept="image/jpeg,image/png,image/webp,image/avif,image/gif">
          </label>
          <div class="field">
            <label for="cover_url">Or take it from an address</label>
            <input type="url" id="cover_url" name="cover_url" placeholder="https://…">
          </div>
          <div class="cover-results" data-results hidden></div>
        </div>

        <details class="cover-details" <?= $coverFile ? '' : 'open' ?>>
          <summary>File, source and colours</summary>
          <div class="field"><label for="cover_file">File</label><input type="text" id="cover_file" name="cover_file" value="<?= e($coverFile) ?>" placeholder="covers/<?= e($game['console']) ?>/…jpg"></div>
          <div class="field half"><label for="cover_ratio">Shape (w ÷ h)</label><input type="number" step="0.0001" id="cover_ratio" name="cover_ratio" value="<?= e($game['cover']['ratio'] ?? '') ?>"></div>
          <div class="field half"><label for="cover_source">Source</label><input type="text" id="cover_source" name="cover_source" value="<?= e($game['cover']['source'] ?? '') ?>" list="coverSources"></div>
          <datalist id="coverSources"><?php foreach (['manual', 'libretro-thumbnails', 'thegamesdb.net', 'sims.fandom.com', 'aldostools/resources', 'xlenore/ps2-covers', 'steam', 'en.wikipedia.org'] as $s): ?><option value="<?= e($s) ?>"><?php endforeach; ?></datalist>
          <div class="field"><label for="cover_ref">Where it is from</label><input type="text" id="cover_ref" name="cover_ref" value="<?= e($game['cover']['ref'] ?? '') ?>"></div>
          <div class="field">
            <label>Spine colours</label>
            <div class="swatches">
              <?php for ($i = 0; $i < 3; $i++): $c = $game['colors'][$i] ?? ''; ?>
                <span class="color-pair"><input type="color" value="<?= e($c ?: '#000000') ?>" data-color-for="color-<?= $i ?>" aria-label="Colour <?= $i + 1 ?>"><input type="text" id="color-<?= $i ?>" name="colors[]" value="<?= e($c) ?>" placeholder="#rrggbb" size="8"></span>
              <?php endfor; ?>
            </div>
            <?= check_box('recolor', false, 'Work out the shape and colours from the cover again') ?>
          </div>
          <div class="field">
            <label for="cover_more">Its other pictures</label>
            <textarea class="code" id="cover_more" name="cover_more" rows="<?= $coverMore ? 4 : 2 ?>" spellcheck="false" placeholder='{"label": "covers/…-label.jpg", "labelRef": "…"}'><?= e($coverMore ? json_encode($coverMore, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '') ?></textarea>
            <div class="hint">The card's label, the back, the spine… as files under covers/.</div>
          </div>
          <label class="field">
            <span class="label">Upload a photo of the card or cartridge label</span>
            <input type="file" name="label_upload" accept="image/jpeg,image/png,image/webp">
          </label>
          <div class="field"><label for="label_ref">About the label photo</label><input type="text" id="label_ref" name="label_ref" value="<?= e($game['cover']['labelRef'] ?? '') ?>"></div>
        </details>
        <?php $coverKeys = array_values(array_filter($groupKeys('cover'), fn ($k) => !in_array(FIELDS[$k]['type'], ['cover', 'colors'], true))); ?>
        <details class="cover-details" <?= array_filter($coverKeys, fn ($k) => isset($game[$k])) ? 'open' : '' ?>>
          <summary>What Find cover goes by</summary>
          <?php foreach ($coverKeys as $key): ?>
            <?= field_input($key, $game[$key] ?? null, $states[$key] ?? null) ?>
          <?php endforeach; ?>
        </details>
      </section>

      <?php if ($history): ?>
        <section class="card">
          <h2>Sync history</h2>
          <ul class="history">
            <?php foreach ($history as $h): ?>
              <li>
                <span class="dot <?= e($h['platform']) ?>"></span>
                <a href="<?= e(url('admin/sync_run?id=' . $h['run_id'])) ?>"><?= e(format_date($h['started_at'])) ?></a>
                <?= e(change_kind_label($h['kind'])) ?><?= $h['field'] ? ' <code>' . e($h['field']) . '</code>' : '' ?>
                <?php if ($h['status'] === 'reverted'): ?><span class="pill reverted">Undone</span><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>
    </aside>
    <div class="edit-main">

      <input type="hidden" name="tab" value="" data-tab-input>
      <nav class="jump" aria-label="Parts of the form" data-tabs>
        <a href="#g-cover" class="jump-cover">Cover</a>
        <?php foreach ($groups as $group => [$groupLabel, $keys]): $set = count(array_filter($keys, fn ($k) => isset($game[$k]))); ?>
          <a href="#g-<?= e($group) ?>" data-tab><?= e($groupLabel) ?><?php if ($set && $group !== 'basics'): ?> <span class="count"><?= $set ?></span><?php endif; ?></a>
        <?php endforeach; ?>
        <?php if ($links): ?><a href="#g-synced" data-tab>Synced numbers</a><?php endif; ?>
      </nav>

      <?php foreach ($groups as $group => [$groupLabel, $keys]):
          // the on/off ones first, side by side, then the rest
          $toggles = array_values(array_filter($keys, fn ($k) => FIELDS[$k]['type'] === 'bool'));
          $others = array_values(array_diff($keys, $toggles));
          ?>
        <section class="card group" id="g-<?= e($group) ?>" data-panel>
          <h2><?= e($groupLabel) ?></h2>
          <?php if ($toggles): ?>
            <div class="toggles">
              <?php foreach ($toggles as $key): ?>
                <?= field_input($key, $game[$key] ?? null, $states[$key] ?? null) ?>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if ($others || $group === 'basics'): ?>
            <div class="fields">
              <?php foreach ($others as $key): ?>
                <?= field_input($key, $game[$key] ?? null, $states[$key] ?? null) ?>
              <?php endforeach; ?>
              <?php if ($group === 'basics'): ?>
                <div class="field">
                  <label for="game_id">Id</label>
                  <input type="text" id="game_id" name="game_id" value="<?= e($game['id']) ?>" pattern="[a-z0-9][a-z0-9\-]*" placeholder="<?= $isNew ? 'made from the console and title' : '' ?>">
                  <div class="hint">The address of the game on the site (#<?= e($game['id'] ?: 'ps5-title') ?>). Changing it breaks links to the old one.</div>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </section>
      <?php endforeach; ?>

      <?php if ($links): ?>
        <section class="card group" id="g-synced" data-panel>
          <h2>What the platforms say</h2>
          <p class="muted small">Synced numbers, shown on the site under the game's details. Correct one and choose whether it stays yours, or only until the platform says something new.</p>
          <?php foreach ($links as $platform => $collections): ?>
            <?php foreach ($collections as $collection => $extIds): ?>
              <?php foreach ($extIds as $extId):
                  $rec = platform_record_row($platform, $collection, $extId);
                  $data = $rec ? json_decode($rec['data_json'], true) : null;
                  $ov = record_overrides($platform, $collection, $extId);
                  ?>
                <div class="synced-block">
                  <div class="synced-head">
                    <span class="dot <?= e($platform) ?>"></span>
                    <b><?= e($data['name'] ?? $extId) ?></b>
                    <span class="muted small"><?= e(PLATFORMS[$platform]['name']) ?> · <?= e($collection === 'trophySets' ? 'trophies' : 'play history') ?> · <code><?= e($extId) ?></code></span>
                    <?php if ($rec): ?><?= sync_badge($platform, $rec['synced_at']) ?><?php endif; ?>
                    <?php if ($rec && $rec['missing_since']): ?><span class="pill warn">Gone from <?= e(PLATFORMS[$platform]['short']) ?> since <?= e(format_date($rec['missing_since'])) ?></span><?php endif; ?>
                  </div>
                  <?php if (!$rec): ?>
                    <p class="muted small">Not in the last sync yet: its numbers show once <?= e(PLATFORMS[$platform]['name']) ?> is synced.</p>
                  <?php else: ?>
                    <div class="synced-fields">
                      <?php foreach (PLATFORM_FIELDS[$platform][$collection] ?? [] as $path => [$label, $type]):
                          $synced = path_get($data, $path);
                          $o = $ov[$path] ?? null;
                          $value = $o ? from_json($o['value_json']) : $synced;
                          $name = "o[$platform][$collection][$extId][$path]";
                          $inputId = 'o-' . md5($name);
                          ?>
                        <div class="field synced-field<?= $o ? ' overridden' : '' ?>">
                          <label for="<?= e($inputId) ?>"><?= e($label) ?><?= $type === 'minutes' ? ' <span class="muted">(hours)</span>' : '' ?></label>
                          <?php if ($type === 'minutes'): ?>
                            <input type="number" step="0.01" min="0" id="<?= e($inputId) ?>" name="<?= e($name) ?>[value]" value="<?= e(minutes_to_hours($value === null ? null : (int) $value)) ?>" data-synced="<?= e(minutes_to_hours($synced === null ? null : (int) $synced)) ?>">
                          <?php elseif ($type === 'datetime'): ?>
                            <input type="datetime-local" id="<?= e($inputId) ?>" name="<?= e($name) ?>[value]" value="<?= e(iso_to_local_input($value)) ?>" data-synced="<?= e(iso_to_local_input($synced)) ?>">
                          <?php else: ?>
                            <input type="number" min="0" id="<?= e($inputId) ?>" name="<?= e($name) ?>[value]" value="<?= e($value) ?>" data-synced="<?= e($synced) ?>">
                          <?php endif; ?>
                          <select name="<?= e($name) ?>[mode]" class="mode" aria-label="<?= e($label) ?>: whose value">
                            <?= options_html(['' => 'Synced value', 'keep' => 'Mine, always', 'until_sync' => 'Mine, until the platform changes'], $o['mode'] ?? '') ?>
                          </select>
                          <div class="hint"><?= e(PLATFORMS[$platform]['short']) ?> says <?= e(format_platform_value($synced, $type)) ?><?= $o ? ' · yours since ' . e(format_date($o['updated_at'])) : '' ?></div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>
    </div>

  </div>

</form>

<?php admin_footer(); ?>
