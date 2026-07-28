<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Two independent authentication guards sharing one session:
 *   - "staff"  → the `users` table, admin panel
 *   - "client" → the `client_users` table, client portal
 *
 * Keeping them separate means a client login can never reach admin routes even
 * if a route is misconfigured, because the guard key simply is not present.
 */
final class Auth
{
    public const STAFF = 'staff';
    public const CLIENT = 'client';

    /** Role capability matrix for the admin panel. */
    private const ROLE_ABILITIES = [
        'admin' => ['*'],
        'manager' => [
            'dashboard.view', 'clients.view', 'clients.manage', 'bids.view', 'bids.manage',
            'enquiries.view', 'enquiries.manage', 'reports.view', 'cms.view', 'cms.manage',
            'documents.manage', 'messages.manage',
        ],
        'writer' => [
            'dashboard.view', 'clients.view', 'bids.view', 'bids.manage',
            'enquiries.view', 'reports.view', 'documents.manage', 'messages.manage',
        ],
        'viewer' => [
            'dashboard.view', 'clients.view', 'bids.view', 'enquiries.view', 'reports.view',
        ],
    ];

    /** @var array<string,array<string,mixed>|null> Per-request cache. */
    private static array $cache = [];

    // -- Session state ------------------------------------------------------

    public static function login(string $guard, int $id): void
    {
        session_regenerate_id(true);
        $_SESSION['auth'][$guard] = ['id' => $id, 'at' => time()];
        Csrf::rotate();
        unset(self::$cache[$guard]);
    }

    public static function logout(string $guard): void
    {
        unset($_SESSION['auth'][$guard], self::$cache[$guard]);
        if (empty($_SESSION['auth'])) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
    }

    public static function id(string $guard): ?int
    {
        $id = $_SESSION['auth'][$guard]['id'] ?? null;
        return $id === null ? null : (int) $id;
    }

    public static function check(string $guard): bool
    {
        return self::user($guard) !== null;
    }

    /**
     * The authenticated record, re-read from the database so that deactivating
     * an account takes effect on the very next request.
     *
     * @return array<string,mixed>|null
     */
    public static function user(string $guard)
    {
        if (array_key_exists($guard, self::$cache)) {
            return self::$cache[$guard];
        }

        $id = self::id($guard);
        if ($id === null) {
            return self::$cache[$guard] = null;
        }

        if ($guard === self::STAFF) {
            $row = Database::first('SELECT * FROM users WHERE id = ? AND is_active = 1', [$id]);
        } else {
            $row = Database::first(
                'SELECT cu.*, c.organisation, c.reference AS client_reference, c.status AS client_status
                 FROM client_users cu
                 JOIN clients c ON c.id = cu.client_id
                 WHERE cu.id = ? AND cu.is_active = 1 AND c.status <> \'archived\'',
                [$id]
            );
        }

        if ($row === null) {
            unset($_SESSION['auth'][$guard]);
        }

        return self::$cache[$guard] = $row;
    }

    // -- Credentials --------------------------------------------------------

    /**
     * Verify an email/password pair. Returns the user row or null.
     * Runs a dummy hash comparison on unknown emails so response timing does
     * not reveal whether an address exists.
     */
    public static function attempt(string $guard, string $email, string $password): ?array
    {
        $table = $guard === self::STAFF ? 'users' : 'client_users';
        $row = Database::first("SELECT * FROM {$table} WHERE email = ? LIMIT 1", [mb_strtolower($email)]);

        if ($row === null || empty($row['password_hash'])) {
            password_verify($password, '$2y$10$usesomesillystringfore.Hxk1zVQwFvGvJ1TgFqz3xJ0K7wRy2');
            return null;
        }

        if (!password_verify($password, (string) $row['password_hash'])) {
            return null;
        }

        if ((int) $row['is_active'] !== 1) {
            return null;
        }

        // Transparently upgrade hashes when PHP's default cost changes.
        if (password_needs_rehash((string) $row['password_hash'], PASSWORD_DEFAULT)) {
            Database::update($table, ['password_hash' => self::hash($password)], ['id' => $row['id']]);
        }

        return $row;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function recordLogin(string $guard, int $id): void
    {
        $table = $guard === self::STAFF ? 'users' : 'client_users';
        Database::update($table, [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => client_ip(),
        ], ['id' => $id]);
    }

    // -- Throttling ---------------------------------------------------------

    public static function tooManyAttempts(string $guard, string $identifier): bool
    {
        $max = (int) Config::get('security.max_login_attempts', 5);
        $minutes = (int) Config::get('security.lockout_minutes', 15);
        $since = date('Y-m-d H:i:s', time() - ($minutes * 60));

        $count = (int) Database::scalar(
            'SELECT COUNT(*) FROM login_attempts
             WHERE guard = ? AND (identifier = ? OR ip_address = ?) AND created_at > ?',
            [$guard, mb_strtolower($identifier), client_ip(), $since],
            0
        );

        return $count >= $max;
    }

    public static function recordFailedAttempt(string $guard, string $identifier): void
    {
        Database::insert('login_attempts', [
            'guard'      => $guard,
            'identifier' => mb_strtolower($identifier),
            'ip_address' => client_ip(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function clearAttempts(string $guard, string $identifier): void
    {
        Database::run(
            'DELETE FROM login_attempts WHERE guard = ? AND (identifier = ? OR ip_address = ?)',
            [$guard, mb_strtolower($identifier), client_ip()]
        );
    }

    /** Housekeeping — drop attempt rows older than a day. */
    public static function pruneAttempts(): void
    {
        Database::run('DELETE FROM login_attempts WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
    }

    public static function lockoutMinutes(): int
    {
        return (int) Config::get('security.lockout_minutes', 15);
    }

    // -- Authorisation ------------------------------------------------------

    /** Does the signed-in staff member hold this ability? */
    public static function can(string $ability): bool
    {
        $user = self::user(self::STAFF);
        if ($user === null) {
            return false;
        }

        $abilities = self::ROLE_ABILITIES[$user['role']] ?? [];
        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    /** Abort with 403 unless the ability is held. */
    public static function authorize(string $ability): void
    {
        if (!self::can($ability)) {
            Response::error(403, 'You do not have permission to do that.');
        }
    }

    public static function isAdmin(): bool
    {
        $user = self::user(self::STAFF);
        return $user !== null && $user['role'] === 'admin';
    }

    /** @return array<string,string> */
    public static function roles(): array
    {
        return [
            'admin'   => 'Administrator — full access including settings and staff accounts',
            'manager' => 'Manager — clients, bids, enquiries, reports and website content',
            'writer'  => 'Bid Writer — bids, documents and messages',
            'viewer'  => 'Viewer — read-only access',
        ];
    }
}
