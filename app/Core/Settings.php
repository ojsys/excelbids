<?php

declare(strict_types=1);

namespace App\Core;

/**
 * System settings, loaded once per request and cached in memory.
 */
final class Settings
{
    /** @var array<string,string|null>|null */
    private static ?array $cache = null;

    /** @return array<string,string|null> */
    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = Database::pairs('SELECT `key`, `value` FROM settings');
        }
        return self::$cache;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = self::all()[$key] ?? null;
        return ($value === null || $value === '') ? $default : (string) $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::all()[$key] ?? null;
        if ($value === null || $value === '') {
            return $default;
        }
        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key);
        return $value === null ? $default : (int) $value;
    }

    public static function set(string $key, ?string $value): void
    {
        $exists = (int) Database::scalar('SELECT COUNT(*) FROM settings WHERE `key` = ?', [$key], 0) > 0;

        if ($exists) {
            Database::update('settings', ['value' => $value, 'updated_at' => date('Y-m-d H:i:s')], ['key' => $key]);
        } else {
            Database::insert('settings', [
                'key' => $key, 'value' => $value, 'group_name' => 'general', 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if (self::$cache !== null) {
            self::$cache[$key] = $value;
        }
    }

    /**
     * Definitions for a settings group, in display order.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function group(string $group): array
    {
        return Database::all(
            'SELECT * FROM settings WHERE group_name = ? ORDER BY sort_order, `key`',
            [$group]
        );
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
