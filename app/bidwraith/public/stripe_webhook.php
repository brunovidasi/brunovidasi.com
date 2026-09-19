<?php
/**
 * Stripe's webhook endpoint. Register it in the Stripe dashboard (Developers ->
 * Webhooks) as {base_url}/stripe_webhook.php, subscribed to:
 *
 *   checkout.session.completed
 *   customer.subscription.created
 *   customer.subscription.updated
 *   customer.subscription.deleted
 *   customer.subscription.trial_will_end
 *
 * and put the endpoint's signing secret in config as stripe.webhook_secret.
 *
 * Every request is authenticated by its Stripe-Signature header, so — unlike eBay's
 * deletion notices — a forged call is rejected outright.
 *
 * The event only says "something about this subscription changed". It isn't used as
 * the data: the handler re-reads the subscription from Stripe and stores that, which
 * makes handling idempotent and immune to events arriving out of order. Payment
 * failures need no event of their own: they move the subscription to past_due, which
 * arrives here as customer.subscription.updated.
 *
 * Answering non-2xx makes Stripe retry with backoff, so a transient failure (Stripe
 * unreachable, database locked) is safe. An event that is simply not ours to handle
 * gets a 200 so it isn't retried forever.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

function webhook_respond(int $status, string $message): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['status' => $message]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    webhook_respond(405, 'POST only');
}

$secret = (string) (billing_config()['webhook_secret'] ?? '');
if ($secret === '' || !billing_enabled()) {
    // Not configured yet: refuse, so the dashboard shows the delivery as failing
    // rather than quietly succeeding while nothing is being recorded.
    webhook_respond(503, 'webhook not configured');
}

$payload = (string) file_get_contents('php://input');
if (!StripeClient::verifyWebhookSignature($payload, $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '', $secret)) {
    webhook_respond(400, 'invalid signature');
}

$event = json_decode($payload, true);
$type = is_array($event) ? (string) ($event['type'] ?? '') : '';
$object = is_array($event) ? ($event['data']['object'] ?? null) : null;
if (!is_array($object)) {
    webhook_respond(400, 'malformed event');
}

try {
    switch ($type) {
        case 'checkout.session.completed':
            $subscriptionId = $object['subscription'] ?? null;
            $userId = ctype_digit((string) ($object['client_reference_id'] ?? '')) ? (int) $object['client_reference_id'] : null;
            if ($subscriptionId && $userId !== null) {
                sync_subscription((string) $subscriptionId, $userId);
            }
            break;

        case 'customer.subscription.trial_will_end':
            billing_notify_trial_ending($object);
            break;

        case 'customer.subscription.created':
        case 'customer.subscription.updated':
        case 'customer.subscription.deleted':
            if (!empty($object['id'])) {
                sync_subscription((string) $object['id']);
            }
            break;
    }
} catch (Throwable $e) {
    error_log('Bidwraith Stripe webhook (' . $type . '): ' . $e->getMessage());
    webhook_respond(500, 'processing failed, retry');
}

webhook_respond(200, 'ok');
