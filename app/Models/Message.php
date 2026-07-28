<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Two-way messages between the bid team and a client's portal users.
 */
final class Message
{
    /** @return array<int,array<string,mixed>> */
    public static function thread(int $clientId, ?int $bidId = null): array
    {
        $sql = 'SELECT m.*, b.reference AS bid_reference, b.title AS bid_title
                FROM messages m LEFT JOIN bids b ON b.id = m.bid_id
                WHERE m.client_id = ?';
        $params = [$clientId];

        if ($bidId !== null) {
            $sql .= ' AND m.bid_id = ?';
            $params[] = $bidId;
        }

        $sql .= ' ORDER BY m.created_at ASC, m.id ASC';
        return Database::all($sql, $params);
    }

    public static function send(int $clientId, ?int $bidId, string $senderType, ?int $senderId, string $senderName, string $body): int
    {
        return Database::insert('messages', [
            'client_id'   => $clientId,
            'bid_id'      => $bidId,
            'sender_type' => $senderType,
            'sender_id'   => $senderId,
            'sender_name' => mb_substr($senderName, 0, 140),
            'body'        => $body,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /** Mark everything from the other party as read. */
    public static function markRead(int $clientId, string $readerType): void
    {
        $otherParty = $readerType === 'staff' ? 'client' : 'staff';
        Database::run(
            'UPDATE messages SET read_at = ? WHERE client_id = ? AND sender_type = ? AND read_at IS NULL',
            [date('Y-m-d H:i:s'), $clientId, $otherParty]
        );
    }

    public static function unreadForClient(int $clientId): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM messages WHERE client_id = ? AND sender_type = 'staff' AND read_at IS NULL",
            [$clientId],
            0
        );
    }

    /**
     * Client conversations for the staff inbox, most recently active first.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function conversations(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        return Database::all(
            "SELECT c.id AS client_id, c.organisation, c.reference,
                    MAX(m.created_at) AS last_message_at,
                    COUNT(*) AS total_messages,
                    SUM(CASE WHEN m.sender_type = 'client' AND m.read_at IS NULL THEN 1 ELSE 0 END) AS unread
             FROM messages m
             JOIN clients c ON c.id = m.client_id
             GROUP BY c.id, c.organisation, c.reference
             ORDER BY unread DESC, last_message_at DESC
             LIMIT {$limit}"
        );
    }

    /** @return array<string,mixed>|null */
    public static function latestForClient(int $clientId): ?array
    {
        return Database::first(
            'SELECT * FROM messages WHERE client_id = ? ORDER BY created_at DESC, id DESC LIMIT 1',
            [$clientId]
        );
    }
}
