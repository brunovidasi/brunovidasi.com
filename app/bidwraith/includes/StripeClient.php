<?php
/**
 * Minimal Stripe API client — just the four calls billing needs, over plain curl so
 * the app keeps its no-Composer, copy-to-any-PHP-host property.
 *
 * Stripe takes form-encoded bodies (nested params as a[b][0][c]=…), which is exactly
 * what http_build_query produces from a nested PHP array.
 */
class StripeClient
{
    private const API_BASE = 'https://api.stripe.com/v1';

    /** How far a webhook's signed timestamp may drift from now. Stripe's own default. */
    private const WEBHOOK_TOLERANCE_SECONDS = 300;

    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = billing_config()['secret_key'] ?? '';
        if ($this->secretKey === '') {
            throw new RuntimeException('Stripe is not configured (stripe.secret_key is empty).');
        }
    }

    /**
     * @throws RuntimeException on a transport failure or any non-2xx answer, with
     *         Stripe's own error message when it gave one.
     */
    private function request(string $method, string $path, array $params = []): array
    {
        $url = self::API_BASE . $path;
        $query = http_build_query($params);

        $ch = curl_init();
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERPWD => $this->secretKey . ':',
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        if ($method === 'GET') {
            $url .= $query !== '' ? '?' . $query : '';
        } else {
            $options[CURLOPT_POSTFIELDS] = $query;
        }
        $options[CURLOPT_URL] = $url;
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Could not reach Stripe: ' . $error);
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new RuntimeException("Stripe returned an unreadable response (HTTP $status).");
        }
        if ($status < 200 || $status >= 300) {
            $message = $data['error']['message'] ?? "HTTP $status";
            throw new RuntimeException('Stripe: ' . $message);
        }

        return $data;
    }

    /** @return array The Checkout Session; its 'url' is where to send the customer. */
    public function createCheckoutSession(array $params): array
    {
        return $this->request('POST', '/checkout/sessions', $params);
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        return $this->request('GET', '/checkout/sessions/' . rawurlencode($sessionId));
    }

    public function retrieveSubscription(string $subscriptionId): array
    {
        return $this->request('GET', '/subscriptions/' . rawurlencode($subscriptionId));
    }

    /** @return array The Customer Portal session; its 'url' is where to send the customer. */
    public function createPortalSession(string $customerId, string $returnUrl): array
    {
        return $this->request('POST', '/billing_portal/sessions', [
            'customer' => $customerId,
            'return_url' => $returnUrl,
        ]);
    }

    /**
     * Checks a webhook's Stripe-Signature header against the raw request body.
     *
     * The header looks like "t=1700000000,v1=<hex>[,v1=<hex>…]" and each v1 is
     * HMAC-SHA256 of "<t>.<body>" under the endpoint's signing secret. Stripe can send
     * several v1 values while a secret is being rotated, so any one matching is enough.
     * The timestamp check stops a captured request being replayed later.
     */
    public static function verifyWebhookSignature(string $payload, string $header, string $secret, ?int $now = null): bool
    {
        if ($secret === '' || $header === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) !== 2) {
                continue;
            }
            if ($pair[0] === 't') {
                $timestamp = $pair[1];
            } elseif ($pair[0] === 'v1') {
                $signatures[] = $pair[1];
            }
        }

        if ($timestamp === null || !ctype_digit($timestamp) || !$signatures) {
            return false;
        }
        if (abs(($now ?? time()) - (int) $timestamp) > self::WEBHOOK_TOLERANCE_SECONDS) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
