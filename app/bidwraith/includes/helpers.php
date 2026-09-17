<?php

/**
 * Builds the current request's URL with some query params overridden ('' or null
 * removes a param). Used by sortable table headers and pagers, which both need to
 * change one or two params while preserving everything else already in the URL.
 */
function url_with(array $overrides): string
{
    $params = array_filter(array_merge($_GET, $overrides), fn ($v) => $v !== '' && $v !== null);
    return basename($_SERVER['SCRIPT_NAME']) . ($params ? '?' . http_build_query($params) : '');
}

/** Query params are user-controlled, so anything non-scalar (?q[]=x) becomes an empty string. */
function get_param(string $name): string
{
    $value = $_GET[$name] ?? '';
    return is_scalar($value) ? (string) $value : '';
}

/**
 * Appends a cache-busting ?v= query param (the file's mtime) to a static asset
 * path under public/. Without this, browsers that already cached app.js or
 * style.css keep serving the old version after a deploy changes them — a
 * feature can ship server-side and still be invisible to anyone with a warm
 * cache until they hard-refresh.
 */
function asset_url(string $path): string
{
    $file = __DIR__ . '/../public/' . $path;
    $version = is_file($file) ? (string) filemtime($file) : (string) time();
    return $path . '?v=' . $version;
}

/**
 * Resolves a (key, direction) sort pair from raw GET values, falling back to the
 * given defaults when unset or when the key isn't one of $allowedKeys.
 */
function resolve_sort(string $rawKey, string $rawDir, array $allowedKeys, string $defaultKey, string $defaultDir): array
{
    $key = in_array($rawKey, $allowedKeys, true) ? $rawKey : $defaultKey;
    $dir = $rawDir === 'asc' ? 'asc' : ($rawDir === 'desc' ? 'desc' : $defaultDir);
    return [$key, $dir];
}

/**
 * Renders a sortable <th>: a link that sorts by this column (flipping direction if
 * it's already the active one) and shows an arrow for the active direction, or a
 * faded neutral arrow when this column isn't the active sort. $resetParams lists
 * other params (typically a pager's page number) to clear, since a page you were on
 * may no longer make sense once the sort changes. $anchor, when given (an element id,
 * no leading '#'), is appended to the link so the reloaded page scrolls straight back
 * to that table instead of landing at the top.
 */
function sortable_th(string $label, string $key, string $activeKey, string $activeDir, string $sortParam, string $dirParam, array $resetParams = [], string $class = '', string $anchor = ''): string
{
    $isActive = $key === $activeKey;
    $nextDir = $isActive && $activeDir === 'asc' ? 'desc' : 'asc';

    $overrides = [$sortParam => $key, $dirParam => $nextDir];
    foreach ($resetParams as $param) {
        $overrides[$param] = null;
    }
    $url = url_with($overrides) . ($anchor !== '' ? '#' . $anchor : '');

    $arrow = $isActive
        ? '<span class="sort-arrow">' . ($activeDir === 'asc' ? '&#9650;' : '&#9660;') . '</span>'
        : '<span class="sort-arrow-inactive">&#8645;</span>';

    $thClass = trim($class . ' sortable-th' . ($isActive ? ' sort-active' : ''));

    return '<th class="' . htmlspecialchars($thClass) . '">'
        . '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($label) . ' ' . $arrow . '</a>'
        . '</th>';
}

/**
 * ORDER BY expression for a watched_auctions listing sorted by $key (one of item,
 * owner, end, status, result, price, topbid), shared by the admin dashboard's
 * auction tables and the past-auctions table. Assumes the query aliases
 * watched_auctions as "wa" (and, for 'owner', joins users as "u").
 */
function watched_auction_order_sql(string $key, string $dir): string
{
    $dir = $dir === 'asc' ? 'ASC' : 'DESC';
    $topBid = '(SELECT MAX(max_bid) FROM bid_steps WHERE bid_steps.watched_auction_id = wa.id)';
    // Matches the past-auctions table's Result column, which collapses anything that
    // isn't settled (still pending/bid_placed/failed after the end time) into "unknown"
    // — sorting by the raw status would scatter those rows instead of grouping them.
    $result = "CASE WHEN wa.status IN ('won', 'lost') THEN wa.status ELSE 'unknown' END";

    return match ($key) {
        'item'   => "wa.title COLLATE NOCASE $dir",
        'owner'  => "u.email COLLATE NOCASE $dir",
        'status' => "wa.status $dir",
        'result' => "$result $dir",
        'price'  => "wa.current_price IS NULL, wa.current_price $dir",
        'topbid' => "$topBid IS NULL, $topBid $dir",
        default  => "wa.end_time IS NULL, wa.end_time $dir",
    };
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function marketplace_country_code(string $marketplaceId): string
{
    // e.g. 'EBAY_AU' -> 'AU'
    $parts = explode('_', $marketplaceId);
    return end($parts);
}

/**
 * Public listing page for an item, so users can jump to it on eBay itself. Domain follows
 * the configured marketplace (e.g. EBAY_AU -> ebay.com.au); marketplaces not in the map
 * fall back to ebay.com, which eBay still resolves to the right listing.
 */
function ebay_item_view_url(string $itemId, string $marketplaceId): string
{
    $domains = [
        'AU' => 'ebay.com.au',
        'US' => 'ebay.com',
        'GB' => 'ebay.co.uk',
        'DE' => 'ebay.de',
        'FR' => 'ebay.fr',
        'IT' => 'ebay.it',
        'ES' => 'ebay.es',
        'CA' => 'ebay.ca',
        'NL' => 'ebay.nl',
        'AT' => 'ebay.at',
        'CH' => 'ebay.ch',
        'IE' => 'ebay.ie',
        'BE' => 'ebay.be',
        'PL' => 'ebay.pl',
        'HK' => 'ebay.com.hk',
        'MY' => 'ebay.com.my',
        'PH' => 'ebay.ph',
        'SG' => 'ebay.com.sg',
        'TH' => 'ebay.co.th',
        'TW' => 'ebay.com.tw',
    ];
    $domain = $domains[marketplace_country_code($marketplaceId)] ?? 'ebay.com';
    return 'https://www.' . $domain . '/itm/' . rawurlencode($itemId);
}

/**
 * Accepts either a plain eBay item ID or a pasted listing URL (e.g.
 * https://www.ebay.com/itm/Some-Title/123456789012?hash=...) and returns just the
 * numeric item ID. eBay always puts the item ID as the last path segment, so the
 * last long digit run in the URL's path is it; a bare ID is returned as-is.
 */
function extract_ebay_item_id(string $input): string
{
    $input = trim($input);
    if ($input === '' || ctype_digit($input)) {
        return $input;
    }

    $path = (string) parse_url($input, PHP_URL_PATH);
    if ($path !== '' && preg_match_all('/\d{9,15}/', $path, $matches)) {
        return end($matches[0]);
    }

    if (preg_match_all('/\d{9,15}/', $input, $matches)) {
        return end($matches[0]);
    }

    return $input;
}

/**
 * Bids fire in order as the auction end approaches, each only if an earlier,
 * lower bid hasn't already won — so a later (closer-to-end) bid must be at
 * least as large as every earlier one, or it could never trigger anything.
 * $steps: list of ['seconds_before' => int, 'max_bid' => float, 'bid_mode' => ?string].
 *
 * An uncapped 'anyway' step is exempt in both directions: without a max value its
 * eventual amount isn't decided until fire time (see resolve_anyway_bid_input()),
 * so there's nothing yet to compare it against. A capped one, or one that has
 * already fired (max_bid overwritten with what was actually bid), has a concrete
 * number and is compared normally.
 */
function validate_bid_step_ordering(array $steps): ?string
{
    $sorted = array_values(array_filter($steps, fn ($s) => !(($s['bid_mode'] ?? 'fixed') === 'anyway' && $s['max_bid'] === null)));
    usort($sorted, fn ($a, $b) => $b['seconds_before'] <=> $a['seconds_before']);

    for ($i = 1; $i < count($sorted); $i++) {
        if ($sorted[$i]['max_bid'] < $sorted[$i - 1]['max_bid']) {
            return sprintf(
                'The bid %ds before the end can\'t be smaller than the bid %ds before the end — bids closer to the end must be equal or higher.',
                $sorted[$i]['seconds_before'],
                $sorted[$i - 1]['seconds_before']
            );
        }
    }

    return null;
}

/**
 * A bid_steps row is unambiguously a "Steps" (last-minute snipe) entry at 1-60s
 * before the end, or a "Scheduled Bid" entry above that — the two never overlap,
 * so this cutoff is also how existing rows are told apart when redisplaying them.
 */
const SCHEDULED_BID_MIN_SECONDS_BEFORE = 61;

/** Keeps a scheduled bid within a sane horizon — far enough out is pointless anyway. */
const SCHEDULED_BID_MAX_SECONDS_BEFORE = 30 * 24 * 60 * 60;

/**
 * Turns the "Scheduled Bid" tab's input (hours/minutes before the end, or an exact
 * date) into a single bid_steps-shaped ['seconds_before' => int, 'max_bid' => float]
 * entry. Kept in the same shape bid_steps already uses rather than a new table,
 * since the cron firing logic (snipe_runner.php) only ever needs a seconds-before-end
 * number and doesn't care how it was derived.
 *
 * $endTime (the app-timezone 'Y-m-d H:i:s' auction end time) is required to resolve
 * either mode: 'date' mode can only become a seconds-before-end offset once the
 * actual end time is known, and 'offset' mode still needs it to confirm the
 * resulting fire time hasn't already passed.
 *
 * Returns [] if the tab was left entirely blank, ['error' => string] if it was
 * filled in but invalid, or ['seconds_before' => int, 'max_bid' => float] once valid.
 */
function resolve_scheduled_bid_input(array $post, ?string $endTime): array
{
    $mode = trim((string) ($post['scheduled_mode'] ?? ''));
    $maxBidRaw = trim((string) ($post['scheduled_max_bid'] ?? ''));
    $hoursRaw = trim((string) ($post['scheduled_hours'] ?? ''));
    $minutesRaw = trim((string) ($post['scheduled_minutes'] ?? ''));
    $dateUtcRaw = trim((string) ($post['scheduled_date_utc'] ?? ''));

    if ($maxBidRaw === '' && $hoursRaw === '' && $minutesRaw === '' && $dateUtcRaw === '') {
        return [];
    }

    if ($maxBidRaw === '' || !is_numeric($maxBidRaw) || (float) $maxBidRaw <= 0) {
        return ['error' => 'The scheduled bid needs a max bid greater than 0.'];
    }
    $maxBid = (float) $maxBidRaw;

    if ($endTime === null) {
        return ['error' => "Couldn't schedule the bid: the auction's end time isn't known yet. Enter it below and save again."];
    }
    $endTs = strtotime($endTime);

    if ($mode === 'date') {
        if ($dateUtcRaw === '' || !ctype_digit($dateUtcRaw)) {
            return ['error' => 'Pick a date and time for the scheduled bid to fire.'];
        }
        $fireAt = (int) $dateUtcRaw;
        $secondsBefore = $endTs - $fireAt;
    } elseif ($mode === 'offset') {
        if (($hoursRaw !== '' && !ctype_digit($hoursRaw)) || ($minutesRaw !== '' && !ctype_digit($minutesRaw))) {
            return ['error' => 'Hours and minutes before the end must be whole numbers.'];
        }
        $hours = $hoursRaw === '' ? 0 : (int) $hoursRaw;
        $minutes = $minutesRaw === '' ? 0 : (int) $minutesRaw;
        if ($minutes > 59) {
            return ['error' => 'Minutes before the end must be between 0 and 59.'];
        }
        if ($hours === 0 && $minutes === 0) {
            return ['error' => 'Enter hours and/or minutes before the end for the scheduled bid.'];
        }
        $secondsBefore = $hours * 3600 + $minutes * 60;
        $fireAt = $endTs - $secondsBefore;
    } else {
        return ['error' => 'Choose how the scheduled bid should be timed: hours/minutes before the end, or an exact date.'];
    }

    if ($secondsBefore < SCHEDULED_BID_MIN_SECONDS_BEFORE) {
        return ['error' => 'The scheduled bid must be timed more than a minute before the end — for anything closer, use the Steps tab instead.'];
    }
    if ($secondsBefore > SCHEDULED_BID_MAX_SECONDS_BEFORE) {
        return ['error' => "The scheduled bid can't be timed more than 30 days before the end."];
    }
    if ($fireAt <= time()) {
        return ['error' => "The scheduled bid's fire time has already passed — pick a later time or fewer hours/minutes before the end."];
    }

    return ['seconds_before' => $secondsBefore, 'max_bid' => $maxBid];
}

/**
 * Default, and minimum sane, timing for "I want the item anyway": close enough to
 * the end that almost nothing can still outbid it, but with enough margin that a
 * slow eBay response still has time to register. A user who sets fewer seconds is
 * allowed to (it's still a valid Steps-range value), just at their own risk.
 */
const ANYWAY_DEFAULT_SECONDS_BEFORE = 4;

/**
 * Turns the "I want the item anyway" tab's input into a bid_steps-shaped entry.
 * Unlike every other bid on this form, its amount is deliberately *not* fixed here
 * — it's computed at fire time from the item's live price plus increment_amount (a
 * flat value or a percentage, per increment_type), optionally capped at max_bid.
 * See the matching logic in snipe_runner.php.
 *
 * Returns [] if the tab was left entirely blank, ['error' => string] if it was
 * filled in but invalid, or ['seconds_before' => int, 'bid_mode' => 'anyway',
 * 'increment_type' => string, 'increment_amount' => float, 'max_bid' => ?float]
 * once valid (max_bid null means no cap).
 */
function resolve_anyway_bid_input(array $post): array
{
    $incrementType = trim((string) ($post['anyway_increment_type'] ?? ''));
    $incrementAmountRaw = trim((string) ($post['anyway_increment_amount'] ?? ''));
    $secondsRaw = trim((string) ($post['anyway_seconds_before'] ?? ''));
    $maxBidRaw = trim((string) ($post['anyway_max_bid'] ?? ''));

    if ($incrementAmountRaw === '' && $secondsRaw === '' && $maxBidRaw === '') {
        return [];
    }

    if (!in_array($incrementType, ['value', 'percent'], true)) {
        return ['error' => 'Choose whether "I want the item anyway" adds a fixed value or a percentage over the current price.'];
    }
    if ($incrementAmountRaw === '' || !is_numeric($incrementAmountRaw) || (float) $incrementAmountRaw <= 0) {
        return ['error' => 'Enter how much more than the current price to bid, greater than 0.'];
    }

    $seconds = $secondsRaw === '' ? ANYWAY_DEFAULT_SECONDS_BEFORE : ($secondsRaw !== '' && ctype_digit($secondsRaw) ? (int) $secondsRaw : null);
    if ($seconds === null || $seconds < 1 || $seconds > 60) {
        return ['error' => 'Seconds before end must be between 1 and 60.'];
    }

    $maxBid = null;
    if ($maxBidRaw !== '') {
        if (!is_numeric($maxBidRaw) || (float) $maxBidRaw <= 0) {
            return ['error' => 'The max value for "I want the item anyway", if set, must be greater than 0.'];
        }
        $maxBid = (float) $maxBidRaw;
    }

    return [
        'seconds_before' => $seconds,
        'bid_mode' => 'anyway',
        'increment_type' => $incrementType,
        'increment_amount' => (float) $incrementAmountRaw,
        'max_bid' => $maxBid,
    ];
}

/**
 * How a bid_steps row's amount should read to a person: a plain number for a fixed
 * step, or for an "anyway" step that has already fired (its max_bid column is
 * overwritten with what was actually bid once it does — see snipe_runner.php). A
 * still-pending "anyway" step instead shows the formula, since the real amount
 * isn't decided until fire time.
 */
function bid_step_amount_text(array $step, string $currency): string
{
    $isPendingAnyway = ($step['bid_mode'] ?? 'fixed') === 'anyway' && $step['status'] === 'pending';
    if (!$isPendingAnyway) {
        return $step['max_bid'] !== null ? $currency . ' ' . number_format((float) $step['max_bid'], 2) : '—';
    }

    $incrementLabel = $step['increment_type'] === 'percent'
        ? '+' . rtrim(rtrim(number_format((float) $step['increment_amount'], 2), '0'), '.') . '%'
        : '+' . $currency . ' ' . number_format((float) $step['increment_amount'], 2);

    return 'Current price ' . $incrementLabel . ($step['max_bid'] !== null
        ? ', up to ' . $currency . ' ' . number_format((float) $step['max_bid'], 2)
        : ', no cap');
}

/**
 * The highest amount actually at stake across an auction's bid_steps, for the
 * landed-cost estimate and the admin/auction-detail tables. A still-pending
 * "anyway" step with no cap has no fixed ceiling to include, so it's simply left
 * out rather than counted as 0 — it could end up higher than every fixed step.
 */
function bid_steps_top_amount(array $steps): float
{
    $amounts = array_filter(array_column($steps, 'max_bid'), fn ($v) => $v !== null);
    return $amounts ? (float) max($amounts) : 0.0;
}

/**
 * Renders a bid_steps seconds_before value the way a person would say it: exact
 * seconds for a last-minute Steps entry (unchanged from before Scheduled Bids
 * existed), and days/hours/minutes for anything further out.
 */
function format_seconds_before(int $seconds): string
{
    if ($seconds <= 60) {
        return $seconds . 's';
    }

    $days = intdiv($seconds, 86400);
    $hours = intdiv($seconds % 86400, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $secs = $seconds % 60;

    $parts = [];
    if ($days > 0) { $parts[] = $days . 'd'; }
    if ($hours > 0) { $parts[] = $hours . 'h'; }
    if ($minutes > 0) { $parts[] = $minutes . 'm'; }
    if ($secs > 0 && $days === 0) { $parts[] = $secs . 's'; }

    return implode(' ', $parts);
}

/**
 * What actually happened to an auction's scheduled bids, for auctions that have ended.
 * A step only leaves 'pending' when the cron reaches it (see cron/snipe.php), so steps
 * still pending after the close mean the cron never ran for them — worth flagging
 * loudly, since it's the difference between losing an auction and never bidding at all.
 * Steps left unfired because the auction was already marked won/lost are expected.
 *
 * $steps: bid_steps rows. Returns ['label', 'tone', 'detail'].
 */
function bid_outcome_summary(array $steps, ?string $auctionStatus = null): array
{
    if (!$steps) {
        return ['label' => 'No bids scheduled', 'tone' => 'muted', 'detail' => ''];
    }

    $placed = $failed = $unfired = 0;
    $lastMessage = '';

    usort($steps, fn ($a, $b) => $b['seconds_before'] <=> $a['seconds_before']);
    foreach ($steps as $step) {
        match ($step['status']) {
            'bid_placed' => $placed++,
            'failed' => $failed++,
            default => $unfired++,
        };
        if ($step['status'] !== 'pending' && !empty($step['result_message'])) {
            $lastMessage = $step['result_message'];
        }
    }

    $parts = [];
    if ($placed) { $parts[] = "$placed placed"; }
    if ($failed) { $parts[] = "$failed failed"; }
    if ($unfired) { $parts[] = "$unfired never fired"; }
    $detail = implode(', ', $parts);
    if ($lastMessage !== '') {
        $detail .= ' · ' . $lastMessage;
    }

    if ($placed === 0 && $failed === 0) {
        $settled = in_array($auctionStatus, ['won', 'lost'], true);
        return [
            'label' => 'Never fired',
            'tone' => $settled ? 'muted' : 'danger',
            'detail' => $settled ? $detail : $detail . ' — check that the cron job is running',
        ];
    }
    if ($placed === 0) {
        return ['label' => 'Bid failed', 'tone' => 'danger', 'detail' => $detail];
    }
    if ($failed > 0) {
        return ['label' => 'Partly placed', 'tone' => 'warn', 'detail' => $detail];
    }

    return ['label' => 'Bid placed', 'tone' => 'ok', 'detail' => $detail];
}

/**
 * eBay AU's published Buyer Protection fee (charged on top of the item price by
 * sellers without a Pro plan): AU$0.30 + 8% up to $20, 6% of $20-$500, 4% of
 * $500-$5000. We have no way to know from the API whether a given seller has a
 * Pro plan, so this is always an estimate, not a guarantee.
 */
function estimate_buyer_protection_fee(float $price): float
{
    $fee = 0.30;
    $fee += 0.08 * min($price, 20);
    if ($price > 20) {
        $fee += 0.06 * (min($price, 500) - 20);
    }
    if ($price > 500) {
        $fee += 0.04 * (min($price, 5000) - 500);
    }
    return round($fee, 2);
}

/**
 * Rough "what will this actually cost me" estimate: item price + shipping + the
 * Buyer Protection fee, plus GST if the item ships from outside the buyer's own
 * country (eBay collects 10% GST on low-value imports under AU$1,000 that isn't
 * already included in the listed price). Domestic AU listings already have GST
 * baked into the price where it applies, so nothing extra is added for those.
 */
function estimate_landed_cost(float $price, ?float $shippingCost, ?string $itemCountry, string $homeCountry): array
{
    $shipping = $shippingCost ?? 0.0;
    $buyerProtectionFee = estimate_buyer_protection_fee($price);
    $isOverseas = $itemCountry && strtoupper($itemCountry) !== strtoupper($homeCountry);
    $gst = ($isOverseas && ($price + $shipping) <= 1000) ? round(0.10 * ($price + $shipping), 2) : 0.0;

    return [
        'shipping' => round($shipping, 2),
        'buyer_protection_fee' => $buyerProtectionFee,
        'gst' => $gst,
        'is_overseas' => (bool) $isOverseas,
        'total' => round($price + $shipping + $buyerProtectionFee + $gst, 2),
    ];
}

/**
 * Shifts a timestamp written by SQLite into the app's timezone. The two clocks in
 * this database disagree: columns filled by datetime('now') (created_at, fired_at,
 * attempted_at, last_checked_at, price_checked_at) are UTC, while end_time is
 * written by PHP in the configured app timezone — which is what cron/snipe.php
 * compares against, so end_time is the one that must stay as it is. Anything shown
 * beside an end time, or subtracted from one, has to come through here first.
 */
function db_time_local(?string $timestamp): ?string
{
    if ($timestamp === null || $timestamp === '') {
        return null;
    }

    try {
        $dt = new DateTimeImmutable($timestamp, new DateTimeZone('UTC'));
    } catch (Exception $e) {
        return $timestamp;
    }

    return $dt->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('Y-m-d H:i:s');
}

/** Unix timestamp for a value written by SQLite's datetime('now') (UTC, no offset). */
function db_time_epoch(?string $timestamp): ?int
{
    if ($timestamp === null || $timestamp === '') {
        return null;
    }

    try {
        return (new DateTimeImmutable($timestamp, new DateTimeZone('UTC')))->getTimestamp();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Renders an absolute instant as a <span data-local-time="epoch"> that app.js
 * re-renders in the viewer's own timezone, so everyone sees their own local time
 * instead of the server's configured one. $fallbackText (typically the
 * server-timezone formatted string) is what stays on screen if JS doesn't run.
 */
function local_time(?int $epoch, string $fallbackText): string
{
    if ($epoch === null) {
        return htmlspecialchars($fallbackText);
    }

    return '<span data-local-time="' . $epoch . '">' . htmlspecialchars($fallbackText) . '</span>';
}

/**
 * Builds a chronological "what actually happened" log for one auction, merging the
 * three sources that each hold a piece of the story: the auction row (added, ended),
 * bid_steps (what was scheduled, and what the cron did with each one), and bid_log
 * (what was really sent to eBay and what eBay said back).
 *
 * The interesting cases are the gaps between those sources:
 *  - a step with a bid_log row actually reached eBay;
 *  - a step marked fired with no bid_log row failed before the API call (e.g. the
 *    user had no connected eBay account), so it gets its own event;
 *  - a step still pending once its moment has passed never fired at all — the cron
 *    didn't run — which is the failure worth shouting about.
 *
 * Returns events ordered oldest first, each ['time' => ?string, 'label' => string,
 * 'detail' => string, 'meta' => string, 'tone' => 'ok'|'danger'|'warn'|'muted'].
 */
function auction_event_timeline(array $auction, array $steps, array $log, string $currency): array
{
    $events = [];
    $endTs = $auction['end_time'] !== null ? strtotime($auction['end_time']) : null;
    $settled = in_array($auction['status'], ['won', 'lost'], true);

    // How far from the auction close a moment actually was, e.g. "4s before end".
    $relativeToEnd = function (?string $time) use ($endTs): string {
        if ($endTs === null || $time === null) {
            return '';
        }
        $delta = $endTs - strtotime($time);
        if ($delta >= 0) {
            return format_seconds_before($delta) . ' before end';
        }
        return format_seconds_before(abs($delta)) . ' after end';
    };

    $events[] = [
        'time' => db_time_local($auction['created_at']),
        'seq' => 0,
        'label' => 'Auction added to the list',
        'detail' => '',
        'meta' => '',
        'tone' => 'muted',
    ];

    $logByStep = [];
    foreach ($log as $entry) {
        $logByStep[(int) $entry['bid_step_id']][] = $entry;
    }

    foreach ($steps as $step) {
        $events[] = [
            'time' => db_time_local($step['created_at']),
            'seq' => 1,
            'label' => 'Bid scheduled: ' . bid_step_amount_text($step, $currency),
            'detail' => '',
            'meta' => 'to fire ' . format_seconds_before((int) $step['seconds_before']) . ' before the auction ends',
            'tone' => 'muted',
        ];

        $stepLog = $logByStep[(int) $step['id']] ?? [];
        $firedAt = db_time_local($step['fired_at']);

        if ($firedAt === null) {
            // Only a moment that has already passed counts as a miss; anything still
            // ahead of us is simply waiting its turn.
            $dueAt = $endTs !== null ? $endTs - (int) $step['seconds_before'] : null;
            $missed = $dueAt !== null && $dueAt < time();

            $events[] = [
                'time' => $dueAt !== null ? date('Y-m-d H:i:s', $dueAt) : null,
                'seq' => 2,
                'label' => ($missed ? 'Bid never fired: ' : 'Bid waiting: ') . bid_step_amount_text($step, $currency),
                'detail' => $missed
                    ? ($settled
                        ? 'The auction was already settled by the time this bid was due, so the cron job skipped it.'
                        : 'This bid was due here and no attempt was ever recorded — check that the cron job is running.')
                    : '',
                'meta' => format_seconds_before((int) $step['seconds_before']) . ' before end',
                'tone' => $missed ? ($settled ? 'muted' : 'danger') : 'muted',
            ];
            continue;
        }

        if (!$stepLog) {
            // Fired, but nothing reached eBay — the cron gave up before the API call.
            $events[] = [
                'time' => $firedAt,
                'seq' => 2,
                'label' => 'Bid failed before reaching eBay: ' . bid_step_amount_text($step, $currency),
                'detail' => $step['result_message'] ?? '',
                'meta' => trim(format_seconds_before((int) $step['seconds_before']) . ' step · gave up ' . $relativeToEnd($firedAt), ' ·'),
                'tone' => 'danger',
            ];
            continue;
        }

        foreach ($stepLog as $entry) {
            $ok = (bool) $entry['success'];
            $attemptedAt = db_time_local($entry['attempted_at']);
            $events[] = [
                'time' => $attemptedAt,
                'seq' => 2,
                'label' => ($ok ? 'Bid placed on eBay: ' : 'Bid rejected by eBay: ') . bid_step_amount_text($step, $currency),
                'detail' => $entry['response_summary'] ?? '',
                'meta' => trim(format_seconds_before((int) $step['seconds_before']) . ' step · fired ' . $relativeToEnd($attemptedAt), ' ·'),
                'tone' => $ok ? 'ok' : 'danger',
            ];
        }
    }

    if ($endTs !== null) {
        $ended = $endTs < time();
        $events[] = [
            'time' => $auction['end_time'],
            'seq' => 3,
            'label' => $ended ? 'Auction ended' : 'Auction ends',
            'detail' => $ended && !$settled
                ? 'The app never checked the final result on eBay, so whether you won is unknown here.'
                : '',
            'meta' => $ended ? 'final status: ' . $auction['status'] : '',
            'tone' => match (true) {
                $auction['status'] === 'won' => 'ok',
                $auction['status'] === 'lost' => 'muted',
                !$ended => 'muted',
                default => 'warn',
            },
        ];
    }

    usort($events, function ($a, $b) {
        $at = $a['time'] === null ? PHP_INT_MAX : strtotime($a['time']);
        $bt = $b['time'] === null ? PHP_INT_MAX : strtotime($b['time']);
        return [$at, $a['seq']] <=> [$bt, $b['seq']];
    });

    return $events;
}

/**
 * Seconds since cron/snipe.php last ran, or null if it never has.
 *
 * The cron job is the only thing that actually places bids, so a silently stopped
 * one is the worst failure this app has: nothing looks wrong until an auction is
 * lost. Cron writes a heartbeat on every run so the admin dashboard can say so.
 */
function cron_heartbeat_age(): ?int
{
    $file = data_dir() . '/cron-heartbeat.txt';
    if (!is_file($file)) {
        return null;
    }

    $stamp = (int) file_get_contents($file);
    return $stamp > 0 ? max(0, time() - $stamp) : null;
}

/** Human summary of cron health: [state, message] where state is ok|warn|bad. */
function cron_health(): array
{
    $age = cron_heartbeat_age();

    if ($age === null) {
        return ['bad', 'The bidding cron job has never run. No bids will be placed until it is set up.'];
    }

    if ($age <= 180) {
        return ['ok', 'Bidding cron ran ' . $age . 's ago.'];
    }

    return ['bad', 'Bidding cron last ran ' . format_duration($age) . ' ago. It should run every minute — no bids will fire until it does.'];
}

/** Rough human duration for the cron status line. */
function format_duration(int $seconds): string
{
    if ($seconds < 120) {
        return $seconds . ' seconds';
    }
    if ($seconds < 7200) {
        return floor($seconds / 60) . ' minutes';
    }
    if ($seconds < 172800) {
        return floor($seconds / 3600) . ' hours';
    }
    return floor($seconds / 86400) . ' days';
}
