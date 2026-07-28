<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

/**
 * Staff accounts for the admin panel.
 */
final class User
{
    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM users WHERE id = ?', [$id]);
    }

    /** @return array<string,mixed>|null */
    public static function findByEmail(string $email): ?array
    {
        return Database::first('SELECT * FROM users WHERE email = ?', [mb_strtolower($email)]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM users';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY name';
        return Database::all($sql);
    }

    /**
     * Staff who can own bids — everyone but read-only viewers.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function assignable(): array
    {
        return Database::all(
            "SELECT id, name, role FROM users WHERE is_active = 1 AND role <> 'viewer' ORDER BY name"
        );
    }

    /** @param array<string,mixed> $data */
    public static function create(array $data): int
    {
        $data['email'] = mb_strtolower((string) $data['email']);
        $data['created_at'] = date('Y-m-d H:i:s');
        return Database::insert('users', $data);
    }

    /** @param array<string,mixed> $data */
    public static function update(int $id, array $data): void
    {
        if (isset($data['email'])) {
            $data['email'] = mb_strtolower((string) $data['email']);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        Database::update('users', $data, ['id' => $id]);
    }

    public static function setPassword(int $id, string $password): void
    {
        Database::update('users', [
            'password_hash'  => Auth::hash($password),
            'must_change_pw' => 0,
            'reset_token'    => null,
            'reset_expires'  => null,
            'updated_at'     => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    /** Issue a password reset token valid for one hour. */
    public static function createResetToken(int $id): string
    {
        $token = random_token();
        Database::update('users', [
            'reset_token'   => $token,
            'reset_expires' => date('Y-m-d H:i:s', time() + 3600),
        ], ['id' => $id]);
        return $token;
    }

    /** @return array<string,mixed>|null */
    public static function findByResetToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        return Database::first(
            'SELECT * FROM users WHERE reset_token = ? AND reset_expires > ? AND is_active = 1',
            [$token, date('Y-m-d H:i:s')]
        );
    }

    /**
     * Guard against removing the last administrator, which would lock
     * everybody out of settings and user management.
     */
    public static function isLastActiveAdmin(int $id): bool
    {
        $user = self::find($id);
        if ($user === null || $user['role'] !== 'admin' || (int) $user['is_active'] !== 1) {
            return false;
        }

        $others = (int) Database::scalar(
            "SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1 AND id <> ?",
            [$id],
            0
        );

        return $others === 0;
    }

    /** Open work assigned to a staff member, for their dashboard. */
    public static function workload(int $userId): array
    {
        $row = Database::first(
            "SELECT
                COUNT(*) AS open_bids,
                SUM(CASE WHEN submission_due IS NOT NULL AND submission_due <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS due_this_week
             FROM bids
             WHERE owner_user_id = ? AND status IN ('draft','in_progress','submitted')",
            [$userId]
        ) ?? [];

        $tasks = (int) Database::scalar(
            'SELECT COUNT(*) FROM bid_tasks WHERE assignee_id = ? AND is_done = 0',
            [$userId],
            0
        );

        return [
            'open_bids'     => (int) ($row['open_bids'] ?? 0),
            'due_this_week' => (int) ($row['due_this_week'] ?? 0),
            'open_tasks'    => $tasks,
        ];
    }
}
