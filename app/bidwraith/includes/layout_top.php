<?php
/** @var string $pageTitle */
$user = current_user();
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(isset($pageTitle) ? 'Bidwraith · ' . $pageTitle : 'Bidwraith') ?></title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<header class="masthead<?= $user ? ' has-nav' : '' ?>">
    <div class="masthead-top">
        <a class="brand" href="dashboard.php">
            <svg class="brand-mark" width="30" height="15" viewBox="0 0 120 60" aria-hidden="true">
                <circle cx="14" cy="46" r="4" style="fill:var(--ink)" opacity="0.18"/>
                <circle cx="38" cy="36" r="6" style="fill:var(--ink)" opacity="0.4"/>
                <circle cx="66" cy="24" r="9" style="fill:var(--ink)" opacity="0.72"/>
                <circle cx="98" cy="12" r="13" style="fill:var(--accent)"/>
            </svg>
            Bidwraith
        </a>
        <span class="masthead-tag">Automatic eBay Bidding</span>
    </div>
    <?php if ($user): ?>
        <nav class="masthead-nav">
            <div class="nav-bar">
                <button type="button" class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="navMenu" aria-label="Menu">
                    <span class="nav-toggle-bars"><span></span><span></span><span></span></span>
                </button>
                <a class="nav-brand" href="dashboard.php" aria-label="Bidwraith">
                    <svg class="brand-mark" width="24" height="12" viewBox="0 0 120 60" aria-hidden="true">
                        <circle cx="14" cy="46" r="4" style="fill:var(--ink)" opacity="0.18"/>
                        <circle cx="38" cy="36" r="6" style="fill:var(--ink)" opacity="0.4"/>
                        <circle cx="66" cy="24" r="9" style="fill:var(--ink)" opacity="0.72"/>
                        <circle cx="98" cy="12" r="13" style="fill:var(--accent)"/>
                    </svg>
                    Bidwraith
                </a>
                <a href="add_auction.php" class="nav-quick-add" aria-label="Add auction">+</a>
            </div>
            <div class="nav-menu" id="navMenu">
                <div class="nav-links">
                    <a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">Auction list</a>
                    <a href="add_auction.php" class="<?= $currentPage === 'add_auction.php' ? 'active' : '' ?>">+ Add auction</a>
                    <a href="watchlist.php" class="<?= $currentPage === 'watchlist.php' ? 'active' : '' ?>">Watchlist</a>
                    <a href="connect_ebay.php" class="<?= $currentPage === 'connect_ebay.php' ? 'active' : '' ?>">eBay account</a>
                    <?php if (!empty($user['is_admin'])): ?>
                        <a href="admin.php" class="<?= in_array($currentPage, ['admin.php', 'ebay_setup.php', 'preflight.php'], true) ? 'active' : '' ?>">Admin</a>
                    <?php endif; ?>
                </div>
                <div class="nav-account">
                    <span class="user-email"><?= htmlspecialchars($user['email']) ?></span>
                    <a href="logout.php">Log out</a>
                </div>
            </div>
        </nav>
    <?php endif; ?>
</header>
<main class="container">
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash flash-<?= htmlspecialchars($_SESSION['flash']['type']) ?>">
        <?= htmlspecialchars($_SESSION['flash']['message']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
