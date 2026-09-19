<?php
/** @var string $pageTitle */
$user = current_user();
$currentPage = current_page();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(isset($pageTitle) ? 'Bidwraith · ' . $pageTitle : 'Bidwraith') ?></title>
    <?php /*
      Every link and asset path in the app is relative, which works because pretty
      URLs put every page one segment below the app root. The 404 page is the
      exception: it renders at whatever URL was asked for, and /a/b/c would resolve
      those relative paths against /a/b/. <base> pins them to the app root instead.
    */ ?>
    <base href="<?= htmlspecialchars(app_path()) ?>">
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<header class="masthead<?= $user ? ' has-nav' : '' ?>">
    <div class="masthead-inner">
        <div class="masthead-top">
            <a class="brand" href="dashboard">
                <svg class="brand-mark" width="30" height="15" viewBox="0 0 120 60" aria-hidden="true">
                    <circle cx="14" cy="46" r="4" style="fill:var(--ink)" opacity="0.18"/>
                    <circle cx="38" cy="36" r="6" style="fill:var(--ink)" opacity="0.4"/>
                    <circle cx="66" cy="24" r="9" style="fill:var(--ink)" opacity="0.72"/>
                    <circle cx="98" cy="12" r="13" style="fill:var(--accent)"/>
                </svg>
                Bidwraith
            </a>
            <?php if ($user): ?>
                <span class="masthead-tag">Automatic eBay Bidding</span>
            <?php else: ?>
                <?php /* login.php sets these so a failed attempt reopens the form with the email kept. */
                $loginError = $mastheadLoginError ?? null;
                $loginEmail = $mastheadLoginEmail ?? ''; ?>
                <div class="masthead-login<?= $loginError ? ' is-open' : '' ?>" id="mastheadLogin">
                    <button type="button" class="masthead-login-toggle secondary" id="mastheadLoginToggle" aria-expanded="<?= $loginError ? 'true' : 'false' ?>" aria-controls="mastheadLoginForm">Log in</button>
                    <form class="masthead-login-form" id="mastheadLoginForm" method="post" action="login">
                        <?= csrf_field() ?>
                        <input type="email" id="mastheadLoginEmail" name="email" placeholder="Email" aria-label="Email" autocomplete="username" required value="<?= htmlspecialchars($loginEmail) ?>">
                        <input type="password" id="mastheadLoginPassword" name="password" placeholder="Password" aria-label="Password" autocomplete="current-password" required>
                        <button type="submit">Log in</button>
                        <a class="masthead-login-forgot" href="forgot_password">Forgot password?</a>
                        <?php if ($loginError): ?><div class="masthead-login-error" role="alert"><?= htmlspecialchars($loginError) ?></div><?php endif; ?>
                    </form>
                </div>
                <?php /* Not in APP_SCRIPTS: logged-out pages don't load those, and this is all they need. */ ?>
                <script defer src="<?= asset_url('assets/js/login_menu.js') ?>"></script>
            <?php endif; ?>
        </div>
    </div>
</header>
<?php if ($user): ?>
    <div id="navSentinel" aria-hidden="true"></div>
    <nav class="site-nav" id="siteNav">
        <div class="site-nav-inner">
            <a class="site-nav-brand" href="dashboard" aria-label="Bidwraith">
                <svg class="brand-mark" width="22" height="11" viewBox="0 0 120 60" aria-hidden="true">
                    <circle cx="14" cy="46" r="4" style="fill:var(--ink)" opacity="0.18"/>
                    <circle cx="38" cy="36" r="6" style="fill:var(--ink)" opacity="0.4"/>
                    <circle cx="66" cy="24" r="9" style="fill:var(--ink)" opacity="0.72"/>
                    <circle cx="98" cy="12" r="13" style="fill:var(--accent)"/>
                </svg>
                Bidwraith
            </a>
            <div class="nav-bar">
                <button type="button" class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="navMenu" aria-label="Menu">
                    <span class="nav-toggle-bars"><span></span><span></span><span></span></span>
                </button>
                <a class="nav-brand" href="dashboard" aria-label="Bidwraith">
                    <svg class="brand-mark" width="24" height="12" viewBox="0 0 120 60" aria-hidden="true">
                        <circle cx="14" cy="46" r="4" style="fill:var(--ink)" opacity="0.18"/>
                        <circle cx="38" cy="36" r="6" style="fill:var(--ink)" opacity="0.4"/>
                        <circle cx="66" cy="24" r="9" style="fill:var(--ink)" opacity="0.72"/>
                        <circle cx="98" cy="12" r="13" style="fill:var(--accent)"/>
                    </svg>
                    Bidwraith
                </a>
                <a href="add_auction" class="nav-quick-add" aria-label="Add auction">+</a>
            </div>
            <div class="nav-menu" id="navMenu">
                <div class="nav-links">
                    <a href="dashboard" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">Auction list</a>
                    <a href="add_auction" class="<?= $currentPage === 'add_auction' ? 'active' : '' ?>">+ Add auction</a>
                    <a href="watchlist" class="<?= $currentPage === 'watchlist' ? 'active' : '' ?>">Watchlist</a>
                    <a href="connect_ebay" class="<?= $currentPage === 'connect_ebay' ? 'active' : '' ?>">eBay account</a>
                    <?php if (billing_enabled() && empty($user['is_admin'])): ?>
                        <a href="billing" class="<?= $currentPage === 'billing' ? 'active' : '' ?>">Billing</a>
                    <?php endif; ?>
                    <?php if (!empty($user['is_admin'])): ?>
                        <a href="admin" class="<?= in_array($currentPage, ['admin', 'admin_subscriptions', 'ebay_setup', 'preflight'], true) ? 'active' : '' ?>">Admin</a>
                    <?php endif; ?>
                </div>
                <div class="nav-account">
                    <span class="user-email"><?= htmlspecialchars($user['email']) ?></span>
                    <a href="logout" class="nav-logout" title="Log out" aria-label="Log out">
                        <svg class="nav-logout-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        <span class="nav-logout-label">Log out</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
<?php endif; ?>
<main class="container">
<?php
// Unconfirmed email first, else plan status (not on Billing itself, which says it in full).
$billingNotice = null;
if ($user && !user_email_verified($user)) {
    if ($currentPage !== 'verify_email') {
        $billingNotice = ['type' => 'error', 'message' => 'Confirm your email address — we sent a link to ' . $user['email'] . '.', 'href' => 'verify_email', 'label' => 'Resend →'];
    }
} elseif ($user && $currentPage !== 'billing') {
    $billingNotice = billing_notice($user);
}
if ($billingNotice): ?>
    <div class="flash flash-<?= $billingNotice['type'] === 'error' ? 'error' : 'info' ?>">
        <?= htmlspecialchars($billingNotice['message']) ?>
        <a href="<?= htmlspecialchars($billingNotice['href']) ?>"><?= htmlspecialchars($billingNotice['label']) ?></a>
    </div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash flash-<?= htmlspecialchars($_SESSION['flash']['type']) ?>">
        <?= htmlspecialchars($_SESSION['flash']['message']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
