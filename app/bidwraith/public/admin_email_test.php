<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$admin = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Sent straight away, not queued, so a wrong password or blocked port shows up here
    // as the mail server's own words instead of a silent retry.
    [, $note] = mail_status();
    try {
        $tpl = email_tpl_test($note);
        deliver_email(['to_email' => $admin['email'], 'subject' => $tpl['subject'], 'body_text' => $tpl['text'], 'body_html' => $tpl['html']]);
        set_flash('success', mail_transport() === 'log'
            ? 'Written to mail.log — transport is "log", so nothing was actually sent.'
            : 'Test email sent to ' . $admin['email'] . '. Check the inbox (and spam).');
    } catch (Throwable $e) {
        set_flash('error', 'Sending failed: ' . $e->getMessage());
    }
}

redirect('admin#email');
