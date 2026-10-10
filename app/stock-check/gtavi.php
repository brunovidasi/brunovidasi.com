<?php
$key   = 'k7Qm2xV9pLr4Tz8Wn3Yb6Hd1';
$topic = 'gtavi-vinyl-bruno-8f3kq2zx';
$page  = 'https://au.gtavi-thealbum.com/products/grand-theft-auto-vi-the-album-limited-edition-vinyl';

if (($_GET['key'] ?? '') !== $key) { http_response_code(403); exit('Forbidden'); }

function notify($topic, $page, $message) {
    file_get_contents("https://ntfy.sh/$topic", false, stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Title: GTA VI vinyl\r\nPriority: urgent\r\nClick: $page",
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
curl_close($ch);

$data = json_decode($body ?: '', true);

if ($data === null) {
    exit("Could not read product data. HTTP $status. $error " . htmlspecialchars(substr((string) $body, 0, 200)));
}

if (!empty($data['available'])) {
    notify($topic, $page, 'Back in stock. Go now.');
    exit('IN STOCK');
}

exit('Sold out');
