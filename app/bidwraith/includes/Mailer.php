<?php
/**
 * Everything the app emails goes through here.
 *
 * Two halves, deliberately separate:
 *
 *  1. queue_email() records a message in the email_outbox table. It is a single local
 *     insert, so it is safe to call anywhere — including inside the bid-firing pass,
 *     where a slow mail server must never delay a bid by even a second.
 *  2. flush_outbox() actually delivers what is queued, retrying failures with a
 *     back-off. Web requests flush after the page has been sent to the browser (so a
 *     slow mail server never slows a page); the cron pass flushes when it is idle and
 *     again when it finishes.
 *
 * Transports (config 'mail' => 'transport'):
 *   smtp  a real mail server or provider (Postmark, Resend, SES, Brevo, your host's own
 *         SMTP…). What production should use: mail sent from a domain that has SPF/DKIM
 *         set up for the provider reaches inboxes; PHP's mail() from shared hosting
 *         often lands in spam.
 *   mail  PHP's mail(), i.e. the host's local sendmail. Works with no setup.
 *   log   writes each message to data/mail.log instead of sending. The default, so a
 *         fresh checkout can't email anyone by accident, and development can read its
 *         verification links.
 */

function mail_config(): array
{
    return app_config()['mail'] ?? [];
}

function mail_transport(): string
{
    $transport = mail_config()['transport'] ?? 'log';
    return in_array($transport, ['smtp', 'mail', 'log'], true) ? $transport : 'log';
}

function mail_host(): string
{
    return parse_url(base_url(), PHP_URL_HOST) ?: 'localhost';
}

/** @return array{0: string, 1: string} sender address and display name */
function mail_from(): array
{
    $config = mail_config();
    $email = trim((string) ($config['from_email'] ?? ''));

    return [
        $email !== '' ? $email : 'noreply@' . mail_host(),
        trim((string) ($config['from_name'] ?? '')) ?: 'Bidwraith',
    ];
}

/** Whether the configured transport has what it needs, and a sentence saying so. */
function mail_status(): array
{
    $transport = mail_transport();

    if ($transport === 'log') {
        return [false, 'transport is "log": messages are written to mail.log, not sent'];
    }
    if ($transport === 'smtp') {
        $smtp = mail_config()['smtp'] ?? [];
        if (empty($smtp['host'])) {
            return [false, 'transport is "smtp" but mail.smtp.host is blank'];
        }
        return [true, 'smtp via ' . $smtp['host'] . ':' . (int) ($smtp['port'] ?? 587)];
    }

    return [true, 'PHP mail() — check deliverability; SMTP is recommended for production'];
}

// --------------------------------------------------------------------- building

/** A header value can never contain a line break: that is how header injection works. */
function mail_clean(string $value): string
{
    return trim(str_replace(["\r", "\n", "\0"], ' ', $value));
}

/** RFC 2047 encoded-word for anything that isn't plain ASCII. */
function mail_encode_header(string $value): string
{
    $value = mail_clean($value);
    if (!preg_match('/[^\x20-\x7e]/', $value)) {
        return $value;
    }

    return function_exists('mb_encode_mimeheader')
        ? mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n")
        : '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function mail_address(string $email, string $name = ''): string
{
    $email = mail_clean($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Invalid email address: ' . $email);
    }
    $name = mail_clean($name);
    if ($name === '') {
        return $email;
    }

    $encoded = mail_encode_header($name);
    if ($encoded === $name) {
        $encoded = '"' . addcslashes($name, '"\\') . '"';
    }

    return $encoded . ' <' . $email . '>';
}

function mail_crlf(string $text): string
{
    return preg_replace('/\r\n|\r|\n/', "\r\n", $text);
}

/**
 * @param array{to_email: string, subject: string, body_text: string, body_html: ?string} $message
 * @return array{0: array<string, string>, 1: string} headers (name => value) and body
 */
function build_mime_message(array $message): array
{
    [$fromEmail, $fromName] = mail_from();
    $replyTo = trim((string) (mail_config()['reply_to'] ?? ''));

    $headers = [
        'Date' => date('r'),
        'From' => mail_address($fromEmail, $fromName),
        'To' => mail_address($message['to_email']),
        'Subject' => mail_encode_header($message['subject']),
        'Message-ID' => '<' . bin2hex(random_bytes(16)) . '@' . mail_host() . '>',
        'MIME-Version' => '1.0',
        // Tells auto-responders (out-of-office replies) not to answer.
        'Auto-Submitted' => 'auto-generated',
    ];
    if ($replyTo !== '') {
        $headers['Reply-To'] = mail_address($replyTo);
    }

    $text = quoted_printable_encode(mail_crlf($message['body_text']));

    if (empty($message['body_html'])) {
        $headers['Content-Type'] = 'text/plain; charset=UTF-8';
        $headers['Content-Transfer-Encoding'] = 'quoted-printable';
        return [$headers, $text];
    }

    $boundary = 'bw_' . bin2hex(random_bytes(12));
    $headers['Content-Type'] = 'multipart/alternative; boundary="' . $boundary . '"';

    $body = "--$boundary\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n"
        . $text . "\r\n"
        . "--$boundary\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n"
        . quoted_printable_encode(mail_crlf($message['body_html'])) . "\r\n"
        . "--$boundary--\r\n";

    return [$headers, $body];
}

// ------------------------------------------------------------------ transports

/**
 * Delivers one message now, by whichever transport is configured.
 *
 * @param array{to_email: string, subject: string, body_text: string, body_html: ?string} $message
 * @throws Throwable with a message safe to show an admin
 */
function deliver_email(array $message): void
{
    switch (mail_transport()) {
        case 'smtp':
            [$headers, $body] = build_mime_message($message);
            $raw = '';
            foreach ($headers as $name => $value) {
                $raw .= $name . ': ' . $value . "\r\n";
            }
            (new SmtpSession(mail_config()['smtp'] ?? []))->send(mail_from()[0], $message['to_email'], $raw . "\r\n" . $body);
            return;

        case 'mail':
            [$headers, $body] = build_mime_message($message);
            $to = $headers['To'];
            $subject = $headers['Subject'];
            unset($headers['To'], $headers['Subject']);
            $raw = [];
            foreach ($headers as $name => $value) {
                $raw[] = $name . ': ' . $value;
            }
            // -f sets the envelope sender, which is what SPF is checked against.
            if (!@mail($to, $subject, $body, implode("\r\n", $raw), '-f' . mail_from()[0])) {
                throw new RuntimeException("PHP mail() refused the message (is sendmail set up on this host?).");
            }
            return;

        default:
            write_mail_log($message);
    }
}

function write_mail_log(array $message): void
{
    $dir = data_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    $log = $dir . '/mail.log';
    if (is_file($log) && filesize($log) > 2 * 1024 * 1024) {
        @rename($log, $log . '.1');
    }

    $entry = '=== ' . date('Y-m-d H:i:s') . " ===\n"
        . 'To: ' . $message['to_email'] . "\n"
        . 'Subject: ' . $message['subject'] . "\n\n"
        . $message['body_text'] . "\n\n";

    if (@file_put_contents($log, $entry, FILE_APPEND) === false) {
        throw new RuntimeException('Could not write ' . $log);
    }
}

/**
 * A just-enough SMTP client: connect (implicit TLS or STARTTLS), authenticate, send one
 * message. No Composer, so no PHPMailer; this covers what every mail provider offers.
 */
final class SmtpSession
{
    /** @var resource */
    private $socket;
    private string $capabilities = '';

    /** @throws RuntimeException */
    public function __construct(private array $config)
    {
    }

    public function send(string $from, string $to, string $rawMessage): void
    {
        $this->connect();
        try {
            $this->command('MAIL FROM:<' . mail_clean($from) . '>', [250]);
            $this->command('RCPT TO:<' . mail_clean($to) . '>', [250, 251]);
            $this->command('DATA', [354]);

            // Dot-stuffing: a line that starts with "." would otherwise end the message.
            $data = preg_replace('/^\./m', '..', mail_crlf($rawMessage));
            $this->write($data . "\r\n.");
            $this->expect([250]);
        } finally {
            @fwrite($this->socket, "QUIT\r\n");
            @fclose($this->socket);
        }
    }

    private function connect(): void
    {
        $host = (string) ($this->config['host'] ?? '');
        $port = (int) ($this->config['port'] ?? 587);
        $encryption = (string) ($this->config['encryption'] ?? 'tls');
        $verify = (bool) ($this->config['verify_peer'] ?? true);

        if ($host === '') {
            throw new RuntimeException('mail.smtp.host is not set.');
        }

        $context = stream_context_create(['ssl' => [
            'verify_peer' => $verify,
            'verify_peer_name' => $verify,
            'peer_name' => $host,
        ]]);
        $socket = @stream_socket_client(
            ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port,
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (!$socket) {
            throw new RuntimeException("Could not connect to $host:$port ($errstr).");
        }
        stream_set_timeout($socket, 20);
        $this->socket = $socket;

        $this->expect([220]);
        $this->hello();

        if ($encryption === 'tls') {
            $this->command('STARTTLS', [220]);
            if (!@stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('The server would not upgrade to TLS.');
            }
            $this->hello();
        }

        $username = (string) ($this->config['username'] ?? '');
        if ($username !== '') {
            $this->authenticate($username, (string) ($this->config['password'] ?? ''));
        }
    }

    private function hello(): void
    {
        $this->capabilities = $this->command('EHLO ' . mail_host(), [250]);
    }

    private function authenticate(string $username, string $password): void
    {
        // Credentials are sent without going through command(), so a failed login
        // can't echo them into an error message.
        $mechanisms = strtoupper($this->capabilities);
        if (str_contains($mechanisms, 'AUTH') && !preg_match('/AUTH[ =].*LOGIN/', $mechanisms) && str_contains($mechanisms, 'PLAIN')) {
            $this->write('AUTH PLAIN ' . base64_encode("\0$username\0$password"));
            $this->expect([235], 'authentication failed');
            return;
        }

        $this->write('AUTH LOGIN');
        $this->expect([334]);
        $this->write(base64_encode($username));
        $this->expect([334]);
        $this->write(base64_encode($password));
        $this->expect([235], 'authentication failed');
    }

    private function write(string $line): void
    {
        if (@fwrite($this->socket, $line . "\r\n") === false) {
            throw new RuntimeException('Lost the connection to the mail server.');
        }
    }

    /** @param int[] $codes acceptable reply codes */
    private function command(string $line, array $codes): string
    {
        $this->write($line);
        return $this->expect($codes, 'rejected ' . strtok($line, ' '));
    }

    /** Reads a (possibly multi-line) reply and checks its code. Returns the reply text. */
    private function expect(array $codes, string $context = ''): string
    {
        $reply = '';
        do {
            $line = fgets($this->socket, 1024);
            if ($line === false) {
                throw new RuntimeException('The mail server stopped answering.');
            }
            $reply .= $line;
        } while (isset($line[3]) && $line[3] === '-');

        if (!in_array((int) substr($reply, 0, 3), $codes, true)) {
            throw new RuntimeException('Mail server ' . ($context !== '' ? $context . ': ' : 'said: ') . trim($reply));
        }

        return $reply;
    }
}

// ----------------------------------------------------------------------- outbox

/**
 * Records an email to send. Cheap and safe anywhere.
 *
 * @param array{subject: string, text: string, html: ?string} $template from email_templates.php
 * @param ?string $dedupeKey when given, a second call with the same key does nothing —
 *        that is what makes "tell them once" safe against replayed webhooks and a cron
 *        pass that sees the same condition every minute.
 * @return bool true if it was queued, false if the key had already been used
 */
function queue_email(string $to, array $template, string $kind, ?int $userId = null, ?string $dedupeKey = null): bool
{
    $stmt = db()->prepare('
        INSERT OR IGNORE INTO email_outbox (user_id, kind, to_email, subject, body_text, body_html, dedupe_key)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([$userId, $kind, $to, $template['subject'], $template['text'], $template['html'] ?? null, $dedupeKey]);
    $queued = $stmt->rowCount() > 0;

    if ($queued && PHP_SAPI !== 'cli') {
        flush_outbox_after_response();
    }

    return $queued;
}

/**
 * Delivers whatever is due. Returns how many went out and how many failed this call.
 *
 * A failure backs that message off (5 minutes, then 10, …) and the pass stops there:
 * if the mail server is down there is no point trying the rest, and the backed-off
 * message can't hold up the ones behind it. After six attempts it is left alone, with
 * its error visible in the admin dashboard.
 *
 * @return array{sent: int, failed: int}
 */
function flush_outbox(int $limit = 25): array
{
    $result = ['sent' => 0, 'failed' => 0];
    $now = gmdate('Y-m-d H:i:s');
    $staleClaim = gmdate('Y-m-d H:i:s', time() - 120);

    $stmt = db()->prepare('
        SELECT * FROM email_outbox
        WHERE sent_at IS NULL AND attempts < 6
          AND (next_attempt_at IS NULL OR next_attempt_at <= ?)
          AND (claimed_at IS NULL OR claimed_at < ?)
        ORDER BY id ASC LIMIT ?
    ');
    $stmt->execute([$now, $staleClaim, $limit]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        // Two overlapping passes (they legitimately overlap) must not both send it.
        $claim = db()->prepare('
            UPDATE email_outbox SET claimed_at = ?, attempts = attempts + 1
            WHERE id = ? AND sent_at IS NULL AND (claimed_at IS NULL OR claimed_at < ?)
        ');
        $claim->execute([$now, $row['id'], $staleClaim]);
        if ($claim->rowCount() !== 1) {
            continue;
        }

        try {
            deliver_email($row);
            db()->prepare("UPDATE email_outbox SET sent_at = ?, claimed_at = NULL, last_error = NULL WHERE id = ?")
                ->execute([gmdate('Y-m-d H:i:s'), $row['id']]);
            $result['sent']++;
        } catch (Throwable $e) {
            $attempts = (int) $row['attempts'] + 1;
            db()->prepare('UPDATE email_outbox SET claimed_at = NULL, last_error = ?, next_attempt_at = ? WHERE id = ?')
                ->execute([mb_substr($e->getMessage(), 0, 500), gmdate('Y-m-d H:i:s', time() + $attempts * 300), $row['id']]);
            error_log('Bidwraith mail #' . $row['id'] . ' (' . $row['kind'] . '): ' . $e->getMessage());
            $result['failed']++;
            break;
        }
    }

    return $result;
}

/**
 * Sends after the page has gone to the browser, so a slow mail server costs the visitor
 * nothing. Registered once per request, the first time something is queued.
 */
function flush_outbox_after_response(): void
{
    static $registered = false;
    if ($registered) {
        return;
    }
    $registered = true;

    register_shutdown_function(function (): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }
            @flush();
        }

        try {
            flush_outbox(10);
        } catch (Throwable $e) {
            error_log('Bidwraith mail flush: ' . $e->getMessage());
        }
    });
}
