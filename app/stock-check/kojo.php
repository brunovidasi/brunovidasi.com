<?php
$key   = 'r5Nw8Jc2Xh7Kq4Ld9Vt3Pz6M';
$topic = 'kojo-shorts-bruno-4m9tq7vw';
$page  = 'https://www.kojofit.com/products/ultra-stretch-3-chino-shorts-black';
$size  = '30';     // e.g. '32' to watch a single size, '' for any size
$price = 8900;   // current full price in cents (A$89.00); notify when it goes below this
$state = sys_get_temp_dir() . '/kojo-last-notified-price.txt';

if (($_GET['key'] ?? '') !== $key) { http_response_code(403); exit('Forbidden'); }

function notify($topic, $page, $message) {
    file_get_contents("https://ntfy.sh/$topic", false, stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Title: Kojo chino shorts\r\nPriority: high\r\nTags: moneybag\r\nClick: $page",
            'content' => $message,
        ],
    ]));
}

if (isset($_GET['test'])) { notify($topic, $page, 'Test: notifications are working'); exit('Test sent'); }

$ch = curl_init($page . '.js');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
]);
$body   = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error  = curl_error($ch);

$data = json_decode($body ?: '', true);

if ($data === null || empty($data['variants'])) {
    exit("Could not read product data. HTTP $status. $error " . htmlspecialchars(substr((string) $body, 0, 200)));
}

$variants = array_filter($data['variants'], fn($v) => $size === '' || $v['title'] === $size);
if (!$variants) { exit("Size $size not found"); }

$lowest = min(array_column($variants, 'price'));
$money  = fn($cents) => 'A$' . number_format($cents / 100, 2);

if ($lowest >= $price) {
    @unlink($state);
    exit('No drop. Price ' . $money($lowest));
}

// Only notify once per new price, not on every cron run while the sale lasts
if (@file_get_contents($state) !== (string) $lowest) {
    notify($topic, $page, 'Price dropped to ' . $money($lowest) . ' (was ' . $money($price) . ')' . ($size !== '' ? " in size $size" : ''));
    file_put_contents($state, (string) $lowest);
}

exit('PRICE DROP ' . $money($lowest));
