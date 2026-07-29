<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Uploader;

/**
 * The media library — images available to page blocks.
 */
final class Media
{
    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM media WHERE id = ?', [$id]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        return Database::all("SELECT * FROM media ORDER BY created_at DESC, id DESC LIMIT {$limit}");
    }

    /**
     * Record an already-stored upload.
     *
     * @param array{original_name:string,stored_name:string,mime_type:string,size_bytes:int} $stored
     */
    public static function record(array $stored, string $altText = ''): int
    {
        $width = null;
        $height = null;

        $path = Uploader::path($stored['stored_name']);
        if (is_file($path) && function_exists('getimagesize')) {
            $dimensions = @getimagesize($path);
            if (is_array($dimensions)) {
                $width = (int) $dimensions[0];
                $height = (int) $dimensions[1];
            }
        }

        return Database::insert('media', [
            'original_name' => $stored['original_name'],
            'stored_name'   => $stored['stored_name'],
            'mime_type'     => $stored['mime_type'],
            'size_bytes'    => $stored['size_bytes'],
            'width'         => $width,
            'height'        => $height,
            'alt_text'      => mb_substr($altText, 0, 255),
            'uploaded_by'   => Auth::id(Auth::STAFF),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    /** Public URL for an image, or null if the record or file has gone. */
    public static function url(?int $id): ?string
    {
        if ($id === null || $id <= 0) {
            return null;
        }

        $media = self::find($id);
        if ($media === null) {
            return null;
        }

        $path = Uploader::path((string) $media['stored_name']);
        if (!is_file($path)) {
            return null;
        }

        return Config::url('media/' . $id) . '?v=' . substr((string) filemtime($path), -6);
    }

    /** Remove the record and the file behind it. */
    public static function remove(int $id): bool
    {
        $media = self::find($id);
        if ($media === null) {
            return false;
        }

        Uploader::delete((string) $media['stored_name']);
        Database::delete('media', ['id' => $id]);

        return true;
    }

    /**
     * Which page blocks reference an image — shown before deleting so nobody
     * silently breaks a live page.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function usage(int $id): array
    {
        return Database::all(
            "SELECT DISTINCT p.id, p.title, p.slug
             FROM page_blocks b
             JOIN pages p ON p.id = b.page_id
             WHERE b.block_type = 'image' AND b.settings LIKE ?",
            ['%\"media_id\":\"' . $id . '\"%']
        );
    }

    public static function totalBytes(): int
    {
        return (int) Database::scalar('SELECT COALESCE(SUM(size_bytes), 0) FROM media', [], 0);
    }
}
