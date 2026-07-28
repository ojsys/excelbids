<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Offset pagination for list screens. Builds page links that preserve the
 * current filters in the query string.
 */
final class Paginator
{
    /** @var array<int,array<string,mixed>> */
    public array $items;

    public int $total;
    public int $perPage;
    public int $currentPage;
    public int $lastPage;

    /** @var array<string,mixed> */
    private array $query;

    public function __construct(array $items, int $total, int $perPage, int $currentPage, array $query = [])
    {
        $this->items = $items;
        $this->total = $total;
        $this->perPage = max(1, $perPage);
        $this->lastPage = max(1, (int) ceil($total / $this->perPage));
        $this->currentPage = min(max(1, $currentPage), $this->lastPage);

        unset($query['page']);
        $this->query = $query;
    }

    /**
     * Run a count + page query pair and build the paginator.
     *
     * @param string $selectSql Full SELECT with ORDER BY, without LIMIT
     * @param string $countSql  Matching SELECT COUNT(*)
     */
    public static function make(string $selectSql, string $countSql, array $params, int $page, int $perPage, array $query = []): self
    {
        $total = (int) Database::scalar($countSql, $params, 0);
        $page = max(1, $page);
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;

        // LIMIT/OFFSET are cast to int here, never bound from raw input.
        $sql = $selectSql . sprintf(' LIMIT %d OFFSET %d', (int) $perPage, (int) $offset);
        $items = Database::all($sql, $params);

        return new self($items, $total, $perPage, $page, $query);
    }

    public function hasPages(): bool
    {
        return $this->lastPage > 1;
    }

    public function urlForPage(int $page): string
    {
        $query = array_merge($this->query, ['page' => $page]);
        return '?' . http_build_query($query);
    }

    /** First item number on the current page, 1-indexed. */
    public function from(): int
    {
        return $this->total === 0 ? 0 : (($this->currentPage - 1) * $this->perPage) + 1;
    }

    public function to(): int
    {
        return min($this->total, $this->currentPage * $this->perPage);
    }

    /**
     * Page numbers to render, with 0 standing in for an ellipsis.
     *
     * @return array<int,int>
     */
    public function window(int $each = 2): array
    {
        if ($this->lastPage <= 7) {
            return range(1, $this->lastPage);
        }

        $pages = [1];
        $start = max(2, $this->currentPage - $each);
        $end = min($this->lastPage - 1, $this->currentPage + $each);

        if ($start > 2) {
            $pages[] = 0;
        }
        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }
        if ($end < $this->lastPage - 1) {
            $pages[] = 0;
        }
        $pages[] = $this->lastPage;

        return $pages;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
