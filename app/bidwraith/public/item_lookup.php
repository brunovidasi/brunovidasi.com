<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();

header('Content-Type: application/json');

if (!user_has_access($user)) {
    http_response_code(402);
    echo json_encode(['found' => false, 'error' => 'Start a plan to look up auctions.']);
    exit;
}

$itemId = extract_ebay_item_id(get_param('item_id'));

if ($itemId === '' || !ctype_digit($itemId)) {
    echo json_encode(['found' => false, 'error' => 'Enter a valid eBay item ID or listing URL.']);
    exit;
}

try {
    $client = new EbayClient();
    $lookup = $client->getItemByLegacyId($itemId);
} catch (Throwable $e) {
    $lookup = null;
}

if (!$lookup) {
    echo json_encode([
        'found' => false,
        'item_id' => $itemId,
        'error' => "Couldn't find that item (this is expected in the eBay Sandbox for real item IDs).",
    ]);
    exit;
}

echo json_encode([
    'found' => true,
    'item_id' => $itemId,
    'title' => $lookup['title'],
    'current_price' => $lookup['current_price'],
    'currency' => user_currency($user),
    'end_time' => $lookup['end_time'],
    'image_url' => $lookup['image_url'],
    'shipping_cost' => $lookup['shipping_cost'],
    'item_country' => $lookup['item_country'],
]);
