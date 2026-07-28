<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Dependency-free mailer with two transports:
 *
 *   mail — PHP's mail(), which works out of the box on almost every cPanel host
 *   smtp — a minimal authenticated SMTP client for when deliverability matters
 *
 * Every send is written to email_log so failures are visible in the admin panel
 * rather than disappearing silently.
 */
final class Mailer
{
    private string $to = '';
    private string $toName = '';
    private string $subject = '';
    private string $htmlBody = '';
    private string $replyTo = '';
    private string $replyToName = '';

    public static function to(string $email, string $name = ''): self
    {
        $mailer = new self();
        $mailer->to = $email;
        $mailer->toName = $name;
        return $mailer;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function replyTo(string $email, string $name = ''): self
    {
        $this->replyTo = $email;
        $this->replyToName = $name;
        return $this;
    }

    /** Render an email view from app/Views/emails. */
    public function view(string $template, array $data = []): self
    {
        $this->htmlBody = View::capture('emails/' . $template, $data, 'emails/layout');
        return $this;
    }

    public function html(string $html): self
    {
        $this->htmlBody = $html;
        return $this;
    }

    public function send(): bool
    {
        if (!filter_var($this->to, FILTER_VALIDATE_EMAIL)) {
            $this->log('failed', 'mail', 'Invalid recipient address');
            return false;
        }

        $transport = Settings::get('mail_transport', 'mail') === 'smtp' ? 'smtp' : 'mail';

        try {
            $sent = $transport === 'smtp' ? $this->sendSmtp() : $this->sendMail();
            $this->log($sent ? 'sent' : 'failed', $transport, $sent ? '' : 'Transport returned failure');
            return $sent;
        } catch (\Throwable $e) {
            error_log('[mailer] ' . $e->getMessage());
            $this->log('failed', $transport, substr($e->getMessage(), 0, 500));
            return false;
        }
    }

    // -- Transports ---------------------------------------------------------

    private function sendMail(): bool
    {
        $headers = [];
        foreach ($this->headerLines() as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }

        // -f sets the envelope sender, which keeps cPanel from rewriting it.
        $fromEmail = $this->fromEmail();
        $params = filter_var($fromEmail, FILTER_VALIDATE_EMAIL) ? '-f' . $fromEmail : '';

        return mail(
            $this->formatAddress($this->to, $this->toName),
            $this->encodeHeader($this->subject),
            $this->bodyForTransport(),
            implode("\r\n", $headers),
            $params
        );
    }

    private function sendSmtp(): bool
    {
        $host = (string) Settings::get('smtp_host', '');
        $port = Settings::int('smtp_port', 587);
        $secure = strtolower((string) Settings::get('smtp_secure', 'tls'));
        $username = (string) Settings::get('smtp_username', '');
        $password = (string) Settings::get('smtp_password', '');

        if ($host === '') {
            throw new \RuntimeException('SMTP transport selected but no host is configured.');
        }

        $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $context = stream_context_create([
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false],
        ]);

        $socket = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            throw new \RuntimeException("SMTP connection to {$remote} failed: {$errstr} ({$errno})");
        }
        stream_set_timeout($socket, 20);

        try {
            $this->expect($socket, 220);

            $ehloHost = parse_url(Config::origin(), PHP_URL_HOST) ?: 'localhost';
            $this->command($socket, 'EHLO ' . $ehloHost, 250);

            if ($secure === 'tls') {
                $this->command($socket, 'STARTTLS', 220);
                $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                if (!stream_socket_enable_crypto($socket, true, $crypto)) {
                    throw new \RuntimeException('Failed to start TLS with the SMTP server.');
                }
                $this->command($socket, 'EHLO ' . $ehloHost, 250);
            }

            if ($username !== '') {
                $this->command($socket, 'AUTH LOGIN', 334);
                $this->command($socket, base64_encode($username), 334);
                $this->command($socket, base64_encode($password), 235);
            }

            $this->command($socket, 'MAIL FROM:<' . $this->fromEmail() . '>', 250);
            $this->command($socket, 'RCPT TO:<' . $this->to . '>', 250);
            $this->command($socket, 'DATA', 354);

            $message = '';
            foreach ($this->headerLines(true) as $name => $value) {
                $message .= $name . ': ' . $value . "\r\n";
            }
            $message .= 'To: ' . $this->formatAddress($this->to, $this->toName) . "\r\n";
            $message .= 'Subject: ' . $this->encodeHeader($this->subject) . "\r\n";
            $message .= "\r\n";
            // Dot-stuffing, per RFC 5321.
            $message .= preg_replace('/^\./m', '..', $this->bodyForTransport());
            $message .= "\r\n.";

            $this->command($socket, $message, 250);
            $this->command($socket, 'QUIT', 221, false);

            return true;
        } finally {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }
    }

    /** @param resource $socket */
    private function command($socket, string $command, int $expected, bool $throw = true): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->expect($socket, $expected, $throw);
    }

    /** @param resource $socket */
    private function expect($socket, int $expected, bool $throw = true): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            // Multi-line replies use "250-"; the final line uses "250 ".
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if ($code !== $expected && $throw) {
            throw new \RuntimeException("SMTP expected {$expected}, got: " . trim($response));
        }
        return $response;
    }

    // -- Message construction ----------------------------------------------

    /** @return array<string,string> */
    private function headerLines(bool $forSmtp = false): array
    {
        $headers = [
            'From'         => $this->formatAddress($this->fromEmail(), (string) Settings::get('mail_from_name', 'ExcelBids')),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
            'X-Mailer'     => 'ExcelBids',
        ];

        if ($this->replyTo !== '' && filter_var($this->replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers['Reply-To'] = $this->formatAddress($this->replyTo, $this->replyToName);
        }

        if ($forSmtp) {
            $headers['Date'] = date('r');
            $headers['Message-ID'] = '<' . bin2hex(random_bytes(12)) . '@' . (parse_url(Config::origin(), PHP_URL_HOST) ?: 'localhost') . '>';
        }

        return $headers;
    }

    private function bodyForTransport(): string
    {
        // Normalise to CRLF and wrap so no line exceeds the SMTP 998-octet limit.
        $body = preg_replace("/\r\n|\r|\n/", "\r\n", $this->htmlBody) ?? $this->htmlBody;
        return wordwrap($body, 900, "\r\n", true);
    }

    private function fromEmail(): string
    {
        $from = (string) Settings::get('mail_from_email', '');
        if ($from === '') {
            $from = (string) Settings::get('contact_email', 'no-reply@localhost');
        }
        return $from;
    }

    private function formatAddress(string $email, string $name): string
    {
        if ($name === '') {
            return $email;
        }
        return $this->encodeHeader($name) . ' <' . $email . '>';
    }

    /** RFC 2047 encode a header value when it contains non-ASCII characters. */
    private function encodeHeader(string $value): string
    {
        $value = str_replace(["\r", "\n"], '', $value);
        if (preg_match('/[\x80-\xFF]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }

    private function log(string $status, string $transport, string $error): void
    {
        try {
            Database::insert('email_log', [
                'to_email'   => substr($this->to, 0, 190),
                'subject'    => substr($this->subject, 0, 255),
                'body'       => $this->htmlBody,
                'status'     => $status,
                'transport'  => $transport,
                'error'      => substr($error, 0, 500),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            error_log('[mailer] could not write email_log: ' . $e->getMessage());
        }
    }
}
