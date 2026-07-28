<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * File uploads for bid and client documents.
 *
 * Files are stored outside the document root under storage/uploads with an
 * opaque generated name, and are only ever served back through a controller
 * that has already checked authorisation. Nothing is executable and nothing is
 * reachable by guessing a URL.
 */
final class Uploader
{
    /** Extension → accepted MIME types. Anything not listed is rejected. */
    private const ALLOWED = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls'  => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'ppt'  => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'odt'  => ['application/vnd.oasis.opendocument.text'],
        'ods'  => ['application/vnd.oasis.opendocument.spreadsheet'],
        'csv'  => ['text/csv', 'text/plain', 'application/csv'],
        'txt'  => ['text/plain'],
        'rtf'  => ['application/rtf', 'text/rtf'],
        'zip'  => ['application/zip', 'application/x-zip-compressed'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'gif'  => ['image/gif'],
        'webp' => ['image/webp'],
    ];

    /** Image types accepted for brand assets (logo, favicon, share image). */
    private const ALLOWED_IMAGES = [
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'gif'  => ['image/gif'],
        'webp' => ['image/webp'],
        'svg'  => ['image/svg+xml', 'text/plain', 'text/xml', 'application/xml'],
        'ico'  => ['image/vnd.microsoft.icon', 'image/x-icon', 'application/octet-stream'],
    ];

    /** @return array<int,string> */
    public static function allowedExtensions(): array
    {
        return array_keys(self::ALLOWED);
    }

    /** @return array<int,string> */
    public static function allowedImageExtensions(): array
    {
        return array_keys(self::ALLOWED_IMAGES);
    }

    /** Brand assets are small; a tight cap keeps pages fast. */
    public static function maxImageBytes(): int
    {
        return min(2 * 1024 * 1024, self::maxBytes());
    }

    /**
     * Validate and store a brand image.
     *
     * Kept separate from store() because the accepted types, size limit and
     * post-processing all differ — and because SVGs need sanitising.
     *
     * @param array<string,mixed> $file A single entry from $_FILES
     * @return array{original_name:string,stored_name:string,mime_type:string,size_bytes:int}
     * @throws RuntimeException with a message safe to show the user
     */
    public static function storeImage(array $file, string $subdirectory = 'branding'): array
    {
        self::assertUploadOk($file);

        $originalName = (string) ($file['name'] ?? 'image');
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));

        if (!isset(self::ALLOWED_IMAGES[$extension])) {
            throw new RuntimeException(
                'That image type is not accepted. Please use: ' . implode(', ', self::allowedImageExtensions()) . '.'
            );
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            throw new RuntimeException('That file appears to be empty.');
        }
        if ($size > self::maxImageBytes()) {
            throw new RuntimeException('That image is too large. The limit is ' . filesize_human(self::maxImageBytes()) . '.');
        }

        $tmpPath = (string) $file['tmp_name'];
        $detected = self::detectMime($tmpPath);

        if (!in_array($detected, self::ALLOWED_IMAGES[$extension], true)) {
            throw new RuntimeException('That file\'s contents do not match its ' . $extension . ' extension.');
        }

        // Raster formats must actually decode — a renamed file is not an image.
        if ($extension !== 'svg' && $extension !== 'ico' && function_exists('getimagesize')) {
            $dimensions = @getimagesize($tmpPath);
            if ($dimensions === false) {
                throw new RuntimeException('That image could not be read. Please re-export it and try again.');
            }
        }

        $directory = self::directory($subdirectory);
        $storedName = bin2hex(random_bytes(12)) . '.' . $extension;
        $target = $directory . '/' . $storedName;

        if (!move_uploaded_file($tmpPath, $target)) {
            throw new RuntimeException('The image could not be saved. Check the storage folder is writable.');
        }
        @chmod($target, 0644);

        // An SVG is a document, not just pixels: strip anything executable.
        if ($extension === 'svg') {
            $clean = self::sanitizeSvg((string) file_get_contents($target));
            if ($clean === null) {
                @unlink($target);
                throw new RuntimeException('That SVG could not be read as valid XML. Please re-export it, or upload a PNG instead.');
            }
            file_put_contents($target, $clean);
            $size = (int) filesize($target);
        }

        return [
            'original_name' => self::sanitizeName($originalName),
            'stored_name'   => $subdirectory . '/' . $storedName,
            'mime_type'     => $extension === 'svg' ? 'image/svg+xml' : $detected,
            'size_bytes'    => $size,
        ];
    }

    /**
     * Remove scripting from an SVG.
     *
     * Belt and braces alongside the CSP the branding route sends: an SVG served
     * from our own origin would otherwise be able to run script if opened directly.
     *
     * @return string|null Null when the input is not parseable XML.
     */
    private static function sanitizeSvg(string $svg): ?string
    {
        // Entity expansion is an attack surface of its own; disable the loader.
        $previous = libxml_use_internal_errors(true);
        if (function_exists('libxml_disable_entity_loader') && PHP_VERSION_ID < 80000) {
            @libxml_disable_entity_loader(true);
        }

        $document = new \DOMDocument();
        $loaded = $document->loadXML($svg, LIBXML_NONET | LIBXML_NOENT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded || $document->documentElement === null) {
            return null;
        }

        $forbiddenTags = ['script', 'foreignObject', 'iframe', 'embed', 'object', 'animate', 'set', 'handler'];
        $xpath = new \DOMXPath($document);

        foreach ($forbiddenTags as $tag) {
            $nodes = $xpath->query('//*[local-name()="' . $tag . '"]');
            if ($nodes !== false) {
                foreach (iterator_to_array($nodes) as $node) {
                    $node->parentNode?->removeChild($node);
                }
            }
        }

        // Strip event handlers and any URL that is not a plain fragment or data image.
        $all = $xpath->query('//*');
        if ($all !== false) {
            foreach ($all as $element) {
                if (!$element instanceof \DOMElement) {
                    continue;
                }
                foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
                    $name = strtolower($attribute->nodeName);
                    $value = trim($attribute->nodeValue ?? '');

                    if (str_starts_with($name, 'on')) {
                        $element->removeAttribute($attribute->nodeName);
                        continue;
                    }
                    if (in_array($name, ['href', 'xlink:href', 'src', 'from', 'to', 'values'], true)) {
                        $isSafe = str_starts_with($value, '#')
                            || str_starts_with($value, 'data:image/')
                            || $value === '';
                        if (!$isSafe) {
                            $element->removeAttribute($attribute->nodeName);
                        }
                    }
                    if ($name === 'style' && preg_match('/(javascript:|expression\(|url\s*\()/i', $value)) {
                        $element->removeAttribute($attribute->nodeName);
                    }
                }
            }
        }

        return (string) $document->saveXML();
    }

    public static function maxBytes(): int
    {
        $configured = Settings::int('upload_max_mb', 10) * 1024 * 1024;
        $phpLimit = min(
            self::parseIniSize((string) ini_get('upload_max_filesize')),
            self::parseIniSize((string) ini_get('post_max_size'))
        );
        return $phpLimit > 0 ? min($configured, $phpLimit) : $configured;
    }

    /**
     * Validate and store one uploaded file.
     *
     * @param array<string,mixed> $file A single entry from $_FILES
     * @return array{original_name:string,stored_name:string,mime_type:string,size_bytes:int}
     * @throws RuntimeException with a message safe to show the user
     */
    public static function store(array $file, string $subdirectory = 'documents'): array
    {
        self::assertUploadOk($file);

        $originalName = (string) ($file['name'] ?? 'file');
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));

        if (!isset(self::ALLOWED[$extension])) {
            throw new RuntimeException(
                'That file type is not accepted. Allowed types: ' . implode(', ', self::allowedExtensions()) . '.'
            );
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            throw new RuntimeException('That file appears to be empty.');
        }
        if ($size > self::maxBytes()) {
            throw new RuntimeException('That file is too large. The limit is ' . filesize_human(self::maxBytes()) . '.');
        }

        // Trust the file's contents over the browser-supplied Content-Type.
        $detected = self::detectMime((string) $file['tmp_name']);
        if (!in_array($detected, self::ALLOWED[$extension], true)) {
            throw new RuntimeException('That file\'s contents do not match its ' . $extension . ' extension.');
        }

        $directory = self::directory($subdirectory);
        $storedName = date('Y/m/') . bin2hex(random_bytes(16)) . '.' . $extension;
        $target = $directory . '/' . $storedName;

        $targetDir = dirname($target);
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new RuntimeException('The upload folder could not be created. Check folder permissions.');
        }

        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            throw new RuntimeException('The file could not be saved. Check the storage folder is writable.');
        }
        @chmod($target, 0644);

        return [
            'original_name' => self::sanitizeName($originalName),
            'stored_name'   => $subdirectory . '/' . $storedName,
            'mime_type'     => $detected,
            'size_bytes'    => $size,
        ];
    }

    /** Absolute path on disk for a stored_name value. */
    public static function path(string $storedName): string
    {
        // Defend against traversal in a value read back from the database.
        $safe = str_replace(['..', "\0"], '', $storedName);
        return self::root() . '/' . ltrim($safe, '/');
    }

    public static function delete(string $storedName): bool
    {
        $path = self::path($storedName);
        return is_file($path) ? @unlink($path) : true;
    }

    // -- Internals ----------------------------------------------------------

    private static function root(): string
    {
        return Config::storagePath('uploads');
    }

    private static function directory(string $subdirectory): string
    {
        $subdirectory = preg_replace('/[^a-z0-9_\-]/i', '', $subdirectory) ?: 'documents';
        $directory = self::root() . '/' . $subdirectory;

        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('The storage folder could not be created.');
        }

        // Belt and braces: even if storage ends up web-reachable, deny execution.
        $htaccess = self::root() . '/.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
        }

        return $directory;
    }

    /** @param array<string,mixed> $file */
    private static function assertUploadOk(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_OK) {
            if (!is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
                throw new RuntimeException('That upload could not be verified.');
            }
            return;
        }

        throw new RuntimeException(match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'That file is larger than the server allows.',
            UPLOAD_ERR_PARTIAL   => 'The upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE   => 'No file was selected.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'The server could not write the file. Contact your host.',
            UPLOAD_ERR_EXTENSION => 'The upload was blocked by a server extension.',
            default              => 'The file could not be uploaded.',
        });
    }

    private static function detectMime(string $tmpPath): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $tmpPath);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($tmpPath);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }
        return 'application/octet-stream';
    }

    /** Strip directory components and control characters from a display name. */
    private static function sanitizeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? $name;
        $name = preg_replace('/["<>|:*?]/', '-', $name) ?? $name;
        return mb_substr(trim($name), 0, 200) ?: 'file';
    }

    private static function parseIniSize(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        $unit = strtolower($value[strlen($value) - 1]);
        $number = (int) $value;
        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
