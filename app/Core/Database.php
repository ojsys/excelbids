<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Thin PDO wrapper. Every query in the application goes through here with bound
 * parameters — there is no string interpolation of user input anywhere.
 */
final class Database
{
    private static ?PDO $pdo = null;

    /** @var array<string,mixed> */
    private static array $config = [];

    private static int $queryCount = 0;

    /** @param array<string,mixed> $config */
    public static function configure(array $config): void
    {
        self::$config = $config;
        self::$pdo = null;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $c = self::$config;
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $c['host'] ?? 'localhost',
            (int) ($c['port'] ?? 3306),
            $c['name'] ?? '',
            $c['charset'] ?? 'utf8mb4'
        );

        try {
            self::$pdo = new PDO($dsn, (string) ($c['user'] ?? ''), (string) ($c['password'] ?? ''), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
            // Reject zero dates and silent truncation rather than storing bad rows.
            self::$pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }

        return self::$pdo;
    }

    /** @param array<string|int,mixed> $params */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        self::$queryCount++;
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch all rows.
     *
     * @param array<string|int,mixed> $params
     * @return array<int,array<string,mixed>>
     */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /**
     * Fetch a single row, or null.
     *
     * @param array<string|int,mixed> $params
     * @return array<string,mixed>|null
     */
    public static function first(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Fetch a single scalar from the first column of the first row. */
    public static function scalar(string $sql, array $params = [], $default = null)
    {
        $value = self::run($sql, $params)->fetchColumn();
        return $value === false ? $default : $value;
    }

    /**
     * Fetch a key => value map from a two-column result.
     *
     * @return array<string,mixed>
     */
    public static function pairs(string $sql, array $params = []): array
    {
        $out = [];
        foreach (self::all($sql, $params) as $row) {
            $values = array_values($row);
            $out[(string) $values[0]] = $values[1] ?? null;
        }
        return $out;
    }

    /** @param array<string,mixed> $data */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            '`' . implode('`, `', $columns) . '`',
            ':' . implode(', :', $columns)
        );
        self::run($sql, self::bindable($data));
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $where
     */
    public static function update(string $table, array $data, array $where): int
    {
        if (!$data) {
            return 0;
        }
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "`{$column}` = :set_{$column}";
        }
        $conditions = [];
        foreach (array_keys($where) as $column) {
            $conditions[] = "`{$column}` = :where_{$column}";
        }

        $params = [];
        foreach (self::bindable($data) as $key => $value) {
            $params['set_' . ltrim((string) $key, ':')] = $value;
        }
        foreach (self::bindable($where) as $key => $value) {
            $params['where_' . ltrim((string) $key, ':')] = $value;
        }

        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE %s',
            $table,
            implode(', ', $set),
            implode(' AND ', $conditions)
        );
        return self::run($sql, $params)->rowCount();
    }

    /** @param array<string,mixed> $where */
    public static function delete(string $table, array $where): int
    {
        $conditions = [];
        foreach (array_keys($where) as $column) {
            $conditions[] = "`{$column}` = :{$column}";
        }
        $sql = sprintf('DELETE FROM `%s` WHERE %s', $table, implode(' AND ', $conditions));
        return self::run($sql, self::bindable($where))->rowCount();
    }

    public static function transaction(callable $callback)
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function queryCount(): int
    {
        return self::$queryCount;
    }

    /** Convert booleans and DateTimes to values PDO can bind. */
    private static function bindable(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 1 : 0;
            } elseif ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            }
            $out[$key] = $value;
        }
        return $out;
    }
}
