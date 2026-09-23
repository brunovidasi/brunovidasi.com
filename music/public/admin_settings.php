<?php

/**
 * The handful of things that are settings rather than data.
 *
 * Credentials are not here: the Discogs token and the cron token live in the
 * config file outside the repo, where a web request can't read or change them.
 * This page only reports on them.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (post('action') === 'test_token') {
        try {
            $identity = (new DiscogsClient())->identity();
            flash('Discogs says hello to ' . ($identity['username'] ?? 'someone') . '.');
        } catch (DiscogsException $e) {
            flash($e->getMessage(), 'error');
        }
        redirect('admin_settings');
    }

    if (post('action') === 'reset_header') {
        // Back to what the header shipped with: drop every hero_* setting.
        db()->exec("DELETE FROM settings WHERE key LIKE 'hero\_%' ESCAPE '\\'");
        flash('The header is back to its defaults.');
        redirect('admin_settings');
    }

    set_setting('site_title', post('site_title') ?: 'The Collection');
    set_setting('site_intro', post('site_intro'));
    set_setting('detail_max_age_days', max(1, (int) post('detail_max_age_days')));
    set_setting('show_wantlist', post('show_wantlist') !== '');
    set_setting('show_selling', post('show_selling') !== '');

    // The header (see hero_options() in includes/hero.php). Text is cut to a
    // length the pills and the strip can hold; a blank number label falls back
    // to its own name.
    set_setting('hero_eyebrow', mb_substr(post('hero_eyebrow'), 0, 60));
    set_setting('hero_wantlist_label', mb_substr(post('hero_wantlist_label'), 0, 24));
    set_setting('hero_selling_label', mb_substr(post('hero_selling_label'), 0, 24));
    foreach (['split_title', 'rule', 'platter', 'tonearm', 'covers', 'counts', 'animate', 'stats'] as $switch) {
        set_setting("hero_$switch", post("hero_$switch") !== '');
    }
    $show = $label = [];
    foreach (HERO_STATS as $key => $default) {
        $show[$key] = post("hero_stat_show_$key") !== '';
        $label[$key] = mb_substr(post("hero_stat_label_$key"), 0, 24);
    }
    set_setting('hero_stat_show', $show);
    set_setting('hero_stat_label', $label);

    flash('Saved.');
    redirect('admin_settings');
}

$client = new DiscogsClient();
$runs = db()->query('SELECT * FROM sync_runs ORDER BY id DESC LIMIT 10')->fetchAll();
$hero = hero_options();

$pageTitle = 'Settings';
$pageIntro = 'How the site describes itself, what its header shows, and how often Discogs gets re-read.';

require __DIR__ . '/../includes/admin_layout_top.php';
?>

<form method="post">
  <?= csrf_field() ?>

  <div class="cards">
    <div class="card">
      <h2>The site</h2>

      <div class="field">
        <label for="site_title">Title</label>
        <input type="text" id="site_title" name="site_title" value="<?= e(setting('site_title', 'The Collection')) ?>">
      </div>

      <div class="field">
        <label for="site_intro">Intro line</label>
        <textarea id="site_intro" name="site_intro"><?= e(setting('site_intro', "Every record, CD and disc Bruno owns, straight from the Discogs shelf.")) ?></textarea>
      </div>

      <label class="check">
        <input type="checkbox" name="show_wantlist" value="1"<?= setting('show_wantlist', true) ? ' checked' : '' ?>>
        Show the wantlist page on the site
      </label>
      <label class="check">
        <input type="checkbox" name="show_selling" value="1"<?= setting('show_selling', true) ? ' checked' : '' ?>>
        Show the selling page on the site
      </label>

      <div class="form-actions">
        <button type="submit" class="gold">Save</button>
      </div>
    </div>

    <div class="card">
      <h2>Syncing</h2>

      <div class="field">
        <label for="detail_max_age_days">Re-read a release after</label>
        <input type="number" id="detail_max_age_days" name="detail_max_age_days" min="1" value="<?= (int) setting('detail_max_age_days', 45) ?>">
        <div class="hint">
          Days before a release's full detail is fetched again. Every re-read costs one API call against a
          limit of <?= $client->hasToken() ? '60' : '25' ?> a minute, so a long window keeps the nightly
          sync short. New records are always fetched immediately.
        </div>
      </div>

      <table class="table">
        <tbody>
          <tr><td>Discogs user</td><td class="right"><?= e(discogs_username() ?: '— not set —') ?></td></tr>
          <tr><td>Token</td><td class="right"><?= $client->hasToken() ? '<span class="pill good">in config</span>' : '<span class="pill warn">missing</span>' ?></td></tr>
          <tr><td>Cron endpoint</td><td class="right"><?= cron_token() !== '' ? '<span class="pill good">enabled</span>' : '<span class="pill warn">no token</span>' ?></td></tr>
        </tbody>
      </table>

      <div class="form-actions">
        <button type="submit" name="action" value="test_token" class="ghost">Test the token</button>
      </div>
    </div>
  </div>

  <div class="card">
    <h2>The header</h2>
    <p class="hint">The top of every page on the site. Each artist's own picture is set on their page under
      <a href="<?= e(url('admin_artists')) ?>">Artists &amp; eras</a>. Changes show as soon as you save.</p>

    <div class="grid-fields">
      <div class="field">
        <label for="hero_eyebrow">Line above the title</label>
        <input type="text" id="hero_eyebrow" name="hero_eyebrow" maxlength="60" value="<?= e($hero['eyebrow']) ?>">
        <div class="hint">On the shelf page only. Leave it empty to hide the line.</div>
      </div>
      <div class="field">
        <label for="hero_wantlist_label">Wantlist link</label>
        <input type="text" id="hero_wantlist_label" name="hero_wantlist_label" maxlength="24" value="<?= e($hero['wantlist_label']) ?>">
        <div class="hint">The gold pill that goes to the wantlist. A ♡ in front is just a character.</div>
      </div>
      <div class="field">
        <label for="hero_selling_label">Selling link</label>
        <input type="text" id="hero_selling_label" name="hero_selling_label" maxlength="24" value="<?= e($hero['selling_label']) ?>">
        <div class="hint">The gold pill that goes to what's for sale.</div>
      </div>
    </div>

    <label class="check">
      <input type="checkbox" name="hero_split_title" value="1"<?= $hero['split_title'] ? ' checked' : '' ?>>
      Split the title's weight: the last word bold and gold, the rest light
    </label>
    <label class="check">
      <input type="checkbox" name="hero_rule" value="1"<?= $hero['rule'] ? ' checked' : '' ?>>
      A small gold rule under the title
    </label>
    <label class="check">
      <input type="checkbox" name="hero_platter" value="1"<?= $hero['platter'] ? ' checked' : '' ?>>
      The big record beside the title
    </label>
    <label class="check" style="margin-left:1.6rem;">
      <input type="checkbox" name="hero_tonearm" value="1"<?= $hero['tonearm'] ? ' checked' : '' ?>>
      with a tonearm resting on it
    </label>
    <label class="check">
      <input type="checkbox" name="hero_covers" value="1"<?= $hero['covers'] ? ' checked' : '' ?>>
      A small round cover on each artist link
    </label>
    <label class="check">
      <input type="checkbox" name="hero_counts" value="1"<?= $hero['counts'] ? ' checked' : '' ?>>
      How many records each artist has, on their link
    </label>
    <label class="check">
      <input type="checkbox" name="hero_animate" value="1"<?= $hero['animate'] ? ' checked' : '' ?>>
      Move: the header settles in when a page opens, the numbers count up and the record turns
    </label>

    <h3 class="sub">The numbers under the intro</h3>
    <label class="check">
      <input type="checkbox" name="hero_stats" value="1"<?= $hero['stats'] ? ' checked' : '' ?>>
      Show them
    </label>
    <table class="table">
      <thead>
        <tr><th style="width:5rem;">Show</th><th>Label</th><th class="hide-sm">What it counts</th></tr>
      </thead>
      <tbody>
        <?php
        $counts = [
            'records' => 'Everything on the shelf',
            'vinyl'   => 'Records on vinyl',
            'discs'   => 'CDs, DVDs and Blu-rays',
            'oldest'  => 'The earliest release year',
        ];
        foreach (HERO_STATS as $key => $default): ?>
          <tr>
            <td><input type="checkbox" name="hero_stat_show_<?= e($key) ?>" value="1"<?= $hero['stat_show'][$key] ? ' checked' : '' ?> aria-label="Show <?= e($default) ?>"></td>
            <td><input type="text" name="hero_stat_label_<?= e($key) ?>" maxlength="24" value="<?= e($hero['stat_label'][$key]) ?>" placeholder="<?= e($default) ?>" aria-label="Label for <?= e($default) ?>"></td>
            <td class="hide-sm"><?= e($counts[$key]) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="form-actions">
      <button type="submit" class="gold">Save</button>
      <a class="btn ghost" href="<?= e(url('')) ?>" target="_blank" rel="noopener">See the site ↗</a>
      <button type="submit" name="action" value="reset_header" class="ghost small" style="margin-left:auto;"
              onclick="return confirm('Put every header setting back to how it shipped? The title, intro and artist pictures are not touched.')">Back to defaults</button>
    </div>
  </div>
</form>

<div class="card">
  <h2 style="display:flex;justify-content:space-between;align-items:baseline;gap:1rem;">
    Recent syncs
    <a class="btn ghost small" href="<?= e(url('admin_sync_runs')) ?>">See all →</a>
  </h2>
  <?php if (!$runs): ?>
    <p class="empty">Nothing yet.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr><th>Started</th><th>By</th><th>Status</th><th class="hide-sm">Collection</th><th class="hide-sm">Wantlist</th><th class="hide-sm">Details</th><th class="right hide-sm">API calls</th></tr>
      </thead>
      <tbody>
        <?php foreach ($runs as $run): ?>
          <tr>
            <td><?= e(format_date($run['started_at'], 'j M, H:i')) ?></td>
            <td><?= e($run['trigger_source']) ?></td>
            <td>
              <span class="pill <?= $run['status'] === 'ok' ? 'good' : ($run['status'] === 'error' ? 'warn' : '') ?>"><?= e($run['status']) ?></span>
              <?php if ($run['message'] && $run['status'] !== 'ok'): ?>
                <small style="opacity:0.6;display:block;"><?= e($run['message']) ?></small>
              <?php endif; ?>
            </td>
            <td class="hide-sm"><?= (int) $run['collection_seen'] ?></td>
            <td class="hide-sm"><?= (int) $run['wantlist_seen'] ?></td>
            <td class="hide-sm"><?= (int) $run['details_fetched'] ?><?= $run['details_pending'] ? ' (+' . (int) $run['details_pending'] . ' to go)' : '' ?></td>
            <td class="right hide-sm"><?= (int) $run['api_calls'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin_layout_bottom.php'; ?>
