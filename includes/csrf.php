<?php

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';

    // hash_equals() calls two empty strings equal, and that is what a forged
    // request without a session looks like.
    if ($expected === '' || !is_string($token) || !hash_equals($expected, $token)) {
        http_response_code(403);
        die('Invalid or expired form submission. Go back and try again.');
    }
}
