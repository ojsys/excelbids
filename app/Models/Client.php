<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

/**
 * Client records and their portal users.
 */
final class Client
{
    public const STATUSES = [
        'prospect' => 'Prospect',
        'active'   => 'Active',
        'on_hold'  => 'On Hold',
        'archived' => 'Archived',
    ];

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::first(
            'SELECT c.*, u.name AS owner_name
             FROM clients c LEFT JOIN users u ON u.id = c.owner_user_id
             WHERE c.id = ?',
            [$id]
        );
    }

    /** Sequential client reference, e.g. CL-0042. */
    public static function nextReference(): string
    {
        $last = (string) Database::scalar(
            "SELECT reference FROM clients WHERE reference LIKE 'CL-%' ORDER BY reference DESC LIMIT 1",
            [],
            ''
        );
        $next = $last === '' ? 1 : ((int) substr($last, 3)) + 1;
        return sprintf('CL-%04d', $next);
    }

    /** @param array<string,mixed> $data */
    public static function create(array $data): int
    {
        $data['reference'] = $data['reference'] ?? self::nextReference();
        $data['created_at'] = date('Y-m-d H:i:s');
        return Database::insert('clients', $data);
    }

    /** @param array<string,mixed> $data */
    public static function update(int $id, array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        Database::update('clients', $data, ['id' => $id]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function portalUsers(int $clientId): array
    {
        return Database::all(
            'SELECT * FROM client_users WHERE client_id = ? ORDER BY is_primary DESC, name',
            [$clientId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function bids(int $clientId, ?string $status = null): array
    {
        $sql = 'SELECT b.*, u.name AS owner_name
                FROM bids b LEFT JOIN users u ON u.id = b.owner_user_id
                WHERE b.client_id = ?';
        $params = [$clientId];

        if ($status !== null && $status !== '') {
            $sql .= ' AND b.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY COALESCE(b.submission_due, b.created_at) DESC, b.id DESC';

        return Database::all($sql, $params);
    }

    /**
     * Headline numbers for a client record.
     *
     * @return array<string,mixed>
     */
    public static function stats(int $clientId): array
    {
        $row = Database::first(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status IN ('draft','in_progress','submitted') THEN 1 ELSE 0 END) AS open_bids,
                SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) AS won,
                SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) AS lost,
                SUM(CASE WHEN status = 'won' THEN contract_value ELSE 0 END) AS value_won,
                SUM(CASE WHEN status IN ('draft','in_progress','submitted') THEN contract_value ELSE 0 END) AS pipeline_value
             FROM bids WHERE client_id = ?",
            [$clientId]
        ) ?? [];

        $decided = (int) ($row['won'] ?? 0) + (int) ($row['lost'] ?? 0);

        return [
            'total'          => (int) ($row['total'] ?? 0),
            'open_bids'      => (int) ($row['open_bids'] ?? 0),
            'won'            => (int) ($row['won'] ?? 0),
            'lost'           => (int) ($row['lost'] ?? 0),
            'value_won'      => (float) ($row['value_won'] ?? 0),
            'pipeline_value' => (float) ($row['pipeline_value'] ?? 0),
            'win_rate'       => $decided > 0 ? round(((int) ($row['won'] ?? 0) / $decided) * 100, 1) : 0.0,
        ];
    }

    /**
     * Options for a client select box.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function options(bool $activeOnly = false): array
    {
        $sql = 'SELECT id, organisation, reference FROM clients';
        if ($activeOnly) {
            $sql .= " WHERE status <> 'archived'";
        }
        $sql .= ' ORDER BY organisation';
        return Database::all($sql);
    }

    /**
     * Create a portal login and return the invite token so the caller can email it.
     *
     * @param array<string,mixed> $data
     * @return array{id:int,token:string}
     */
    public static function createPortalUser(int $clientId, array $data): array
    {
        $token = random_token();

        $id = Database::insert('client_users', [
            'client_id'      => $clientId,
            'name'           => $data['name'],
            'email'          => mb_strtolower((string) $data['email']),
            'job_title'      => $data['job_title'] ?? '',
            'phone'          => $data['phone'] ?? '',
            'is_primary'     => !empty($data['is_primary']) ? 1 : 0,
            'is_active'      => 1,
            'invite_token'   => $token,
            'invite_expires' => date('Y-m-d H:i:s', time() + (7 * 86400)),
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        // Only one primary contact per client.
        if (!empty($data['is_primary'])) {
            Database::run(
                'UPDATE client_users SET is_primary = 0 WHERE client_id = ? AND id <> ?',
                [$clientId, $id]
            );
        }

        return ['id' => $id, 'token' => $token];
    }

    /** Unread message count for the staff inbox badge. */
    public static function unreadMessageCount(?int $clientId = null): int
    {
        $sql = "SELECT COUNT(*) FROM messages WHERE sender_type = 'client' AND read_at IS NULL";
        $params = [];
        if ($clientId !== null) {
            $sql .= ' AND client_id = ?';
            $params[] = $clientId;
        }
        return (int) Database::scalar($sql, $params, 0);
    }

    /** Convert a website enquiry into a client record. */
    public static function fromEnquiry(array $enquiry): int
    {
        $clientId = self::create([
            'organisation'  => $enquiry['organisation'] !== '' ? $enquiry['organisation'] : $enquiry['name'],
            'contact_name'  => $enquiry['name'],
            'email'         => $enquiry['email'],
            'phone'         => $enquiry['phone'],
            'sector'        => $enquiry['sector'],
            'status'        => 'prospect',
            'owner_user_id' => $enquiry['assigned_to'] ?? Auth::id(Auth::STAFF),
            'notes'         => "Converted from consultation request {$enquiry['reference']}.\n\n" . (string) $enquiry['message'],
        ]);

        Database::update('enquiries', [
            'status'     => 'converted',
            'client_id'  => $clientId,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $enquiry['id']]);

        return $clientId;
    }
}
