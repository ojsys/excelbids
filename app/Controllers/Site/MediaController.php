<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Uploader;
use App\Models\Media;

/**
 * Serves media library images.
 *
 * Like brand assets, these live outside the web root so the app works in either
 * cPanel layout. Lookup is by database id, so the route cannot be pointed at an
 * arbitrary file on disk.
 */
final class MediaController extends Controller
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
        $media = Media::find((int) ($params['id'] ?? 0));
        if ($media === null) {
            Response::error(404, 'That image could not be found.');
        }

        $path = Uploader::path((string) $media['stored_name']);
        if (!is_file($path)) {
            Response::error(404, 'That image could not be found.');
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $mime = self::MIME_TYPES[$extension] ?? 'application/octet-stream';
        $etag = '"' . md5($path . '|' . filemtime($path) . '|' . filesize($path)) . '"';

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

        // Sanitised on upload; this stops anything that slipped through from
        // executing if the file is opened directly.
        if ($extension === 'svg') {
            header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
        }

        readfile($path);
        exit;
    }
}
