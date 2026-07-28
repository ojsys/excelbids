<?php
/**
 * Global helpers. Prefixed `eb_` where they are internal, unprefixed for the
 * handful used constantly inside view templates.
 */

declare(strict_types=1);

/** Escape for HTML output. Short name because views call it on every value. */
function eb_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return eb_e($value);
    }
}

/** Build an absolute URL for a path within the app. */
function url(string $path = '/'): string
{
    return App\Core\Config::url($path);
}

/** Build a path-only URL (keeps links relative to the install directory). */
function path(string $path = '/'): string
{
    $base = App\Core\Config::basePath();
    return $base . '/' . ltrim($path, '/');
}

/** URL for a file in public_html/assets, cache-busted by file mtime. */
function asset(string $path): string
{
    $rel = 'assets/' . ltrim($path, '/');
    $file = EB_ROOT . '/public_html/' . $rel;
    $version = is_file($file) ? substr((string) filemtime($file), -6) : '1';
    return path($rel) . '?v=' . $version;
}

/** Read a site setting. */
function setting(string $key, ?string $default = null): ?string
{
    return App\Core\Settings::get($key, $default);
}

/** Read an editable CMS content block. */
function block(string $key, string $default = ''): string
{
    return App\Core\Content::block($key, $default);
}

/** Old form input after a validation failure. */
function old(string $key, $default = '')
{
    return App\Core\Flash::old($key, $default);
}

/** Hidden CSRF field for forms. */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . eb_e(App\Core\Csrf::token()) . '">';
}

/** Format a money value using the configured currency symbol. */
function money($amount, bool $withPence = false): string
{
    $symbol = App\Core\Settings::get('currency_symbol', '£');
    return $symbol . number_format((float) $amount, $withPence ? 2 : 0);
}

/** Format a date for display, tolerating null and zero dates. */
function fdate(?string $date, string $format = 'j M Y'): string
{
    if ($date === null || $date === '' || str_starts_with($date, '0000')) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '—';
}

/** Format a datetime for display. */
function fdatetime(?string $date, string $format = 'j M Y, H:i'): string
{
    return fdate($date, $format);
}

/** "in 3 days" / "2 days ago" style relative label. */
function relative_days(?string $date): string
{
    if (!$date || str_starts_with($date, '0000')) {
        return '';
    }
    $ts = strtotime($date);
    if (!$ts) {
        return '';
    }
    $days = (int) floor(($ts - time()) / 86400);
    if ($days === 0) {
        return 'today';
    }
    if ($days === 1) {
        return 'tomorrow';
    }
    if ($days === -1) {
        return 'yesterday';
    }
    return $days > 0 ? "in {$days} days" : abs($days) . ' days ago';
}

/** Truncate on a word boundary. */
function str_excerpt(?string $text, int $length = 120): string
{
    $text = trim(strip_tags((string) $text));
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    $cut = mb_substr($text, 0, $length);
    $space = mb_strrpos($cut, ' ');
    return rtrim($space ? mb_substr($cut, 0, $space) : $cut, ",.;:") . '…';
}

/** Human-readable file size. */
function filesize_human(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    $units = ['KB', 'MB', 'GB'];
    $i = -1;
    do {
        $bytes /= 1024;
        $i++;
    } while ($bytes >= 1024 && $i < count($units) - 1);
    return round($bytes, $bytes < 10 ? 1 : 0) . ' ' . $units[$i];
}

/** Turn an enum-ish DB value into a readable label. */
function labelize(?string $value): string
{
    return ucwords(str_replace('_', ' ', (string) $value));
}

/** Initials for an avatar chip. */
function initials(?string $name): string
{
    $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
    $parts = array_filter($parts);
    if (!$parts) {
        return '?';
    }
    $first = mb_substr((string) reset($parts), 0, 1);
    $last = count($parts) > 1 ? mb_substr((string) end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
}

/** Current visitor IP, respecting a single trusted proxy hop. */
function client_ip(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', (string) $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '';
}

/** Cryptographically random token for invites and password resets. */
function random_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}
