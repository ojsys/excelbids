<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Route middleware. Each entry either passes silently or terminates the request.
 */
final class Middleware
{
    public static function run(string $name, Request $request): void
    {
        // "can:bids.manage" style parameters.
        $parameter = null;
        if (str_contains($name, ':')) {
            [$name, $parameter] = explode(':', $name, 2);
        }

        switch ($name) {
            case 'csrf':
                self::csrf($request);
                return;

            case 'auth.staff':
                self::authStaff($request);
                return;

            case 'auth.client':
                self::authClient($request);
                return;

            case 'guest.staff':
                if (Auth::check(Auth::STAFF)) {
                    Response::redirect('/admin');
                }
                return;

            case 'guest.client':
                if (Auth::check(Auth::CLIENT)) {
                    Response::redirect('/portal');
                }
                return;

            case 'can':
                Auth::authorize((string) $parameter);
                return;

            case 'portal.enabled':
                if (Settings::bool('portal_enabled', true) === false) {
                    Response::error(404, 'The client portal is not currently available.');
                }
                return;

            default:
                throw new RuntimeException("Unknown middleware: {$name}");
        }
    }

    /** Reject unsafe requests without a valid token. */
    private static function csrf(Request $request): void
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $token = $request->raw('_token');
        if (!is_string($token) || !Csrf::check($token)) {
            if ($request->isAjax()) {
                Response::json(['error' => 'Your session expired. Please reload the page.'], 419);
            }
            Flash::error('Your session expired before that form was submitted. Please try again.');
            Response::back('/');
        }
    }

    private static function authStaff(Request $request): void
    {
        if (Auth::check(Auth::STAFF)) {
            return;
        }

        if ($request->isAjax()) {
            Response::json(['error' => 'Not authenticated'], 401);
        }

        if (!empty($_SESSION['_expired'])) {
            unset($_SESSION['_expired']);
            Flash::warning('You were signed out after a period of inactivity.');
        }

        // Remember where they were headed so login can return them there.
        $_SESSION['_intended_staff'] = $request->uri();
        Response::redirect('/admin/login');
    }

    private static function authClient(Request $request): void
    {
        if (Auth::check(Auth::CLIENT)) {
            return;
        }

        if ($request->isAjax()) {
            Response::json(['error' => 'Not authenticated'], 401);
        }

        if (!empty($_SESSION['_expired'])) {
            unset($_SESSION['_expired']);
            Flash::warning('You were signed out after a period of inactivity.');
        }

        $_SESSION['_intended_client'] = $request->uri();
        Response::redirect('/portal/login');
    }

    /** Consume and clear a stored "intended" destination. */
    public static function intended(string $guard, string $fallback): string
    {
        $key = $guard === Auth::STAFF ? '_intended_staff' : '_intended_client';
        $target = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        if (!is_string($target) || $target === '' || !str_starts_with($target, '/')) {
            return $fallback;
        }
        // Never bounce back to a login or logout route.
        if (str_contains($target, '/login') || str_contains($target, '/logout')) {
            return $fallback;
        }
        return $target;
    }
}
