<?php

/**
 * Settings, in tabs: the site's name and each page's texts, the views it offers,
 * the "Add me" links, the platforms' sign-ins (Connections), the cron and what
 * a sync keeps (Sync), and the password (Account).
 */

require_once __DIR__ . '/../includes/admin.php';

$user = require_login();

const SETTINGS_TABS = [
    'site'        => 'Site & pages',
    'views'       => 'Views',
    'friends'     => 'Add me links',
    'connections' => 'Connections',
    'sync'        => 'Sync',
    'account'     => 'Account',
];

$tab = query('tab', post('tab', 'site'));
if (!isset(SETTINGS_TABS[$tab])) {
    $tab = 'site';
}
$errors = [];

/* ---------- the sign-in steps kept between showing the link and pasting the answer ---------- */

function connect_state(string $platform): array
{
    $_SESSION['connect'][$platform] ??= ['state' => b64url(random_bytes(24)), 'verifier' => b64url(random_bytes(48))];

    return $_SESSION['connect'][$platform];
}

function connect_url(string $platform): string
{
    $s = connect_state($platform);

    return match ($platform) {
        'xbox'     => XboxSync::signInUrl($s['state']),
        'nintendo' => NintendoSync::signInUrl($s['verifier'], $s['state']),
        'ea'       => EaSync::signInUrl($s['verifier']),
    };
}

/**
 * The "Send to my shelf" bookmark: on Sony's NPSSO page it takes the token, on
 * Nintendo's "Select this person" page the button's link, and brings it to
 * admin_connect in the address's #fragment, which no server ever sees. Anywhere
 * else it offers to open Sony's page.
 */
function shelf_bookmarklet(): string
{
    $js = <<<'JS'
(function(){var B=%s,h=location.hostname;
if(h==='ca.account.sony.com'){var t;try{t=JSON.parse(document.body.innerText).npsso}catch(e){}
if(t){location.href=B+'#psn='+encodeURIComponent(t);return}alert('Sign in at playstation.com first, then open this page again and click the bookmark.');return}
var a=document.querySelector('a[href^="npf5c38e31cd085304b://auth#"]');if(a){location.href=B+'#nintendo='+encodeURIComponent(a.getAttribute('href'));return}
if(/nintendo\.com$/.test(h)){alert('Click it on the "Select this person" page, after signing in.');return}
if(confirm('Send your PlayStation sign-in to your game shelf? This opens Sony\'s page for it: click the bookmark again there.'))location.href='https://ca.account.sony.com/api/v1/ssocookie';})();
JS;

    return 'javascript:' . rawurlencode(sprintf(str_replace("\n", '', $js), json_encode(base_url() . '/admin_connect')));
}

/* ---------- coming back from Microsoft's "Connect Xbox" and "Sign in through Steam" (through admin_connect) ---------- */

$oauth = query('oauth') ?: ((isset($_GET['code']) || isset($_GET['error'])) && isset($_GET['state']) ? 'xbox' : '');
if (!is_post() && $tab === 'connections' && in_array($oauth, ['xbox', 'steam'], true)) {
    $expected = $_SESSION['connect'][$oauth . '_return']['state'] ?? null;
    unset($_SESSION['connect'][$oauth . '_return']);
    try {
        if (!$expected || !hash_equals($expected, query('state'))) {
            throw new RuntimeException('That sign-in was started somewhere else, or was already used: start it again here.');
        }
        if (!secrets_ready()) {
            throw new RuntimeException('Set app_key in the config first: sign-ins are kept encrypted with it.');
        }
        if ($oauth === 'xbox') {
            if (query('error') !== '') {
                throw new RuntimeException('Microsoft said: ' . query('error_description', query('error')));
            }
            $account = XboxSync::connectApp(query('code'));
            set_connection_status('xbox', 'ok', null, $account, true);
            flash('Xbox is connected' . ($account ? " as $account" : '') . '. Sync it from the dashboard.');
        } else {
            $steamId = SteamSync::verifySignIn($_GET);
            set_credential('steam', 'steam_id', $steamId);
            if (credential('steam', 'api_key') !== null) {
                $account = SteamSync::check();
                set_connection_status('steam', 'ok', null, $account, true);
                flash('Signed in through Steam' . ($account ? " as $account" : '') . '. Steam is connected.');
            } else {
                flash("Signed in through Steam: your SteamID ($steamId) is filled in. Add the Web API key to finish.");
            }
        }
    } catch (Throwable $e) {
        flash(platform_name($oauth) . ': ' . $e->getMessage(), 'error');
    }
    redirect('admin_settings?tab=connections#' . $oauth);
}

/* ---------- saving ---------- */

if (is_post()) {
    csrf_verify();
    $action = post('action');

    switch ($action) {
        case 'site':
            $name = post('name');
            if ($name === '') {
                $errors[] = 'The site needs a name.';
                break;
            }
            set_setting('site', ['name' => $name, 'accent' => post('accent')]);
            $pages = [];
            foreach (PAGE_DEFAULTS as $page => $defaults) {
                foreach (array_keys($defaults) as $key) {
                    $value = trim((string) ($_POST['pages'][$page][$key] ?? ''));
                    $pages[$page][$key] = $value === '' ? $defaults[$key] : $value;
                }
            }
            set_setting('pages', $pages);
            touch_data();
            flash('The site\'s name and pages were saved.');
            break;

        case 'views':
            $enabled = array_values(array_intersect(array_keys(VIEWS), (array) ($_POST['views'] ?? [])));
            if (!$enabled) {
                $errors[] = 'Keep at least one view.';
                break;
            }
            $default = post('default_view');
            set_setting('views', ['enabled' => $enabled, 'default' => in_array($default, $enabled, true) ? $default : $enabled[0]]);
            touch_data();
            flash('The views were saved. Someone who last picked a view that is now off gets the default one.');
            break;

        case 'friends':
            $friends = [];
            foreach (FRIEND_DEFAULTS as $key => $defaults) {
                $url = trim((string) ($_POST['friends'][$key]['url'] ?? ''));
                if ($url !== '' && empty($defaults['copy']) && !safe_http_url($url)) {
                    $errors[] = FRIEND_NAMES[$key] . ': the link must start with https://';
                    continue;
                }
                $friends[$key] = [
                    'url' => $url,
                    'show' => !empty($_POST['friends'][$key]['show']) && $url !== '',
                    'long' => trim((string) ($_POST['friends'][$key]['long'] ?? '')) ?: $defaults['long'],
                    'label' => trim((string) ($_POST['friends'][$key]['label'] ?? '')) ?: $defaults['label'],
                ];
            }
            if (!$errors) {
                set_setting('friends', $friends);
                touch_data();
                flash('The "Add me" links were saved.');
            }
            break;

        case 'connect':
            $platform = post('platform');
            try {
                if (!secrets_ready()) {
                    throw new RuntimeException('Set app_key in the config first: sign-ins are kept encrypted with it.');
                }
                $account = match ($platform) {
                    'steam' => (function () {
                        if (post('api_key') !== '') {
                            set_credential('steam', 'api_key', preg_replace('/\s+/', '', post('api_key')));
                        }
                        if (!preg_match('/^\d{17}$/', post('steam_id'))) {
                            throw new RuntimeException('The SteamID64 is 17 digits (steamid.io finds it from the profile\'s address).');
                        }
                        set_credential('steam', 'steam_id', post('steam_id'));
                        if (credential('steam', 'api_key') === null) {
                            throw new RuntimeException('Add the Web API key too.');
                        }
                        return SteamSync::check();
                    })(),
                    'psn'      => PsnSync::connect(post('npsso')),
                    'xbox'     => XboxSync::connect(post('link'), connect_state('xbox')['state']),
                    'nintendo' => NintendoSync::connect(post('link'), connect_state('nintendo')['verifier'], connect_state('nintendo')['state']),
                    'ea'       => EaSync::connect(post('link'), connect_state('ea')['verifier']),
                    'rockstar' => RockstarSync::connect($_POST['curl'] ?? ''),
                    default    => throw new RuntimeException('No such platform.'),
                };
                unset($_SESSION['connect'][$platform]);
                set_connection_status($platform, 'ok', null, $account, true);
                flash(platform_name($platform) . ' is connected' . ($account ? " as $account" : '') . '. Sync it from the dashboard.');
            } catch (Throwable $e) {
                $errors[] = platform_name($platform) . ': ' . $e->getMessage();
                $_GET['open'] = $platform;
            }
            break;

        case 'xbox_app':
            // your own Microsoft app, for the "Connect Xbox" button
            try {
                if (!secrets_ready()) {
                    throw new RuntimeException('Set app_key in the config first.');
                }
                if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', post('app_id'))) {
                    throw new RuntimeException('The Application (client) ID looks like 1a2b3c4d-…, 36 letters, digits and dashes.');
                }
                set_credential('xbox', 'app_id', strtolower(post('app_id')));
                if (post('app_secret') !== '') {
                    set_credential('xbox', 'app_secret', post('app_secret'));
                }
                if (credential('xbox', 'app_secret') === null) {
                    throw new RuntimeException('Add the client secret too (its Value, not its Secret ID).');
                }
                flash('Your Microsoft app is saved. Now press Connect Xbox.');
            } catch (Throwable $e) {
                $errors[] = 'Xbox: ' . $e->getMessage();
            }
            break;

        case 'forget':
            $platform = post('platform');
            if (isset(PLATFORMS[$platform])) {
                forget_connection($platform);
                unset($_SESSION['connect'][$platform]);
                flash('The ' . platform_name($platform) . ' sign-in was forgotten. What it synced stays.');
            }
            break;

        case 'cron_token':
            set_credential('cron', 'token', new_token());
            flash('A new cron address was made. The old one no longer works: update the scheduler.');
            break;

        case 'cron_off':
            set_credential('cron', 'token', null);
            flash('The cron address was turned off.');
            break;

        case 'sync':
            set_setting('sync', [
                'cron_platforms' => array_values(array_intersect(array_keys(PLATFORMS), (array) ($_POST['cron_platforms'] ?? []))),
                'keep_snapshots' => max(1, min(100, (int) post('keep_snapshots', '10'))),
            ]);
            flash('Saved.');
            break;

        case 'password':
            $error = change_password((int) $user['id'], $_POST['current'] ?? '', $_POST['new'] ?? '', $_POST['confirm'] ?? '');
            if ($error) {
                $errors[] = $error;
            } else {
                flash('The password was changed.');
            }
            break;
    }

    if (!$errors) {
        redirect('admin_settings?tab=' . $tab);
    }
}

/* ---------- the page ---------- */

$site = site_settings();
$pages = page_settings();
$views = views_settings();
$friends = friend_settings();
$syncSettings = (array) setting('sync', []) + ['cron_platforms' => array_keys(PLATFORMS), 'keep_snapshots' => 10];

admin_header('Settings');

foreach ($errors as $error) {
    echo flash_box($error, 'error');
}
?>

<nav class="tabs" aria-label="Settings">
  <?php foreach (SETTINGS_TABS as $key => $label): ?>
    <a href="<?= e(url('admin_settings?tab=' . $key)) ?>"<?= $tab === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<?php if ($tab === 'site'): ?>
  <form method="post" class="settings">
    <?= csrf_field() ?>
    <input type="hidden" name="tab" value="site">
    <section class="card">
      <h2>The site</h2>
      <div class="fields">
        <div class="field"><label for="name">Name</label><input type="text" id="name" name="name" value="<?= e($site['name']) ?>" required><div class="hint">In the top bar, and in the admin.</div></div>
        <div class="field"><label for="accent">Words in blue</label><input type="text" id="accent" name="accent" value="<?= e($site['accent']) ?>"><div class="hint">The end of the name, in the accent colour ("Game Shelf").</div></div>
      </div>
    </section>

    <?php foreach (PAGE_DEFAULTS as $page => $defaults): $p = $pages[$page]; ?>
      <section class="card">
        <h2><?= e(PAGE_NAMES[$page]) ?> <span class="muted small"><?= $page === 'shelf' ? url('') : url('?' . $page) ?></span></h2>
        <div class="fields">
          <div class="field"><label>In the menu</label><input type="text" name="pages[<?= $page ?>][nav]" value="<?= e($p['nav']) ?>" placeholder="<?= e($defaults['nav']) ?>"></div>
          <div class="field"><label>Title</label><input type="text" name="pages[<?= $page ?>][title]" value="<?= e($p['title']) ?>" placeholder="<?= e($defaults['title']) ?>"><div class="hint">The browser tab's title<?= $page === 'shelf' ? '' : ', before the site\'s' ?>, and what search engines show.</div></div>
          <div class="field wide"><label>Description</label><textarea name="pages[<?= $page ?>][description]" rows="2" placeholder="<?= e($defaults['description']) ?>"><?= e($p['description']) ?></textarea><div class="hint">What search engines and shared links show under the title.</div></div>
          <?php if ($page !== 'shelf'): ?>
            <div class="field"><label>Above the heading</label><input type="text" name="pages[<?= $page ?>][eyebrow]" value="<?= e($p['eyebrow']) ?>" placeholder="<?= e($defaults['eyebrow']) ?>"></div>
            <div class="field"><label>Heading</label>
              <span class="heading-pair"><input type="text" name="pages[<?= $page ?>][heading]" value="<?= e($p['heading']) ?>" placeholder="<?= e($defaults['heading']) ?>" aria-label="Heading"><input type="text" name="pages[<?= $page ?>][accent]" value="<?= e($p['accent']) ?>" placeholder="<?= e($defaults['accent']) ?>" aria-label="Heading, the words in colour"></span>
              <div class="hint">The second box is the words in colour.</div></div>
            <div class="field wide"><label>Introduction</label><textarea name="pages[<?= $page ?>][intro]" rows="3" placeholder="<?= e($defaults['intro']) ?>"><?= e($p['intro']) ?></textarea><?php if ($page === 'gta'): ?><div class="hint">{firstYear} becomes the year of the first game.</div><?php endif; ?></div>
          <?php endif; ?>
        </div>
      </section>
    <?php endforeach; ?>
    <div class="save-bar"><button type="submit" name="action" value="site" class="primary">Save</button></div>
  </form>

<?php elseif ($tab === 'views'): ?>
  <form method="post" class="settings">
    <?= csrf_field() ?>
    <input type="hidden" name="tab" value="views">
    <section class="card">
      <h2>The views the site offers</h2>
      <p class="muted small">The buttons beside the search. The Sims and GTA pages add their Checklist to these.</p>
      <div class="view-picks">
        <?php foreach (VIEWS as $key => $label): ?>
          <div class="view-pick">
            <?= check_box('views[]', in_array($key, $views['enabled'], true), $label, '', $key) ?>
            <label class="radio"><input type="radio" name="default_view" value="<?= e($key) ?>"<?= $views['default'] === $key ? ' checked' : '' ?>> First one shown</label>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
    <div class="save-bar"><button type="submit" name="action" value="views" class="primary">Save</button></div>
  </form>

<?php elseif ($tab === 'friends'): ?>
  <form method="post" class="settings">
    <?= csrf_field() ?>
    <input type="hidden" name="tab" value="friends">
    <section class="card">
      <h2>"Add me" links</h2>
      <p class="muted small">The buttons in the top bar (and the strip under it on phones) that let people add you as a friend.</p>
      <?php foreach ($friends as $key => $f): ?>
        <div class="friend-row">
          <span class="friend-icon <?= $key ?>"><?= FRIEND_ICONS[$key] ?></span>
          <div class="fields">
            <div class="field wide"><?php if ($f['copy']): ?><label for="fr-<?= $key ?>"><?= e(FRIEND_NAMES[$key]) ?> ID <span class="muted">(there is no link to add a friend, so the button copies it)</span></label><input type="text" id="fr-<?= $key ?>" name="friends[<?= $key ?>][url]" value="<?= e($f['url']) ?>"><?php else: ?><label for="fr-<?= $key ?>"><?= e(FRIEND_NAMES[$key]) ?> link</label><input type="url" id="fr-<?= $key ?>" name="friends[<?= $key ?>][url]" value="<?= e($f['url']) ?>" placeholder="https://"><?php endif; ?></div>
            <div class="field"><label>Its words</label><input type="text" name="friends[<?= $key ?>][long]" value="<?= e($f['long']) ?>"></div>
            <div class="field"><label>On a phone</label><input type="text" name="friends[<?= $key ?>][label]" value="<?= e($f['label']) ?>"></div>
            <?= check_box("friends[$key][show]", $f['show'], 'Show it') ?>
          </div>
        </div>
      <?php endforeach; ?>
    </section>
    <div class="save-bar"><button type="submit" name="action" value="friends" class="primary">Save</button></div>
  </form>

<?php elseif ($tab === 'connections'): ?>
  <?php if (!secrets_ready()): ?>
    <?= flash_box('There is no app_key in the config, so no sign-in can be saved. Add one (config.example.php says how) and reload.', 'error') ?>
  <?php endif; ?>
  <p class="muted">What each sync needs to sign in. Everything here is kept in the database, encrypted, and never shown again once saved. Renewed sign-ins are saved by the syncs themselves, so most of this is done once.</p>

  <section class="card bookmark-card">
    <div>
      <h2>The "Send to my shelf" bookmark</h2>
      <p class="muted small">For PlayStation and Nintendo, which have no sign-in for other sites. Drag the button to your browser's bookmarks bar, once. Then, on Sony's or Nintendo's sign-in page, click it, and the sign-in comes straight here, without copying anything. It only ever sends to <?= e(base_url()) ?><?= is_production() ? '' : ' (drag it again from the site once it is online)' ?>.</p>
    </div>
    <a class="button primary bookmarklet" href="<?= e(shelf_bookmarklet()) ?>" title="Drag me to the bookmarks bar" onclick="alert('Drag this button to your bookmarks bar, then use it on Sony\'s or Nintendo\'s page.'); return false;">★ Send to my shelf</a>
  </section>

  <?php foreach (PLATFORMS as $platform => $p):
      $conn = connection_info($platform);
      $connected = platform_sync($platform)->connected();
      $dates = credential_dates($platform);
      $open = !$connected || query('open') === $platform || ($_GET['open'] ?? '') === $platform;
      ?>
    <section class="card connection" id="<?= e($platform) ?>">
      <div class="card-head">
        <h2><span class="dot <?= e($platform) ?>"></span> <?= e($p['name']) ?></h2>
        <?= $connected ? status_pill($conn['status'] === 'unset' ? 'ok' : $conn['status']) : status_pill('unset') ?>
      </div>
      <p class="muted small"><?= e($p['about']) ?><?= $p['unofficial'] ? ' Unofficial: it can stop working when the platform changes it.' : '' ?></p>
      <?php if ($connected): ?>
        <p class="small">
          <?= $conn['account'] ? 'Signed in as <b>' . e($conn['account']) . '</b>. ' : '' ?>
          Saved <?= e(time_ago(max($dates ?: ['']))) ?>.
          <?php if ($platform === 'psn' && ($exp = credential('psn', 'refresh_expires'))): ?>Runs out <?= e(format_date(gmdate('Y-m-d', (int) $exp))) ?> unless a sync renews it.<?php endif; ?>
          <?php if ($conn['status'] === 'error' && $conn['message']): ?><span class="error-text"><?= e($conn['message']) ?></span><?php endif; ?>
        </p>
      <?php endif; ?>

      <?php if ($platform === 'steam'): ?>
        <p class="quick">
          <a class="button" href="<?= e(SteamSync::signInUrl(base_url() . '/admin_connect?oauth=steam&state=' . connect_state('steam_return')['state'])) ?>">Sign in through Steam</a>
          <span class="muted small">Fills in your SteamID. The Web API key is still pasted once below: Steam has no way to hand it over.</span>
        </p>
      <?php elseif ($platform === 'xbox'): ?>
        <?php if (XboxSync::hasApp()): ?>
          <p class="quick">
            <a class="button primary" href="<?= e(XboxSync::appSignInUrl(connect_state('xbox_return')['state'])) ?>"><?= $connected ? 'Connect Xbox again' : 'Connect Xbox' ?></a>
            <span class="muted small">Signs in with Microsoft and comes straight back here.</span>
          </p>
        <?php endif; ?>
        <details class="app-setup" <?= XboxSync::hasApp() ? '' : 'open' ?>>
          <summary><?= XboxSync::hasApp() ? 'Your Microsoft app' : 'For a "Connect Xbox" button: register a Microsoft app, once' ?></summary>
          <ol class="steps">
            <li>Open <a href="https://portal.azure.com/#view/Microsoft_AAD_RegisteredApps/ApplicationsListBlade" target="_blank" rel="noopener">App registrations</a> in the Azure portal, signed in with any Microsoft account (it is free, and needs no subscription), and choose <i>New registration</i>.</li>
            <li>Name it anything (<i><?= e(site_settings()['name']) ?></i>). Under <i>Supported account types</i> pick <b>Personal Microsoft accounts only</b>.</li>
            <li>Under <i>Redirect URI</i> pick <b>Web</b> and give it:
              <div class="copy-row"><input type="text" readonly value="<?= e(XboxSync::appRedirectUri()) ?>" id="xboxRedirect" aria-label="Redirect URI"><button type="button" data-copy="xboxRedirect">Copy</button></div>
              <?php if (!is_production()): ?><span class="muted small">When the site is online, add <code>https://brunovida.si/games/admin_connect</code> too (<i>Authentication</i> → <i>Add URI</i>).</span><?php endif; ?></li>
            <li><i>Register</i>, and copy the <b>Application (client) ID</b> from its page.</li>
            <li>In <i>Certificates &amp; secrets</i>, make a <i>New client secret</i> (24 months is the longest) and copy its <b>Value</b>. When it runs out, make a new one and save it here.</li>
          </ol>
          <form method="post" class="connect-form" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="tab" value="connections">
            <div class="fields">
              <div class="field"><label for="app_id">Application (client) ID</label><input type="text" id="app_id" name="app_id" value="<?= e(credential('xbox', 'app_id')) ?>" placeholder="1a2b3c4d-…" spellcheck="false"></div>
              <div class="field"><label for="app_secret">Client secret (its Value)</label><input type="password" id="app_secret" name="app_secret" placeholder="<?= credential('xbox', 'app_secret') !== null ? 'saved; leave empty to keep it' : '' ?>"></div>
            </div>
            <div class="form-actions"><button type="submit" name="action" value="xbox_app"<?= secrets_ready() ? '' : ' disabled' ?>>Save the app</button></div>
          </form>
        </details>
      <?php endif; ?>

      <details <?= $open && !($platform === 'xbox' && XboxSync::hasApp()) ? 'open' : '' ?>>
        <summary><?= $platform === 'xbox' ? 'Or without an app of your own' : ($connected ? 'Connect again' : 'Connect') ?></summary>
        <form method="post" class="connect-form" autocomplete="off">
          <?= csrf_field() ?>
          <input type="hidden" name="tab" value="connections">
          <input type="hidden" name="platform" value="<?= e($platform) ?>">

          <?php if ($platform === 'steam'): ?>
            <ol class="steps">
              <li>Get a Web API key at <a href="https://steamcommunity.com/dev/apikey" target="_blank" rel="noopener">steamcommunity.com/dev/apikey</a>.</li>
              <li>The SteamID64 is the 17 digits in the profile's address, or <a href="https://steamid.io" target="_blank" rel="noopener">steamid.io</a> finds it.</li>
              <li>The profile's <i>Game details</i> must be public for play time and achievements.</li>
            </ol>
            <div class="fields">
              <div class="field"><label for="api_key">Web API key</label><input type="password" id="api_key" name="api_key" placeholder="<?= isset($dates['api_key']) ? 'saved; leave empty to keep it' : '32 letters and digits' ?>"></div>
              <div class="field"><label for="steam_id">SteamID64</label><input type="text" id="steam_id" name="steam_id" inputmode="numeric" value="<?= e(credential('steam', 'steam_id')) ?>" placeholder="7656119…"></div>
            </div>

          <?php elseif ($platform === 'psn'): ?>
            <ol class="steps">
              <li>Sign in at <a href="https://www.playstation.com" target="_blank" rel="noopener">playstation.com</a>.</li>
              <li>In the same browser, open <a href="https://ca.account.sony.com/api/v1/ssocookie" target="_blank" rel="noopener">ca.account.sony.com/api/v1/ssocookie</a>.</li>
              <li>Click <b>Send to my shelf</b> in your bookmarks bar (above). Or copy the <code>npsso</code> value (64 letters and digits) and paste it here.</li>
            </ol>
            <p class="muted small">It is traded at once for a sign-in that lasts about two months; the token itself isn't kept.</p>
            <div class="field"><label for="npsso">NPSSO token</label><input type="password" id="npsso" name="npsso" required></div>

          <?php elseif ($platform === 'xbox'): ?>
            <ol class="steps">
              <li><a href="<?= e(connect_url('xbox')) ?>" target="_blank" rel="noopener">Open Microsoft's sign-in</a> and sign in with the account your Xbox profile uses.</li>
              <li>The page goes blank afterwards. Copy the whole address from the address bar: it starts with <code>https://login.live.com/oauth20_desktop.srf?code=</code>.</li>
            </ol>
            <div class="field"><label for="xbox-link">The address</label><input type="password" id="xbox-link" name="link" required></div>

          <?php elseif ($platform === 'nintendo'): ?>
            <ol class="steps">
              <li><a href="<?= e(connect_url('nintendo')) ?>" target="_blank" rel="noopener">Open the Nintendo Account sign-in</a> and sign in (it says it is linking to <i>Nintendo Store</i>).</li>
              <li>On the <i>Select this person</i> page, don't click the button: click <b>Send to my shelf</b> in your bookmarks bar (above). Or right-click the button, <i>Copy Link Address</i> (it starts with <code>npf5c38e31cd085304b://auth#</code>) and paste it here.</li>
            </ol>
            <div class="field"><label for="nin-link">The link</label><input type="password" id="nin-link" name="link" required></div>

          <?php elseif ($platform === 'ea'): ?>
            <ol class="steps">
              <li>Open the browser's developer tools on the <i>Network</i> tab and tick <i>Preserve log</i>.</li>
              <li><a href="<?= e(connect_url('ea')) ?>" target="_blank" rel="noopener">Open EA's sign-in</a> in that tab and sign in.</li>
              <li>Afterwards the page seems to do nothing. Type <code>connect/auth</code> in the Network filter, click the last <code>auth?…</code> request (status 302) and copy the value of the <code>location</code> response header. It starts with <code>qrc:///html/login_successful.html?code=</code>.</li>
            </ol>
            <div class="field"><label for="ea-link">The location</label><input type="password" id="ea-link" name="link" required></div>

          <?php elseif ($platform === 'rockstar'): ?>
            <ol class="steps">
              <li>Sign in at <a href="https://www.rockstargames.com" target="_blank" rel="noopener">rockstargames.com</a> (tick <i>Keep me signed in</i>).</li>
              <li>Open the developer tools on the <i>Network</i> tab and type <code>check/rsg</code> in the filter. Wait five minutes and reload: a POST named <code>rsg</code> shows up.</li>
              <li>Right-click it → <i>Copy</i> → <i>Copy as cURL (bash)</i>, and paste it here.</li>
            </ol>
            <div class="field wide"><label for="rs-curl">The request, as cURL</label><textarea class="code secret" id="rs-curl" name="curl" rows="4" required spellcheck="false"></textarea></div>
          <?php endif; ?>

          <div class="form-actions">
            <button type="submit" name="action" value="connect" class="primary"<?= secrets_ready() ? '' : ' disabled' ?>>Save &amp; check</button>
          </div>
        </form>
      </details>

      <?php if ($connected): ?>
        <form method="post" class="inline forget">
          <?= csrf_field() ?>
          <input type="hidden" name="tab" value="connections">
          <input type="hidden" name="platform" value="<?= e($platform) ?>">
          <button type="submit" name="action" value="forget" class="small ghost" data-confirm="Forget the <?= e($p['name']) ?> sign-in? What it synced stays.">Forget the sign-in</button>
        </form>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>

<?php elseif ($tab === 'sync'): ?>
  <?php $token = credential('cron', 'token'); $cronUrl = $token ? base_url() . '/cron_sync.php?token=' . rawurlencode($token) : null; ?>
  <section class="card">
    <h2>Syncing on a schedule</h2>
    <?php if ($cronUrl): ?>
      <p>Any scheduler that can open an address syncs every connected platform with:</p>
      <div class="copy-row"><input type="text" readonly value="<?= e($cronUrl) ?>" id="cronUrl" aria-label="Cron address"><button type="button" data-copy="cronUrl">Copy</button></div>
      <p class="muted small">Add <code>&amp;platform=steam</code> for one platform only. It answers at once and syncs after hanging up, about a minute or two in all. On the server, a cron job of the host can also run it from the command line, without the address:</p>
      <pre class="code">17 4 * * * /usr/local/bin/php <?= e(dirname(__DIR__)) ?>/cron/sync.php >> <?= e(data_dir()) ?>/sync.log 2>&amp;1</pre>
      <form method="post" class="inline">
        <?= csrf_field() ?><input type="hidden" name="tab" value="sync">
        <button type="submit" name="action" value="cron_token" class="ghost" data-confirm="Make a new address? The old one stops working.">Make a new address</button>
        <button type="submit" name="action" value="cron_off" class="ghost" data-confirm="Turn the cron address off?">Turn it off</button>
      </form>
    <?php else: ?>
      <p>The cron address is off. Make one to sync on a schedule; it carries a secret token, so only a scheduler that has it can start a sync.</p>
      <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="tab" value="sync"><button type="submit" name="action" value="cron_token" class="primary">Make a cron address</button></form>
    <?php endif; ?>
  </section>

  <form method="post" class="settings">
    <?= csrf_field() ?>
    <input type="hidden" name="tab" value="sync">
    <section class="card">
      <h2>What the cron syncs</h2>
      <div class="checks">
        <?php foreach (PLATFORMS as $platform => $p): ?>
          <?= check_box('cron_platforms[]', in_array($platform, $syncSettings['cron_platforms'], true), $p['name'], '', $platform) ?>
        <?php endforeach; ?>
      </div>
      <p class="muted small">A platform that isn't connected is passed over.</p>
    </section>
    <section class="card">
      <h2>What each sync keeps</h2>
      <div class="field"><label for="keep_snapshots">Answers kept per platform</label><input type="number" id="keep_snapshots" name="keep_snapshots" min="1" max="100" value="<?= (int) $syncSettings['keep_snapshots'] ?>"><div class="hint">What a platform answered, to download from a sync's page. The values a sync changed are always kept, so it can be undone.</div></div>
    </section>
    <div class="save-bar"><button type="submit" name="action" value="sync" class="primary">Save</button></div>
  </form>

<?php elseif ($tab === 'account'): ?>
  <form method="post" class="settings">
    <?= csrf_field() ?>
    <input type="hidden" name="tab" value="account">
    <section class="card narrow">
      <h2>Password</h2>
      <p class="muted small">Signed in as <?= e($user['email']) ?>, the only account there is.</p>
      <div class="field"><label for="current">Current password</label><input type="password" id="current" name="current" autocomplete="current-password" required></div>
      <div class="field"><label for="new">New password</label><input type="password" id="new" name="new" autocomplete="new-password" minlength="10" required><div class="hint">At least 10 characters.</div></div>
      <div class="field"><label for="confirm">New password again</label><input type="password" id="confirm" name="confirm" autocomplete="new-password" required></div>
    </section>
    <div class="save-bar"><button type="submit" name="action" value="password" class="primary">Change the password</button></div>
  </form>
<?php endif; ?>

<?php admin_footer(); ?>
