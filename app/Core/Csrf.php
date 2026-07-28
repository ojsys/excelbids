<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Per-session CSRF token. Every state-changing form posts `_token`, and the
 * Middleware::csrf check rejects anything that does not match.
 */
final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION[self::KEY];
    }

    public static function check(?string $token): bool
    {
        if ($token === null || $token === '' || empty($_SESSION[self::KEY])) {
            return false;
        }
        return hash_equals((string) $_SESSION[self::KEY], $token);
    }

    /** Issue a fresh token — called after login to prevent fixation. */
    public static function rotate(): void
    {
        $_SESSION[self::KEY] = bin2hex(random_bytes(32));
    }
}
