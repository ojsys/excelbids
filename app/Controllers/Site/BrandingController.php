<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Branding;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

/**
 * Serves the logo, favicon and share image.
 *
 * These sit outside the web root so the app works in either cPanel layout, so
 * they are streamed from here instead. Stored names are random and only change
 * on re-upload, which makes them safe to cache hard.
 */
final class BrandingController extends Controller
{
    private const MIME_TYPES = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
    ];

    public function show(Request $request, array $params): void
    {
        $path = Branding::resolveStoredFile((string) ($params['file'] ?? ''));

        if ($path === null) {
            Response::error(404, 'That image could not be found.');
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $mime = self::MIME_TYPES[$extension] ?? 'application/octet-stream';
        $etag = '"' . md5($path . '|' . filemtime($path) . '|' . filesize($path)) . '"';

        // Honour the browser's cache before doing any work.
        if (trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
            http_response_code(304);
            header('ETag: ' . $etag);
            header('Cache-Control: public, max-age=31536000, immutable');
            exit;
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('ETag: ' . $etag);
        header('Cache-Control: public, max-age=31536000, immutable');
        header('X-Content-Type-Options: nosniff');

        // An SVG is a document. It is sanitised on upload; this stops anything
        // that survived from executing if the file is opened directly.
        if ($extension === 'svg') {
            header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
        }

        readfile($path);
        exit;
    }
}
