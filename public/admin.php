<?php

/** The dashboard: the collection in figures, each platform's sync, what needs a look, and where things are. */

require_once __DIR__ . '/../includes/admin.php';

require_login();

$games = all_games();
$fig = collection_figures($games);

// what needs a look
$unmatched = db()->query('SELECT * FROM sync_unmatched WHERE dismissed = 0 ORDER BY platform, reason, name')->fetchAll();
$checks = array_filter($games, fn ($g) => !empty($g['check']));
$noCover = array_filter($games, fn ($g) => empty($g['cover']['file']) && empty($g['hidden']));
$coverFiles = array_filter(array_map(fn ($g) => $g['cover']['file'] ?? null, $games));
$missingFiles = array_filter($coverFiles, fn ($f) => !is_file(public_dir() . '/' . $f));
$hidden = array_filter($games, fn ($g) => !empty($g['hidden']));

$psnExpires = credential('psn', 'refresh_expires');
$psnDays = $psnExpires ? (int) floor(((int) $psnExpires - time()) / 86400) : null;

// where things are
$dbSize = is_file(db_path()) ? filesize(db_path()) : 0;
$commit = null;
$head = dirname(__DIR__) . '/.git/HEAD';
if (is_file($head)) {
    $ref = trim((string) file_get_contents($head));
    $commit = str_starts_with($ref, 'ref: ') ? @file_get_contents(dirname(__DIR__) . '/.git/' . substr($ref, 5)) : $ref;
    $commit = $commit ? substr(trim($commit), 0, 7) : null;
}
$cronToken = credential('cron', 'token');
$cronUrl = $cronToken ? base_url() . '/cron_sync.php?token=' . rawurlencode($cronToken) : null;
$lastCron = db()->query("SELECT * FROM sync_runs WHERE trigger_source = 'cron' ORDER BY id DESC LIMIT 1")->fetch() ?: null;

$familyNames = ['playstation' => 'PlayStation', 'nintendo' => 'Nintendo', 'pc' => 'PC', 'xbox' => 'Xbox'];

admin_header('Dashboard', 'Everything on the shelf, as the site counts it.');
?>

<section class="stats" aria-label="The collection in figures">
  <a class="stat big" href="<?= e(url('admin_games?flag=onsite')) ?>"><b><?= number_format($fig['games']) ?></b><span>Games</span></a>
  <a class="stat" href="<?= e(url('admin_games?format=physical&flag=onsite')) ?>"><b><?= number_format($fig['physical']) ?></b><span>Physical</span></a>
  <a class="stat" href="<?= e(url('admin_games?format=digital&flag=onsite')) ?>"><b><?= number_format($fig['digital']) ?></b><span>Digital</span></a>
  <a class="stat" href="#byConsole"><b><?= count($fig['consoles']) ?></b><span>Consoles</span></a>
  <?php foreach ($fig['families'] as $family => $n): ?>
    <a class="stat fam-<?= e($family) ?>" href="<?= e(url('admin_games?console=' . rawurlencode('@' . $family) . '&flag=onsite')) ?>"><b><?= $n ?></b><span><?= e($familyNames[$family] ?? $family) ?></span></a>
  <?php endforeach; ?>
  <a class="stat add" href="<?= e(url('admin_game')) ?>"><b aria-hidden="true">+</b><span>Add a game</span></a>
</section>

<section class="card sync-board" id="syncBoard" data-endpoint="<?= e(url('admin_sync')) ?>">
  <div class="card-head">
    <h2>Syncs</h2>
    <button type="button" class="primary" data-sync-all>Sync all</button>
  </div>
  <div class="sync-grid">
    <?php foreach (PLATFORMS as $platform => $p):
        $client = platform_sync($platform);
        $conn = connection_info($platform);
        $last = latest_sync_run($platform);
        $lastOk = latest_ok_sync_run($platform);
        $account = platform_account($platform);
        $connected = $client->connected();
        ?>
      <article class="sync-tile" data-platform="<?= e($platform) ?>" data-connected="<?= $connected ? '1' : '0' ?>">
        <header>
          <span class="dot <?= e($platform) ?>"></span>
          <h3><?= e($p['name']) ?></h3>
          <?= $connected ? status_pill($conn['status'] === 'unset' ? 'ok' : $conn['status']) : status_pill('unset') ?>
        </header>
        <?php if ($conn['account']): ?><p class="muted small">Signed in as <?= e($conn['account']) ?></p><?php endif; ?>
        <dl>
          <dt>Last synced</dt>
          <dd data-last><?php if ($lastOk): ?><span title="<?= e(format_datetime($lastOk['finished_at'])) ?>"><?= e(time_ago($lastOk['finished_at'])) ?></span><?php elseif ($account): ?><span title="Imported from the old JSON file">From the old file, <?= e(format_date($account['synced'] ?? null)) ?></span><?php else: ?>Never<?php endif; ?></dd>
          <?php if ($last && $last['status'] === 'error'): ?>
            <dt>Last try</dt><dd class="error-text"><a href="<?= e(url('admin_sync_run?id=' . $last['id'])) ?>"><?= e($last['message']) ?></a></dd>
          <?php elseif ($lastOk): ?>
            <dt>Result</dt><dd><a href="<?= e(url('admin_sync_run?id=' . $lastOk['id'])) ?>"><?= e($lastOk['message']) ?></a></dd>
          <?php endif; ?>
        </dl>
        <p class="sync-out" data-out aria-live="polite"></p>
        <footer>
          <?php if ($connected): ?>
            <button type="button" data-sync="<?= e($platform) ?>">Sync <?= e($p['short']) ?></button>
          <?php else: ?>
            <a class="button ghost" href="<?= e(url('admin_settings?tab=connections#' . $platform)) ?>">Connect</a>
          <?php endif; ?>
          <?php if ($p['unofficial']): ?><span class="muted small" title="Undocumented: the platform can change it at any time, and every sync is kept so one can be undone.">Unofficial API</span><?php endif; ?>
        </footer>
      </article>
    <?php endforeach; ?>
  </div>
  <?php if ($cronUrl): ?>
    <p class="muted small">Cron: <?= $lastCron ? 'last ran ' . e(time_ago($lastCron['started_at'])) : 'not run yet' ?> · the address is in <a href="<?= e(url('admin_settings?tab=sync')) ?>">Settings → Sync</a>.</p>
  <?php else: ?>
    <p class="muted small">No cron address yet: make one in <a href="<?= e(url('admin_settings?tab=sync')) ?>">Settings → Sync</a> to sync on a schedule.</p>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Needs a look</h2>
  <ul class="attention">
    <?php if ($missingFiles): ?>
      <li class="warn"><b><?= count($missingFiles) ?></b> <?= plural(count($missingFiles), 'cover') ?> named in the database <?= count($missingFiles) === 1 ? 'is' : 'are' ?> not in <code>public/covers/</code> on this server yet<?= is_production() ? ': upload the covers folder by FTP' : '' ?>.</li>
    <?php endif; ?>
    <?php if ($psnDays !== null && $psnDays < 14): ?>
      <li class="warn">The PSN sign-in runs out in <?= max(0, $psnDays) ?> <?= plural(max(0, $psnDays), 'day') ?>: <a href="<?= e(url('admin_settings?tab=connections#psn')) ?>">connect it again</a> with a new NPSSO token.</li>
    <?php endif; ?>
    <?php if ($noCover): ?>
      <li><a href="<?= e(url('admin_games?flag=nocover')) ?>"><b><?= count($noCover) ?></b> <?= plural(count($noCover), 'game') ?> without a cover</a></li>
    <?php endif; ?>
    <?php if ($checks): ?>
      <li><a href="<?= e(url('admin_games?flag=check')) ?>"><b><?= count($checks) ?></b> <?= plural(count($checks), 'game') ?> with something still to check</a></li>
    <?php endif; ?>
    <?php if ($hidden): ?>
      <li><a href="<?= e(url('admin_games?flag=hidden')) ?>"><b><?= count($hidden) ?></b> hidden from the site</a></li>
    <?php endif; ?>
    <?php if (!$unmatched && !$missingFiles && !$noCover && !$checks): ?>
      <li class="muted">Nothing. Everything synced is tied to a copy.</li>
    <?php endif; ?>
  </ul>

  <?php if ($unmatched): ?>
    <h3 class="sub-head">To tie by hand <span class="count"><?= count($unmatched) ?></span></h3>
    <p class="muted small">Games a sync found but couldn't tie to one copy: none on the shelf has that name, or several could be it. Tie one to its copy, or dismiss it (a game played but not owned).</p>
    <datalist id="gameIds">
      <?php foreach ($games as $g): ?><option value="<?= e($g['id']) ?>"><?= e($g['title'] . ' · ' . console_name($g['console'])) ?></option><?php endforeach; ?>
    </datalist>
    <div class="table-wrap">
      <table class="table compact">
        <thead><tr><th>Platform</th><th>Game</th><th>Why</th><th>Tie to</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($unmatched as $u): $cands = json_column($u['candidates_json']); ?>
          <tr>
            <td><span class="dot <?= e($u['platform']) ?>"></span> <?= e(PLATFORMS[$u['platform']]['short'] ?? $u['platform']) ?></td>
            <td><?= e($u['name']) ?><div class="muted small"><code><?= e($u['ext_id']) ?></code></div></td>
            <td><?= $u['reason'] === 'ambiguous' ? 'Could be ' . count($cands) . ' copies' : 'No copy with that name' ?></td>
            <td>
              <form method="post" action="<?= e(url('admin_unmatched')) ?>" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="platform" value="<?= e($u['platform']) ?>">
                <input type="hidden" name="collection" value="<?= e($u['collection']) ?>">
                <input type="hidden" name="ext_id" value="<?= e($u['ext_id']) ?>">
                <?php if ($cands): ?>
                  <select name="game_id" aria-label="Copy"><?= options_html(array_combine($cands, array_map(fn ($id) => ($games[$id]['title'] ?? $id) . ' · ' . console_name($games[$id]['console'] ?? ''), $cands))) ?></select>
                <?php else: ?>
                  <input type="text" name="game_id" list="gameIds" placeholder="a copy's id" aria-label="Copy" size="22">
                <?php endif; ?>
                <button type="submit" name="action" value="tie" class="small">Tie</button>
              </form>
            </td>
            <td>
              <form method="post" action="<?= e(url('admin_unmatched')) ?>" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="platform" value="<?= e($u['platform']) ?>">
                <input type="hidden" name="collection" value="<?= e($u['collection']) ?>">
                <input type="hidden" name="ext_id" value="<?= e($u['ext_id']) ?>">
                <button type="submit" name="action" value="dismiss" class="small ghost">Dismiss</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<section class="card">
  <h2>About the site</h2>
  <dl class="facts">
    <dt>Address</dt><dd><a href="<?= e(base_url() . '/') ?>" target="_blank" rel="noopener"><?= e(base_url()) ?>/</a> <span class="pill"><?= e(app_env()) ?></span></dd>
    <dt>Data last changed</dt><dd><?= e(format_date(data_updated())) ?> <span class="muted">· version <?= e(data_version()) ?></span></dd>
    <dt>Copies in the database</dt><dd><?= count($games) ?> <span class="muted">(<?= count($hidden) ?> hidden)</span></dd>
    <dt>Covers</dt><dd><?= count($coverFiles) - count($missingFiles) ?> of <?= count($coverFiles) ?> on this server</dd>
    <dt>Views on the site</dt><dd><?= e(implode(', ', array_map(fn ($v) => VIEWS[$v], views_settings()['enabled']))) ?></dd>
    <dt>Database</dt><dd><code><?= e(db_path()) ?></code> <span class="muted">· <?= e(format_bytes($dbSize)) ?></span></dd>
    <dt>Config</dt><dd><code><?= e(config_path()) ?></code><?= instance_dir() ? '' : ' <span class="muted">(no games-instance directory: development)</span>' ?></dd>
    <dt>Saved sign-ins</dt><dd><?= secrets_ready() ? 'Encrypted with the config\'s app_key' : '<span class="error-text">No app_key in the config: sign-ins can\'t be saved</span>' ?></dd>
    <dt>Software</dt><dd>PHP <?= e(PHP_VERSION) ?> · SQLite <?= e(db()->query('SELECT sqlite_version()')->fetchColumn()) ?> · GD <?= function_exists('gd_info') ? e(gd_info()['GD Version']) : '<span class="error-text">missing</span>' ?><?= $commit ? ' · commit <code>' . e($commit) . '</code>' : '' ?></dd>
    <dt>Signed in as</dt><dd><?= e(current_user()['email']) ?></dd>
  </dl>
</section>

<div class="grid-2">
  <section class="card" id="byConsole">
    <h2>By console</h2>
    <ul class="bars">
      <?php $max = max($fig['consoles'] ?: [1]); ?>
      <?php foreach ($fig['consoles'] as $console => $n): ?>
        <li>
          <a href="<?= e(url('admin_games?console=' . rawurlencode($console) . '&flag=onsite')) ?>"><?= e(console_name($console)) ?></a>
          <span class="bar fam-<?= e(console_family($console)) ?>" style="--w: <?= round($n / $max * 100, 1) ?>%"></span>
          <b><?= $n ?></b>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="card">
    <h2>PC launchers</h2>
    <ul class="bars">
      <?php $max = max($fig['launchers'] ?: [1]); ?>
      <?php foreach ($fig['launchers'] as $launcher => $n): ?>
        <li>
          <a href="<?= e(url('admin_games?launcher=' . rawurlencode($launcher) . '&flag=onsite')) ?>"><?= e(LAUNCHERS[$launcher] ?? $launcher) ?></a>
          <span class="bar" style="--w: <?= round($n / $max * 100, 1) ?>%"></span>
          <b><?= $n ?></b>
        </li>
      <?php endforeach; ?>
    </ul>
    <p class="muted small">A bundle counts as the packs in it, a copy on two consoles once, and hidden copies not at all, as on the site.</p>
  </section>
</div>

<?php admin_footer(); ?>
