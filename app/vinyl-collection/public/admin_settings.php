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

    set_setting('site_title', post('site_title') ?: 'The Collection');
    set_setting('site_intro', post('site_intro'));
    set_setting('detail_max_age_days', max(1, (int) post('detail_max_age_days')));
    set_setting('show_wantlist', post('show_wantlist') !== '');

    flash('Saved.');
    redirect('admin_settings');
}

$client = new DiscogsClient();
$runs = db()->query('SELECT * FROM sync_runs ORDER BY id DESC LIMIT 10')->fetchAll();

$pageTitle = 'Settings';
$pageIntro = 'How the site describes itself, and how often Discogs gets re-read.';

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
</form>

<div class="card">
  <h2>Recent syncs</h2>
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
