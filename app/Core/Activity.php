<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Append-only audit trail. Used by the admin dashboard's recent-activity feed
 * and for answering "who changed this and when".
 */
final class Activity
{
    public static function log(string $action, string $entityType = '', ?int $entityId = null, string $description = ''): void
    {
        [$actorType, $actorId, $actorName] = self::actor();

        try {
            Database::insert('activity_log', [
                'actor_type'  => $actorType,
                'actor_id'    => $actorId,
                'actor_name'  => mb_substr($actorName, 0, 140),
                'action'      => mb_substr($action, 0, 80),
                'entity_type' => mb_substr($entityType, 0, 60),
                'entity_id'   => $entityId,
                'description' => mb_substr($description, 0, 255),
                'ip_address'  => client_ip(),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Never let an audit write break the action it is recording.
            error_log('[activity] ' . $e->getMessage());
        }
    }

    /** @return array{0:string,1:int|null,2:string} */
    private static function actor(): array
    {
        $staff = Auth::user(Auth::STAFF);
        if ($staff !== null) {
            return ['staff', (int) $staff['id'], (string) $staff['name']];
        }

        $client = Auth::user(Auth::CLIENT);
        if ($client !== null) {
            return ['client', (int) $client['id'], (string) $client['name']];
        }

        return ['system', null, 'System'];
    }

    /** @return array<int,array<string,mixed>> */
    public static function recent(int $limit = 15): array
    {
        $limit = max(1, min(100, $limit));
        return Database::all("SELECT * FROM activity_log ORDER BY created_at DESC, id DESC LIMIT {$limit}");
    }

    /** @return array<int,array<string,mixed>> */
    public static function forEntity(string $entityType, int $entityId, int $limit = 30): array
    {
        $limit = max(1, min(200, $limit));
        return Database::all(
            "SELECT * FROM activity_log WHERE entity_type = ? AND entity_id = ?
             ORDER BY created_at DESC, id DESC LIMIT {$limit}",
            [$entityType, $entityId]
        );
    }
}
