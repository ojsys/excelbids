<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Shared controller behaviour. Subclasses set $layout and call $this->view().
 */
abstract class Controller
{
    protected string $layout = 'site/partials/layout';

    /** @param array<string,mixed> $data */
    protected function view(string $template, array $data = [], ?string $layout = null): void
    {
        Response::securityHeaders();
        View::render($template, $data, $layout ?? $this->layout);
    }

    protected function redirect(string $path): void
    {
        Response::redirect($path);
    }

    protected function back(string $fallback = '/'): void
    {
        Response::back($fallback);
    }

    /**
     * Validate input; on failure, flash the errors and bounce back to $redirectTo.
     *
     * @param array<string,string> $rules
     * @param array<string,string> $labels
     * @return array<string,mixed> The validated input
     */
    protected function validate(Request $request, array $rules, string $redirectTo, array $labels = []): array
    {
        $input = $request->all();
        $validator = Validator::make($input, $rules, $labels);

        if ($validator->fails()) {
            Flash::failValidation($validator->errors(), $input, $redirectTo);
        }

        return $input;
    }

    /** The signed-in staff member, or null. */
    protected function staff(): ?array
    {
        return Auth::user(Auth::STAFF);
    }

    /** The signed-in portal user, or null. */
    protected function client(): ?array
    {
        return Auth::user(Auth::CLIENT);
    }

    protected function notFound(string $message = 'Not found'): void
    {
        Response::error(404, $message);
    }
}
