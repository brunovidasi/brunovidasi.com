<?php
/**
 * Deployment diagnostics. The server is reachable only through DirectAdmin's file
 * manager and cron UI — no shell — so this page stands in for the commands you'd
 * normally run over SSH: it resolves the absolute paths, finds the PHP CLI binary,
 * and prints the exact cron line to paste.
 *
 * Access: admin only, EXCEPT while the database has no users yet, since on a fresh
 * deploy there is no admin to log in as. If the database can't be opened at all it
 * still reports what it can, because that's precisely when it's needed.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$dbError = null;
$openAccess = false;

try {
    $openAccess = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0;
} catch (Throwable $e) {
    $dbError = $e->getMessage();
    $openAccess = true;
}

if (!$openAccess) {
    require_admin();
}

/** @return array{0:bool,1:string} pass/fail plus a human explanation. */
function check(bool $ok, string $yes, string $no): array
{
    return [$ok, $ok ? $yes : $no];
}

$projectRoot = dirname(__DIR__);
$dataDir = data_dir();

$checks = [];

$checks['PHP version'] = check(
    PHP_VERSION_ID >= 80000,
    PHP_VERSION,
    PHP_VERSION . ' — this app needs PHP 8.0 or newer'
);
$checks['pdo_sqlite extension'] = check(extension_loaded('pdo_sqlite'), 'loaded', 'MISSING — the app cannot store anything');
$checks['curl extension'] = check(extension_loaded('curl'), 'loaded', 'MISSING — the app cannot reach eBay');

$checks['Instance directory'] = check(
    instance_dir() !== null,
    instance_dir() ?? '',
    'not found — create "bidwraith-instance" above public_html (see below)'
);
$checks['Config file'] = check(file_exists(config_path()), config_path(), 'missing at ' . config_path());
$checks['Environment'] = check(true, app_env() . (is_production() ? '' : '  (NOT production)'), '');
$checks['Error display'] = check(!is_debug() || !is_production(), is_debug() ? 'on (development)' : 'off (production)', 'debug is ON in production');

$checks['Data directory'] = check(is_dir($dataDir), $dataDir, 'does not exist: ' . $dataDir);
$checks['Data directory writable'] = check(is_dir($dataDir) && is_writable($dataDir), 'yes', 'NOT writable — the app cannot save anything');
$checks['Database'] = $dbError !== null
    ? [false, 'could not open: ' . $dbError]
    : check(file_exists(db_path()), db_path() . '  (' . number_format(filesize(db_path()) / 1024, 1) . ' KB)', 'not created yet');

$underWebRoot = str_starts_with(realpath($dataDir) ?: $dataDir, realpath($projectRoot) ?: $projectRoot);
$checks['Database outside web root'] = check(
    !$underWebRoot,
    'yes — not reachable over HTTP',
    'NO: the data directory is inside the deployed folder. Anyone who can defeat the .htaccess rules could download the database, which holds eBay auth tokens.'
);

// SCRIPT_NAME points at the real file on disk, REQUEST_URI at what was asked for.
// They only differ by the /public/ segment when the rewrite is doing its job.
$rewriteWorking = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/public/')
    && !str_contains($_SERVER['REQUEST_URI'] ?? '', '/public/');
$checks['.htaccess rewrite'] = check(
    $rewriteWorking,
    'active — served from public/ behind a clean URL',
    'not active. You reached this page directly rather than through the rewrite, or mod_rewrite is unavailable.'
);

$checks['HTTPS'] = check(request_is_https(), 'yes', 'this request was plain HTTP');

$missingKeys = ebay_keys_missing();
$checks['eBay credentials (' . ebay_api() . ')'] = check(
    !$missingKeys,
    'all present',
    'blank: ' . implode(', ', $missingKeys)
);

$heartbeatFile = $dataDir . '/cron-heartbeat.txt';
if (is_file($heartbeatFile)) {
    $age = time() - (int) file_get_contents($heartbeatFile);
    $checks['Cron last ran'] = check(
        $age < 180,
        $age . 's ago',
        $age . 's ago — the cron job looks stopped. It must run every minute or bids will not fire.'
    );
} else {
    $checks['Cron last ran'] = [false, 'never — the cron job has not run yet'];
}

// DirectAdmin hosts vary in where the PHP CLI binary lives, and it is NOT the same
// binary serving this page. Probe the usual locations so the cron line can be exact.
$candidates = array_merge(
    ['/usr/local/bin/php', '/usr/bin/php'],
    glob('/usr/local/php*/bin/php') ?: [],
    glob('/opt/alt/php*/usr/bin/php') ?: [],
    glob('/opt/cpanel/ea-php*/root/usr/bin/php') ?: []
);
$cliPaths = array_values(array_unique(array_filter($candidates, 'is_executable')));
$phpBinary = $cliPaths[0] ?? '/usr/local/bin/php';

$cronLine = sprintf(
    '* * * * * %s %s/cron/snipe.php >> %s/cron.log 2>&1',
    $phpBinary,
    $projectRoot,
    $dataDir
);

$failures = count(array_filter($checks, fn ($c) => !$c[0]));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preflight — Bidwraith</title>
    <style>
        body { font: 14px/1.6 -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; padding: 24px; background: #f6f5f2; color: #1c1b19; }
        .wrap { max-width: 860px; margin: 0 auto; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .sub { color: #6b6862; margin: 0 0 24px; }
        .banner { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
        .banner.ok { background: #e3f3e6; color: #1d5b2a; }
        .banner.bad { background: #fbe6e4; color: #8a2318; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; }
        td { padding: 10px 14px; border-bottom: 1px solid #ece9e3; vertical-align: top; }
        tr:last-child td { border-bottom: 0; }
        td.name { width: 220px; font-weight: 600; white-space: nowrap; }
        td.state { width: 28px; }
        .pass { color: #2c7a3f; } .fail { color: #b3341f; }
        td.val { word-break: break-all; }
        pre { background: #1c1b19; color: #f3f1ec; padding: 14px; border-radius: 8px; overflow-x: auto; font-size: 13px; }
        h2 { font-size: 15px; margin: 28px 0 8px; }
        ol { padding-left: 20px; } li { margin-bottom: 6px; }
        code { background: #ece9e3; padding: 1px 5px; border-radius: 4px; word-break: break-all; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Preflight</h1>
    <p class="sub">Deployment diagnostics for Bidwraith.</p>

    <?php if ($failures === 0): ?>
        <div class="banner ok">All <?= count($checks) ?> checks passed.</div>
    <?php else: ?>
        <div class="banner bad"><?= $failures ?> of <?= count($checks) ?> checks need attention.</div>
    <?php endif; ?>

    <?php if ($openAccess && $dbError === null): ?>
        <div class="banner bad">No accounts exist yet, so this page is open to anyone.
            Create your admin account at <a href="setup_admin.php">setup_admin.php</a> — that closes both pages.</div>
    <?php endif; ?>

    <table>
        <?php foreach ($checks as $name => [$ok, $detail]): ?>
            <tr>
                <td class="state <?= $ok ? 'pass' : 'fail' ?>"><?= $ok ? '&#10003;' : '&#10007;' ?></td>
                <td class="name"><?= htmlspecialchars($name) ?></td>
                <td class="val"><?= htmlspecialchars($detail) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Cron job</h2>
    <p>In DirectAdmin &rarr; <strong>Cron Jobs</strong>, create a job running every minute
       (<code>*</code> in all five fields) with this command. Leave the notification email
       blank, or you will receive 1,440 emails a day.</p>
    <pre><?= htmlspecialchars($cronLine) ?></pre>
    <p>PHP CLI binaries found on this server:
        <?= $cliPaths ? '<code>' . implode('</code>, <code>', array_map('htmlspecialchars', $cliPaths)) . '</code>' : 'none at the usual paths — check DirectAdmin for the correct one' ?>.
    </p>

    <h2>Paths</h2>
    <table>
        <tr><td class="name">Project root</td><td class="val"><?= htmlspecialchars($projectRoot) ?></td></tr>
        <tr><td class="name">Instance directory</td><td class="val"><?= htmlspecialchars(instance_dir() ?? 'not found') ?></td></tr>
        <tr><td class="name">Expected location</td><td class="val"><?= htmlspecialchars(dirname($projectRoot, 3) . '/bidwraith-instance') ?></td></tr>
        <tr><td class="name">Base URL</td><td class="val"><?= htmlspecialchars(base_url()) ?></td></tr>
        <tr><td class="name">eBay callback URL</td><td class="val"><?= htmlspecialchars(ebay_callback_url()) ?></td></tr>
        <tr><td class="name">REQUEST_URI</td><td class="val"><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?></td></tr>
        <tr><td class="name">SCRIPT_NAME</td><td class="val"><?= htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? '') ?></td></tr>
    </table>
</div>
</body>
</html>
