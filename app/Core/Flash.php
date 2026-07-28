<?php

declare(strict_types=1);

namespace App\Core;

/**
 * One-request session storage: status messages, validation errors and the old
 * input that repopulates a form after a failed submit.
 */
final class Flash
{
    private const MESSAGES = '_flash_messages';
    private const ERRORS = '_flash_errors';
    private const OLD = '_flash_old';

    /** Values read at the start of this request, before we cleared them. */
    private static ?array $readMessages = null;
    private static ?array $readErrors = null;
    private static ?array $readOld = null;

    public static function success(string $message): void
    {
        self::add('success', $message);
    }

    public static function error(string $message): void
    {
        self::add('error', $message);
    }

    public static function info(string $message): void
    {
        self::add('info', $message);
    }

    public static function warning(string $message): void
    {
        self::add('warning', $message);
    }

    private static function add(string $type, string $message): void
    {
        $_SESSION[self::MESSAGES][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Pull all queued messages. Reading clears them, but repeat calls in the
     * same request return the same list so a layout can render them once.
     *
     * @return array<int,array{type:string,message:string}>
     */
    public static function messages(): array
    {
        if (self::$readMessages === null) {
            self::$readMessages = $_SESSION[self::MESSAGES] ?? [];
            unset($_SESSION[self::MESSAGES]);
        }
        return self::$readMessages;
    }

    /** @param array<string,string> $errors */
    public static function setErrors(array $errors): void
    {
        $_SESSION[self::ERRORS] = $errors;
    }

    /** @return array<string,string> */
    public static function errors(): array
    {
        if (self::$readErrors === null) {
            self::$readErrors = $_SESSION[self::ERRORS] ?? [];
            unset($_SESSION[self::ERRORS]);
        }
        return self::$readErrors;
    }

    public static function errorFor(string $field): ?string
    {
        return self::errors()[$field] ?? null;
    }

    public static function hasErrors(): bool
    {
        return self::errors() !== [];
    }

    /** @param array<string,mixed> $input */
    public static function setOld(array $input): void
    {
        unset($input['_token'], $input['password'], $input['password_confirm'], $input['current_password']);
        $_SESSION[self::OLD] = $input;
    }

    public static function old(string $key, $default = '')
    {
        if (self::$readOld === null) {
            self::$readOld = $_SESSION[self::OLD] ?? [];
            unset($_SESSION[self::OLD]);
        }
        return self::$readOld[$key] ?? $default;
    }

    /** Store errors + input and bounce back to the form. */
    public static function failValidation(array $errors, array $input, string $redirectTo): void
    {
        self::setErrors($errors);
        self::setOld($input);
        self::error('Please correct the highlighted fields and try again.');
        Response::redirect($redirectTo);
    }
}
