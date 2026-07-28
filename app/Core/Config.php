<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Static access to the loaded configuration, plus URL construction that copes
 * with the app living in a sub-directory (a common cPanel arrangement).
 */
final class Config
{
    /** @var array<string,mixed> */
    private static array $items = [];

    private static ?string $basePath = null;

    /** @param array<string,mixed> $config */
    public static function load(array $config): void
    {
        self::$items = $config;
        self::$basePath = null;
    }

    /** Dot-notation getter: Config::get('db.host'). */
    public static function get(string $key, $default = null)
    {
        $value = self::$items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public static function set(string $key, $value): void
    {
        self::$items[$key] = $value;
    }

    /**
     * Directory the app is served from, without a trailing slash.
     * '' at a domain root, '/excelbids' in a sub-folder.
     */
    public static function basePath(): string
    {
        if (self::$basePath !== null) {
            return self::$basePath;
        }

        $configured = trim((string) self::get('base_path', ''));
        if ($configured !== '') {
            return self::$basePath = '/' . trim($configured, '/');
        }

        // Derive from the front controller's location.
        //
        // Only trust SCRIPT_NAME when it actually points at a front controller.
        // Under mod_rewrite it is "/index.php" or "/sub/dir/index.php"; under
        // PHP's built-in server with a router script it is the requested path,
        // which must not be mistaken for an install directory.
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

        if (!str_ends_with($script, '/index.php')) {
            return self::$basePath = '';
        }

        $dir = substr($script, 0, -strlen('/index.php'));

        // The installer is served from /install/index.php but shares the app root.
        if (str_ends_with($dir, '/install')) {
            $dir = substr($dir, 0, -strlen('/install'));
        }

        return self::$basePath = rtrim($dir, '/');
    }

    /** Scheme + host, derived from the request unless base_url is configured. */
    public static function origin(): string
    {
        $configured = rtrim((string) self::get('base_url', ''), '/');
        if ($configured !== '') {
            $parts = parse_url($configured);
            if (!empty($parts['host'])) {
                $scheme = $parts['scheme'] ?? 'https';
                $port = isset($parts['port']) ? ':' . $parts['port'] : '';
                return $scheme . '://' . $parts['host'] . $port;
            }
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return ($https ? 'https' : 'http') . '://' . $host;
    }

    /** Absolute URL for an app path. */
    public static function url(string $path = '/'): string
    {
        $configured = rtrim((string) self::get('base_url', ''), '/');
        if ($configured !== '') {
            return $configured . '/' . ltrim($path, '/');
        }
        return self::origin() . self::basePath() . '/' . ltrim($path, '/');
    }

    public static function isDebug(): bool
    {
        return (bool) self::get('debug', false);
    }

    public static function storagePath(string $append = ''): string
    {
        $base = rtrim((string) self::get('storage_path', EB_ROOT . '/storage'), '/');
        return $append === '' ? $base : $base . '/' . ltrim($append, '/');
    }
}
