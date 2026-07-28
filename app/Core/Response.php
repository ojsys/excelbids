<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Response helpers. Every one of these terminates the request.
 */
final class Response
{
    public static function redirect(string $path, int $status = 302): void
    {
        $location = str_starts_with($path, 'http') ? $path : Config::url($path);
        if (!headers_sent()) {
            header('Location: ' . $location, true, $status);
        }
        exit;
    }

    /** Redirect back to the referring page, falling back to a known path. */
    public static function back(string $fallback = '/'): void
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        // Only follow same-origin referers.
        if ($referer !== '' && str_starts_with($referer, Config::origin())) {
            self::redirect($referer);
        }
        self::redirect($fallback);
    }

    public static function json($data, int $status = 200): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Stream a CSV download from an array of rows. */
    public static function csv(string $filename, array $headers, array $rows): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
            header('Pragma: no-cache');
        }
        $out = fopen('php://output', 'wb');
        // BOM so Excel opens UTF-8 correctly — this system is used with Excel a lot.
        fwrite($out, "\xEF\xBB\xBF");
        if ($headers) {
            fputcsv($out, $headers);
        }
        foreach ($rows as $row) {
            fputcsv($out, array_map(static fn ($v) => is_scalar($v) || $v === null ? (string) $v : json_encode($v), (array) $row));
        }
        fclose($out);
        exit;
    }

    /** Send a stored file as a download, with a streamed read for large files. */
    public static function download(string $absolutePath, string $downloadName, string $mime = 'application/octet-stream'): void
    {
        if (!is_file($absolutePath)) {
            self::error(404, 'File not found');
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadName) . '"');
        header('Content-Length: ' . filesize($absolutePath));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, must-revalidate');

        readfile($absolutePath);
        exit;
    }

    public static function error(int $status, string $message = ''): void
    {
        if (!headers_sent()) {
            http_response_code($status);
        }

        $view = EB_APP . "/Views/errors/{$status}.php";
        if (!is_file($view)) {
            $view = EB_APP . '/Views/errors/500.php';
        }

        if (is_file($view)) {
            $errorMessage = $message;
            $errorStatus = $status;
            include $view;
        } else {
            echo eb_e($message !== '' ? $message : 'Error ' . $status);
        }
        exit;
    }

    /** Baseline security headers for HTML responses. */
    public static function securityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
}
