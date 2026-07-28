<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Consultation requests submitted through the public site.
 */
final class Enquiry
{
    public const STATUSES = [
        'new'       => 'New',
        'contacted' => 'Contacted',
        'qualified' => 'Qualified',
        'converted' => 'Converted to client',
        'closed'    => 'Closed',
    ];

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::first(
            'SELECT e.*, u.name AS assigned_name, c.organisation AS client_organisation
             FROM enquiries e
             LEFT JOIN users u ON u.id = e.assigned_to
             LEFT JOIN clients c ON c.id = e.client_id
             WHERE e.id = ?',
            [$id]
        );
    }

    public static function nextReference(): string
    {
        $year = date('Y');
        $last = (string) Database::scalar(
            'SELECT reference FROM enquiries WHERE reference LIKE ? ORDER BY reference DESC LIMIT 1',
            ["REQ/{$year}/%"],
            ''
        );
        $next = $last === '' ? 1 : ((int) substr($last, -4)) + 1;
        return sprintf('REQ/%s/%04d', $year, $next);
    }

    /** @param array<string,mixed> $data */
    public static function create(array $data): int
    {
        $data['reference'] = self::nextReference();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['ip_address'] = client_ip();
        return Database::insert('enquiries', $data);
    }

    public static function newCount(): int
    {
        return (int) Database::scalar("SELECT COUNT(*) FROM enquiries WHERE status = 'new'", [], 0);
    }

    /**
     * Simple per-IP rate limit on the public form, so the inbox cannot be
     * flooded from a single source.
     */
    public static function tooManyRecent(string $ip, int $limit = 5, int $withinMinutes = 60): bool
    {
        if ($ip === '') {
            return false;
        }
        $count = (int) Database::scalar(
            'SELECT COUNT(*) FROM enquiries WHERE ip_address = ? AND created_at > ?',
            [$ip, date('Y-m-d H:i:s', time() - ($withinMinutes * 60))],
            0
        );
        return $count >= $limit;
    }

    public static function statusTone(string $status): string
    {
        return match ($status) {
            'new'       => 'warning',
            'contacted' => 'info',
            'qualified' => 'info',
            'converted' => 'success',
            default     => 'muted',
        };
    }
}
