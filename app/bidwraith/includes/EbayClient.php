<?php
/**
 * Thin wrapper around the two eBay APIs this app needs:
 *  - OAuth (client credentials) + Buy Browse API, to look up an item's title/end time.
 *  - The legacy Trading API (XML), which is what actually supports placing a proxy bid
 *    (PlaceOffer with Action=Bid). This is why bidding needs the older Auth'n'Auth user
 *    token rather than a modern OAuth user token — eBay never ported bidding to OAuth.
 */
class EbayClient
{
    private array $cfg;
    private string $environment;

    public function __construct()
    {
        $this->cfg = ebay_config();
        $this->environment = $this->cfg['environment'];
    }

    private function isSandbox(): bool
    {
        return $this->environment === 'sandbox';
    }

    private function tokenEndpoint(): string
    {
        return $this->isSandbox()
            ? 'https://api.sandbox.ebay.com/identity/v1/oauth2/token'
            : 'https://api.ebay.com/identity/v1/oauth2/token';
    }

    private function browseEndpoint(): string
    {
        return $this->isSandbox()
            ? 'https://api.sandbox.ebay.com/buy/browse/v1'
            : 'https://api.ebay.com/buy/browse/v1';
    }

    private function tradingEndpoint(): string
    {
        return $this->isSandbox()
            ? 'https://api.sandbox.ebay.com/ws/api.dll'
            : 'https://api.ebay.com/ws/api.dll';
    }

    private function signInEndpoint(): string
    {
        return $this->isSandbox()
            ? 'https://signin.sandbox.ebay.com/ws/eBayISAPI.dll'
            : 'https://signin.ebay.com/ws/eBayISAPI.dll';
    }

    private function httpPost(string $url, array $headers, string $body): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            throw new RuntimeException("eBay request failed: $error");
        }

        return [$status, $response];
    }

    private function httpGet(string $url, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            throw new RuntimeException("eBay request failed: $error");
        }

        return [$status, $response];
    }

    /**
     * App-level OAuth token (client credentials grant). Used only for read-only
     * catalog lookups (Browse API) — never for bidding. Cached in the app_tokens
     * table so we're not re-fetching it on every page load.
     */
    public function getAppAccessToken(): string
    {
        $stmt = db()->prepare('SELECT access_token, expires_at FROM app_tokens WHERE environment = ?');
        $stmt->execute([$this->environment]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && strtotime($row['expires_at']) > time() + 60) {
            return $row['access_token'];
        }

        $basic = base64_encode($this->cfg['app_id'] . ':' . $this->cfg['cert_id']);
        [$status, $body] = $this->httpPost(
            $this->tokenEndpoint(),
            [
                'Authorization: Basic ' . $basic,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            http_build_query([
                'grant_type' => 'client_credentials',
                'scope' => 'https://api.ebay.com/oauth/api_scope',
            ])
        );

        $data = json_decode($body, true);
        if ($status !== 200 || empty($data['access_token'])) {
            throw new RuntimeException('Could not get eBay app token: ' . $body);
        }

        $expiresAt = date('Y-m-d H:i:s', time() + (int) $data['expires_in']);
        db()->prepare('
            INSERT INTO app_tokens (environment, access_token, expires_at) VALUES (?, ?, ?)
            ON CONFLICT(environment) DO UPDATE SET access_token = excluded.access_token, expires_at = excluded.expires_at
        ')->execute([$this->environment, $data['access_token'], $expiresAt]);

        return $data['access_token'];
    }

    /**
     * Dev-only shortcut: item ID "1" always resolves to a canned mock item instead of
     * calling out to eBay, so the add-auction flow (lookup result, max bid, bid tabs)
     * can be exercised locally without depending on the Sandbox having matching test
     * data. Gated on is_production() so it can never fire on a real deployment.
     */
    private function mockItem(): array
    {
        // An inline data-URI placeholder rather than an external image URL, so the
        // mock has no network dependency of its own and renders offline.
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300">'
            . '<rect width="100%" height="100%" fill="#ddd"/>'
            . '<text x="50%" y="50%" font-family="sans-serif" font-size="20" text-anchor="middle" dominant-baseline="middle" fill="#555">Mock Item</text>'
            . '</svg>';

        return [
            'title' => 'Mock Item (dev only) — Vintage Widget',
            'end_time' => date('c', time() + 3600),
            'current_price' => 42.50,
            'shipping_cost' => 5.00,
            'item_country' => 'US',
            'image_url' => 'data:image/svg+xml;base64,' . base64_encode($svg),
        ];
    }

    /**
     * Looks up an item by the plain numeric ID from an ebay.com listing URL.
     * Note: in the Sandbox environment this only finds items that exist in your own
     * sandbox seller test data — real production item IDs won't resolve there.
     * Returns null if the item can't be found (caller should fall back to manual entry).
     */
    public function getItemByLegacyId(string $legacyItemId): ?array
    {
        if ($legacyItemId === '1' && !is_production()) {
            return $this->mockItem();
        }

        $token = $this->getAppAccessToken();
        $url = $this->browseEndpoint() . '/item/get_item_by_legacy_id?legacy_item_id=' . urlencode($legacyItemId);

        [$status, $body] = $this->httpGet($url, [
            'Authorization: Bearer ' . $token,
            'X-EBAY-C-MARKETPLACE-ID: ' . $this->cfg['marketplace_id'],
        ]);

        if ($status !== 200) {
            return null;
        }

        $data = json_decode($body, true);
        if (empty($data['itemId'])) {
            return null;
        }

        // currentBidPrice is only present once an auction has at least one bid;
        // before that, price reflects the starting price.
        $currentPrice = $data['currentBidPrice']['value'] ?? $data['price']['value'] ?? null;
        $shippingCost = $data['shippingOptions'][0]['shippingCost']['value'] ?? null;

        return [
            'title' => $data['title'] ?? null,
            'end_time' => $data['itemEndDate'] ?? null,
            'current_price' => $currentPrice !== null ? (float) $currentPrice : null,
            'shipping_cost' => $shippingCost !== null ? (float) $shippingCost : null,
            'item_country' => $data['itemLocation']['country'] ?? null,
            'image_url' => $data['image']['imageUrl'] ?? null,
        ];
    }

    private function tradingHeaders(string $callName): array
    {
        return [
            'X-EBAY-API-COMPATIBILITY-LEVEL: 1193',
            'X-EBAY-API-SITEID: ' . $this->cfg['site_id'],
            'X-EBAY-API-CALL-NAME: ' . $callName,
            'X-EBAY-API-APP-NAME: ' . $this->cfg['app_id'],
            'X-EBAY-API-DEV-NAME: ' . $this->cfg['dev_id'],
            'X-EBAY-API-CERT-NAME: ' . $this->cfg['cert_id'],
            'Content-Type: text/xml',
        ];
    }

    private function tradingErrorMessage(?SimpleXMLElement $xml, string $rawResponse): string
    {
        if (!$xml) {
            return 'Unparseable response from eBay.';
        }
        $messages = [];
        foreach ($xml->Errors as $err) {
            $messages[] = (string) $err->LongMessage;
        }
        return $messages ? implode('; ', $messages) : $rawResponse;
    }

    /**
     * Step 1 of connecting an eBay account: get a temporary SessionID that identifies
     * this authorization attempt, then send the user to signInUrl() to log in and grant access.
     */
    public function getSessionId(): string
    {
        $ruName = $this->cfg['ru_name'];
        $body = '<?xml version="1.0" encoding="utf-8"?>'
            . '<GetSessionIDRequest xmlns="urn:ebay:apis:eBLBaseComponents">'
            . '<RuName>' . htmlspecialchars($ruName) . '</RuName>'
            . '</GetSessionIDRequest>';

        [, $response] = $this->httpPost($this->tradingEndpoint(), $this->tradingHeaders('GetSessionID'), $body);
        $xml = simplexml_load_string($response);

        if (!$xml || (string) $xml->Ack === 'Failure') {
            throw new RuntimeException($this->tradingErrorMessage($xml ?: null, $response));
        }

        return (string) $xml->SessionID;
    }

    public function signInUrl(string $sessionId): string
    {
        return $this->signInEndpoint() . '?SignIn'
            . '&RuName=' . urlencode($this->cfg['ru_name'])
            . '&SessID=' . urlencode($sessionId);
    }

    /**
     * Step 2, after the user comes back from signing in: exchange the SessionID
     * for the actual long-lived eBay Auth Token used to place bids on their behalf.
     */
    public function fetchToken(string $sessionId): array
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>'
            . '<FetchTokenRequest xmlns="urn:ebay:apis:eBLBaseComponents">'
            . '<SessionID>' . htmlspecialchars($sessionId) . '</SessionID>'
            . '</FetchTokenRequest>';

        [, $response] = $this->httpPost($this->tradingEndpoint(), $this->tradingHeaders('FetchToken'), $body);
        $xml = simplexml_load_string($response);

        if (!$xml || (string) $xml->Ack === 'Failure') {
            throw new RuntimeException($this->tradingErrorMessage($xml ?: null, $response));
        }

        return [
            'token' => (string) $xml->eBayAuthToken,
            'expires_at' => (string) $xml->HardExpirationTime,
        ];
    }

    /**
     * Places (or raises) a proxy bid. eBay will auto-rebid on the user's behalf up to
     * maxBid each time they're outbid, exactly like bidding manually on the site.
     */
    public function placeBid(string $authToken, string $itemId, float $maxBid): array
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>'
            . '<PlaceOfferRequest xmlns="urn:ebay:apis:eBLBaseComponents">'
            . '<RequesterCredentials><eBayAuthToken>' . htmlspecialchars($authToken) . '</eBayAuthToken></RequesterCredentials>'
            . '<ItemID>' . htmlspecialchars($itemId) . '</ItemID>'
            . '<Offer>'
            . '<Action>Bid</Action>'
            . '<MaxBid currencyID="' . htmlspecialchars($this->cfg['currency']) . '">' . htmlspecialchars((string) $maxBid) . '</MaxBid>'
            . '<Quantity>1</Quantity>'
            . '</Offer>'
            . '</PlaceOfferRequest>';

        [, $response] = $this->httpPost($this->tradingEndpoint(), $this->tradingHeaders('PlaceOffer'), $body);
        $xml = simplexml_load_string($response);

        if (!$xml) {
            return ['success' => false, 'message' => $this->tradingErrorMessage(null, $response)];
        }

        $ack = (string) $xml->Ack;
        if ($ack === 'Failure') {
            return ['success' => false, 'message' => $this->tradingErrorMessage($xml, $response)];
        }

        return ['success' => true, 'message' => "Ack: $ack, HighBidder: " . (string) $xml->Offer->HighBidder->UserID];
    }

    /**
     * The eBay username behind an Auth'n'Auth token, fetched right after connecting
     * so a stored token can be matched against eBay's account-deletion notifications
     * later — those identify the account by username/userId, not by our own token.
     * Best-effort: returns null on any failure rather than throwing, since losing
     * this shouldn't block the user from finishing "Connect eBay account".
     */
    public function getUsername(string $authToken): ?string
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>'
            . '<GetUserRequest xmlns="urn:ebay:apis:eBLBaseComponents">'
            . '<RequesterCredentials><eBayAuthToken>' . htmlspecialchars($authToken) . '</eBayAuthToken></RequesterCredentials>'
            . '</GetUserRequest>';

        try {
            [, $response] = $this->httpPost($this->tradingEndpoint(), $this->tradingHeaders('GetUser'), $body);
            $xml = simplexml_load_string($response);
        } catch (Throwable $e) {
            return null;
        }

        if (!$xml || (string) $xml->Ack === 'Failure' || empty($xml->User->UserID)) {
            return null;
        }

        return (string) $xml->User->UserID;
    }

    /**
     * The items the user is actually watching on eBay itself (Site Preferences ->
     * Watch List), as opposed to this app's own auction list. Uses the same
     * Auth'n'Auth token already stored for bidding — no separate OAuth needed.
     */
    private function watchListRequestBody(string $authToken): string
    {
        return '<?xml version="1.0" encoding="utf-8"?>'
            . '<GetMyeBayBuyingRequest xmlns="urn:ebay:apis:eBLBaseComponents">'
            . '<RequesterCredentials><eBayAuthToken>' . htmlspecialchars($authToken) . '</eBayAuthToken></RequesterCredentials>'
            . '<WatchList><Include>true</Include><Pagination><EntriesPerPage>200</EntriesPerPage></Pagination></WatchList>'
            . '<DetailLevel>ReturnSummary</DetailLevel>'
            . '</GetMyeBayBuyingRequest>';
    }

    /** TEMPORARY debugging helper: returns eBay's raw GetMyeBayBuying XML response, unparsed. */
    public function getWatchListRaw(string $authToken): string
    {
        [, $response] = $this->httpPost($this->tradingEndpoint(), $this->tradingHeaders('GetMyeBayBuying'), $this->watchListRequestBody($authToken));

        return $response;
    }

    public function getWatchList(string $authToken): array
    {
        [, $response] = $this->httpPost($this->tradingEndpoint(), $this->tradingHeaders('GetMyeBayBuying'), $this->watchListRequestBody($authToken));
        $xml = simplexml_load_string($response);

        if (!$xml || (string) $xml->Ack === 'Failure') {
            throw new RuntimeException($this->tradingErrorMessage($xml ?: null, $response));
        }

        $items = [];
        if (isset($xml->WatchList->ItemArray->Item)) {
            foreach ($xml->WatchList->ItemArray->Item as $item) {
                $items[] = [
                    'item_id' => (string) $item->ItemID,
                    'title' => (string) $item->Title,
                    'end_time' => (string) $item->ListingDetails->EndTime,
                    'view_url' => (string) $item->ListingDetails->ViewItemURL,
                    'gallery_url' => (string) $item->PictureDetails->GalleryURL,
                    'current_price' => isset($item->SellingStatus->CurrentPrice) ? (float) $item->SellingStatus->CurrentPrice : null,
                    'currency' => (string) ($item->SellingStatus->CurrentPrice['currencyID'] ?? ''),
                    'bid_count' => isset($item->SellingStatus->BidCount) ? (int) $item->SellingStatus->BidCount : null,
                    // "Chinese" is eBay's historical internal name for the online-auction listing
                    // format (as opposed to "FixedPriceItem"/"StoresFixedPrice" etc.) — unrelated to China.
                    'listing_type' => (string) $item->ListingType,
                ];
            }
        }

        return $items;
    }
}
