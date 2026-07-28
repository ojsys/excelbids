<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Read-only view of the current HTTP request.
 */
final class Request
{
    private string $method;
    private string $uri;
    /** @var array<string,mixed> */
    private array $query;
    /** @var array<string,mixed> */
    private array $body;

    public function __construct()
    {
        $this->method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        // Support the _method override used by forms that need PUT/DELETE semantics.
        if ($this->method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $this->method = $override;
            }
        }

        $this->query = $_GET;
        $this->body = $_POST;
        $this->uri = $this->resolveUri();
    }

    private function resolveUri(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $uri = explode('?', $uri, 2)[0];
        $uri = rawurldecode($uri);

        // Strip the install sub-directory so routes are always app-relative.
        $base = Config::basePath();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        $uri = '/' . trim($uri, '/');
        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    /** Query string value. */
    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    /** POST body value, trimmed when it is a string. */
    public function input(string $key, $default = null)
    {
        $value = $this->body[$key] ?? $this->query[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    /** POST body value with no trimming (for textareas and passwords). */
    public function raw(string $key, $default = null)
    {
        return $this->body[$key] ?? $default;
    }

    /** Checkbox helper — present and truthy. */
    public function boolean(string $key): bool
    {
        $value = $this->body[$key] ?? $this->query[$key] ?? null;
        return in_array($value, ['1', 1, true, 'true', 'on', 'yes'], true);
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key, null);
        return $value === null || $value === '' ? $default : (int) $value;
    }

    /** Nullable string: empty input becomes null, for nullable DB columns. */
    public function nullable(string $key): ?string
    {
        $value = $this->input($key, null);
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    /** @return array<string,mixed> */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    /** @return array<int,string> */
    public function arrayInput(string $key): array
    {
        $value = $this->body[$key] ?? $this->query[$key] ?? [];
        return is_array($value) ? $value : [];
    }

    public function isAjax(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    public function userAgent(): string
    {
        return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public function referer(): string
    {
        return (string) ($_SERVER['HTTP_REFERER'] ?? '');
    }
}
