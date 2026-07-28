<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Uploader;

/**
 * Bid and client document records. Files themselves live outside the document
 * root; these rows carry the metadata and the client-visibility flag.
 */
final class Document
{
    public const CATEGORIES = [
        'tender_pack'   => 'Tender pack / ITT',
        'draft'         => 'Draft response',
        'final'         => 'Final submission',
        'evidence'      => 'Evidence & policies',
        'clarification' => 'Clarifications',
        'feedback'      => 'Buyer feedback',
        'contract'      => 'Contract & onboarding',
        'general'       => 'General',
    ];

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM documents WHERE id = ?', [$id]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function forBid(int $bidId, bool $clientVisibleOnly = false): array
    {
        $sql = 'SELECT * FROM documents WHERE bid_id = ?';
        if ($clientVisibleOnly) {
            $sql .= ' AND visible_to_client = 1';
        }
        $sql .= ' ORDER BY created_at DESC, id DESC';
        return Database::all($sql, [$bidId]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function forClient(int $clientId, bool $clientVisibleOnly = false): array
    {
        $sql = 'SELECT d.*, b.reference AS bid_reference, b.title AS bid_title
                FROM documents d
                LEFT JOIN bids b ON b.id = d.bid_id
                WHERE d.client_id = ?';
        if ($clientVisibleOnly) {
            $sql .= ' AND d.visible_to_client = 1';
        }
        $sql .= ' ORDER BY d.created_at DESC, d.id DESC';
        return Database::all($sql, [$clientId]);
    }

    /**
     * Record an already-stored upload.
     *
     * @param array{original_name:string,stored_name:string,mime_type:string,size_bytes:int} $stored
     */
    public static function record(array $stored, array $context): int
    {
        return Database::insert('documents', [
            'client_id'         => $context['client_id'] ?? null,
            'bid_id'            => $context['bid_id'] ?? null,
            'category'          => $context['category'] ?? 'general',
            'original_name'     => $stored['original_name'],
            'stored_name'       => $stored['stored_name'],
            'mime_type'         => $stored['mime_type'],
            'size_bytes'        => $stored['size_bytes'],
            'uploader_type'     => $context['uploader_type'] ?? 'staff',
            'uploader_id'       => $context['uploader_id'] ?? null,
            'visible_to_client' => !empty($context['visible_to_client']) ? 1 : 0,
            'notes'             => mb_substr((string) ($context['notes'] ?? ''), 0, 255),
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    /** Remove the row and the file behind it. */
    public static function remove(int $id): bool
    {
        $document = self::find($id);
        if ($document === null) {
            return false;
        }
        Uploader::delete((string) $document['stored_name']);
        Database::delete('documents', ['id' => $id]);
        return true;
    }

    /** A short label for the file type, used as an icon caption. */
    public static function extension(string $originalName): string
    {
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        return $extension === '' ? 'file' : $extension;
    }

    public static function totalSizeForClient(int $clientId): int
    {
        return (int) Database::scalar(
            'SELECT COALESCE(SUM(size_bytes), 0) FROM documents WHERE client_id = ?',
            [$clientId],
            0
        );
    }
}
