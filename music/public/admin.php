<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$counts = db()->query("
    SELECT source, media_kind, COUNT(*) AS n
      FROM items
     WHERE missing_since IS NULL
     GROUP BY source, media_kind
")->fetchAll();

$byKind = [];
$collection = 0;
$wantlist = 0;
foreach ($counts as $row) {
    if ($row['source'] === 'collection') {
        $byKind[$row['media_kind']] = ($byKind[$row['media_kind']] ?? 0) + (int) $row['n'];
        $collection += (int) $row['n'];
    } elseif ($row['source'] === 'wantlist') {
        $wantlist += (int) $row['n'];
    }
}

$searching = (int) db()->query("SELECT COUNT(*) FROM items WHERE source = 'searching'")->fetchColumn();
$missing = (int) db()->query('SELECT COUNT(*) FROM items WHERE missing_since IS NOT NULL')->fetchColumn();
$hidden = (int) db()->query('SELECT COUNT(*) FROM items WHERE is_visible = 0')->fetchColumn();
$pendingDetails = count_pending_details();
$lastRun = latest_sync_run();

$client = new DiscogsClient();
$cronUrl = cron_token() !== '' ? base_url() . '/cron_sync.php?token=' . rawurlencode(cron_token()) : null;

$pageTitle = 'Dashboard';
$pageIntro = 'Everything on the shelf, as the site sees it.';
$pageScript = 'js/admin-sync.js';

require __DIR__ . '/../includes/admin_layout_top.php';
?>

<div class="stats">
  <div class="stat"><b><?= $collection ?></b><span>In the collection</span></div>
  <?php foreach (MEDIA_KINDS as $kind => $label): ?>
    <?php if (!empty($byKind[$kind])): ?>
      <div class="stat"><b><?= (int) $byKind[$kind] ?></b><span><?= e($label) ?></span></div>
    <?php endif; ?>
  <?php endforeach; ?>
  <div class="stat"><b><?= $wantlist + $searching ?></b><span>Wanted &amp; hunting</span></div>
</div>

<?php if ($missing || $hidden || $pendingDetails): ?>
  <p style="margin:1rem 0 0;font-size:0.85rem;">
    <?php if ($pendingDetails): ?><span class="pill"><?= $pendingDetails ?> release<?= $pendingDetails === 1 ? '' : 's' ?> still to fetch in full</span> <?php endif; ?>
    <?php if ($hidden): ?><span class="pill"><?= $hidden ?> hidden from the site</span> <?php endif; ?>
    <?php if ($missing): ?><a class="pill warn" href="<?= e(url('admin_items?state=missing')) ?>"><?= $missing ?> no longer on Discogs</a><?php endif; ?>
  </p>
<?php endif; ?>

<div class="card" style="margin-top:1.4rem;" id="syncCard"
     data-endpoint="<?= e(url('admin_sync')) ?>"
     data-csrf="<?= e(csrf_token()) ?>"
     data-running="<?= $lastRun && $lastRun['status'] === 'running' ? (int) $lastRun['id'] : '' ?>">
  <h2>Sync with Discogs</h2>
  <p>Pulls the collection and the wantlist, then fills in each release's full detail — tracklist, images, identifiers, barcode. It picks up where it left off, so stopping halfway costs nothing.</p>

  <?php if (!$client->hasToken()): ?>
    <div class="flash error">
      No Discogs token is set in the config. Syncing still works, but at 25 API calls a minute instead of 60 — roughly twice as long for a first run. Add a personal access token to speed it up.
    </div>
  <?php endif; ?>

  <div class="sync-state">
    <button type="button" id="syncStart" class="gold">Sync now</button>
    <span id="syncStatus">
      <?php if ($lastRun): ?>
        Last run <?= e(time_ago($lastRun['started_at'])) ?> (<?= e($lastRun['status']) ?>)<?= $lastRun['message'] ? ' — ' . e($lastRun['message']) : '' ?>
      <?php else: ?>
        Never synced.
      <?php endif; ?>
    </span>
  </div>

  <div class="sync-bar" id="syncBar" hidden><i></i></div>
  <pre class="sync-log" id="syncLog" hidden></pre>
</div>

<div class="card">
  <h2>Daily sync by cron</h2>
  <p>Give this URL to any scheduler that can fetch a page once a day. It answers immediately and keeps working in the background.</p>

  <?php if ($cronUrl === null): ?>
    <div class="flash error">No <code>cron_token</code> is set in the config, so the endpoint is switched off. Generate one with <code>php -r "echo bin2hex(random_bytes(24));"</code> and add it.</div>
  <?php else: ?>
    <code class="token-url" id="cronUrl"><?= e($cronUrl) ?></code>
    <div class="form-actions">
      <button type="button" class="ghost small" id="copyCron">Copy URL</button>
      <span class="hint" style="font-size:0.76rem;opacity:0.6;">Treat it like a password — anyone with it can start a sync.</span>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Where things are</h2>
  <table class="table">
    <tbody>
      <tr><td>Discogs account</td><td class="right"><?= e(discogs_username() ?: '— not set —') ?></td></tr>
      <tr><td>API token</td><td class="right"><?= $client->hasToken() ? '<span class="pill good">set</span>' : '<span class="pill warn">missing</span>' ?></td></tr>
      <tr><td>Environment</td><td class="right"><?= e(app_env()) ?></td></tr>
      <tr><td>Config file</td><td class="right" style="font-size:0.78rem;word-break:break-all;"><?= e(config_path()) ?></td></tr>
      <tr><td>Database</td><td class="right" style="font-size:0.78rem;word-break:break-all;"><?= e(db_path()) ?></td></tr>
      <tr><td>Last successful sync</td><td class="right"><?= e(time_ago(setting('last_successful_sync'))) ?></td></tr>
    </tbody>
  </table>
</div>

<?php if ($lastRun && !empty($lastRun['log'])): ?>
  <div class="card">
    <h2>Last run</h2>
    <p>
      Started <?= e(format_date($lastRun['started_at'], 'j M Y, H:i')) ?> ·
      <?= (int) $lastRun['collection_seen'] ?> in collection ·
      <?= (int) $lastRun['wantlist_seen'] ?> wanted ·
      <?= (int) $lastRun['details_fetched'] ?> details ·
      <?= (int) $lastRun['api_calls'] ?> API calls
    </p>
    <pre class="sync-log"><?= e(trim($lastRun['log'])) ?></pre>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/admin_layout_bottom.php'; ?>
