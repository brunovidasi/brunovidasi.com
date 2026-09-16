<?php
/**
 * eBay's Marketplace Account Deletion/Closure notification endpoint.
 *
 * Required for every production app that stores any data tied to an eBay account —
 * this one stores the auth token — since 2021. There is no "we don't store
 * anything" opt-out available here; the alternative eBay offers only applies to
 * apps that genuinely keep nothing, which this one doesn't.
 *
 * Two request shapes, both from eBay's subscription system:
 *
 *  GET  ?challenge_code=...   One-time ownership check when you save the endpoint
 *       URL + verification token in the Developer Program's Notifications page.
 *       Must answer {"challengeResponse": sha256(challengeCode + verificationToken
 *       + endpointURL)} as JSON, matching eBay's documented field order and hex
 *       casing exactly, or eBay's page will report verification failed. If it does,
 *       this endpoint logs exactly what it tried (see the "Recent challenge
 *       attempts" panel on the admin dashboard) so the mismatch is visible rather
 *       than guessed at.
 *
 *  POST {...}   An actual account deletion/closure event. Must be answered 200
 *       promptly. IMPORTANT CAVEAT: unlike the ownership check, these notifications
 *       are not further signed per-request in eBay's implementation of this
 *       specific API, so this endpoint cannot cryptographically prove any single
 *       POST really came from eBay. Deliberately kept low-blast-radius as a result:
 *       the only action it can ever take is deleting a stored eBay auth token that
 *       matches a given eBay username — never anything else, and never anyone's
 *       Bidwraith login. A forged call does no more damage than one user having to
 *       reconnect their eBay account.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$token = ebay_deletion_token();

function respond_json(int $status, array $body): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($body);
    exit;
}

function log_deletion_event(?string $notificationId, ?string $username, ?int $matchedUserId, string $action): void
{
    try {
        db()->prepare('
            INSERT OR IGNORE INTO ebay_deletion_log (notification_id, ebay_username, matched_user_id, action)
            VALUES (?, ?, ?, ?)
        ')->execute([$notificationId, $username, $matchedUserId, $action]);
    } catch (Throwable $e) {
        // Logging must never be the reason a real notification goes unhandled.
    }
}

if ($token === '') {
    // Not configured yet — this is the state right after a fresh deploy, before
    // ebay_deletion_token is set. Fail loudly on GET (visible in eBay's own
    // verification UI) but still 200 a POST so eBay doesn't retry forever.
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        respond_json(200, ['status' => 'ignored', 'reason' => 'endpoint not configured']);
    }
    respond_json(503, ['error' => 'ebay_deletion_token is not configured']);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $challengeCode = $_GET['challenge_code'] ?? null;

    if ($challengeCode === null) {
        respond_json(400, ['error' => 'missing challenge_code']);
    }

    // Order and casing must match eBay's documented formula exactly:
    // sha256(challengeCode + verificationToken + endpoint), lowercase hex.
    $endpoint = ebay_deletion_url();
    $hash = hash('sha256', $challengeCode . $token . $endpoint);

    log_deletion_event(null, null, null, "challenge code={$challengeCode} endpoint={$endpoint} -> {$hash}");

    respond_json(200, ['challengeResponse' => $hash]);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond_json(405, ['error' => 'method not allowed']);
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true) ?: [];

// Field names are read defensively from a few plausible shapes rather than one
// exact path, since the response is answered 200 either way — a shape mismatch
// should show up in the log as an unmatched username, not as a dropped event.
$notificationData = $data['notification']['data'] ?? $data['data'] ?? $data;
$notificationId = $data['notification']['notificationId']
    ?? $data['notificationId']
    ?? ('sha256:' . hash('sha256', $raw));
$username = $notificationData['username'] ?? $notificationData['userId'] ?? null;

if ($username === null) {
    log_deletion_event($notificationId, null, null, 'no username/userId found in payload');
    respond_json(200, ['status' => 'received', 'matched' => false]);
}

$row = db()->prepare('SELECT id, user_id FROM ebay_accounts WHERE ebay_username = ?');
$row->execute([$username]);
$account = $row->fetch(PDO::FETCH_ASSOC);

if ($account) {
    // The only action this endpoint can ever take: remove our copy of the eBay
    // auth token for the matched account. This is a separate identity from the
    // Bidwraith login (email/password) — that account and its own data are
    // untouched; only the connection to eBay is severed, exactly as if the user
    // had clicked "Disconnect" themselves.
    db()->prepare('DELETE FROM ebay_accounts WHERE id = ?')->execute([$account['id']]);
    log_deletion_event($notificationId, $username, (int) $account['user_id'], 'deleted stored eBay token');
} else {
    log_deletion_event($notificationId, $username, null, 'no matching stored token');
}

respond_json(200, ['status' => 'received', 'matched' => (bool) $account]);
