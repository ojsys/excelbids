<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Blocks;
use App\Core\Content;
use App\Core\Database;

/**
 * Pages and the blocks that make them up.
 */
final class Page
{
    /** Slugs the application's own routes already own. */
    public const RESERVED_SLUGS = [
        'admin', 'portal', 'install', 'assets', 'branding', 'media', 'forms',
        'consultation', 'robots.txt', 'sitemap.xml',
    ];

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM pages WHERE id = ?', [$id]);
    }

    /** @return array<string,mixed>|null */
    public static function findBySlug(string $slug, bool $publishedOnly = true): ?array
    {
        $sql = 'SELECT * FROM pages WHERE slug = ?';
        if ($publishedOnly) {
            $sql .= ' AND is_published = 1';
        }
        return Database::first($sql, [$slug]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(): array
    {
        return Database::all('SELECT * FROM pages ORDER BY sort_order, id');
    }

    // -- Blocks -------------------------------------------------------------

    /**
     * The page's blocks as a two-level tree: sections, each with columns of
     * child blocks. One query, assembled in PHP.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function blockTree(int $pageId, bool $visibleOnly = false): array
    {
        $sql = 'SELECT * FROM page_blocks WHERE page_id = ?';
        if ($visibleOnly) {
            $sql .= ' AND is_visible = 1';
        }
        $sql .= ' ORDER BY sort_order, id';

        $rows = Database::all($sql, [$pageId]);

        $sections = [];
        $children = [];

        foreach ($rows as $row) {
            $row['settings'] = self::decode($row['settings'] ?? null);

            if ($row['parent_id'] === null) {
                $row['columns'] = [];
                $sections[(int) $row['id']] = $row;
            } else {
                $children[(int) $row['parent_id']][] = $row;
            }
        }

        foreach ($sections as $id => $section) {
            $count = Blocks::columnCount((string) ($section['settings']['columns'] ?? '1'));

            // Pre-fill every column so the builder always shows a drop target.
            $columns = array_fill(0, $count, []);

            foreach ($children[$id] ?? [] as $child) {
                // A block left behind by reducing the column count folds back
                // into the last column rather than disappearing.
                $index = min((int) $child['column_index'], $count - 1);
                $columns[$index][] = $child;
            }

            $sections[$id]['columns'] = $columns;
        }

        return array_values($sections);
    }

    /** @return array<string,mixed>|null */
    public static function findBlock(int $blockId, int $pageId): ?array
    {
        $block = Database::first(
            'SELECT * FROM page_blocks WHERE id = ? AND page_id = ?',
            [$blockId, $pageId]
        );

        if ($block !== null) {
            $block['settings'] = self::decode($block['settings'] ?? null);
        }

        return $block;
    }

    /**
     * Append a block to a page (or to a column of a section).
     *
     * @param array<string,mixed> $settings
     */
    public static function addBlock(int $pageId, string $type, ?int $parentId = null, int $columnIndex = 0, array $settings = []): int
    {
        $next = (int) Database::scalar(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM page_blocks
             WHERE page_id = ? AND ' . ($parentId === null ? 'parent_id IS NULL' : 'parent_id = ?') . '
               AND column_index = ?',
            $parentId === null ? [$pageId, $columnIndex] : [$pageId, $parentId, $columnIndex],
            1
        );

        return Database::insert('page_blocks', [
            'page_id'      => $pageId,
            'parent_id'    => $parentId,
            'block_type'   => $type,
            'column_index' => $columnIndex,
            'sort_order'   => $next,
            'settings'     => json_encode($settings ?: Blocks::defaults($type), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_visible'   => 1,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    /** @param array<string,mixed> $settings */
    public static function updateBlockSettings(int $blockId, array $settings): void
    {
        Database::update('page_blocks', [
            'settings'   => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $blockId]);
    }

    /**
     * Move a block one place up or down within its own column.
     * Swapping sort orders keeps the sequence dense and avoids a full re-index.
     */
    public static function moveBlock(int $blockId, int $pageId, string $direction): void
    {
        $block = self::findBlock($blockId, $pageId);
        if ($block === null) {
            return;
        }

        $comparison = $direction === 'up' ? '<' : '>';
        $order = $direction === 'up' ? 'DESC' : 'ASC';

        $parentClause = $block['parent_id'] === null ? 'parent_id IS NULL' : 'parent_id = ?';
        $params = [$pageId];
        if ($block['parent_id'] !== null) {
            $params[] = (int) $block['parent_id'];
        }
        $params[] = (int) $block['column_index'];
        $params[] = (int) $block['sort_order'];

        $neighbour = Database::first(
            "SELECT id, sort_order FROM page_blocks
             WHERE page_id = ? AND {$parentClause} AND column_index = ? AND sort_order {$comparison} ?
             ORDER BY sort_order {$order} LIMIT 1",
            $params
        );

        if ($neighbour === null) {
            return;
        }

        Database::update('page_blocks', ['sort_order' => (int) $neighbour['sort_order']], ['id' => $blockId]);
        Database::update('page_blocks', ['sort_order' => (int) $block['sort_order']], ['id' => (int) $neighbour['id']]);
    }

    /** Move a block into a different column of the same section. */
    public static function moveBlockToColumn(int $blockId, int $pageId, int $columnIndex): void
    {
        $block = self::findBlock($blockId, $pageId);
        if ($block === null || $block['parent_id'] === null) {
            return;
        }

        $next = (int) Database::scalar(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM page_blocks WHERE parent_id = ? AND column_index = ?',
            [(int) $block['parent_id'], $columnIndex],
            1
        );

        Database::update('page_blocks', [
            'column_index' => $columnIndex,
            'sort_order'   => $next,
            'updated_at'   => date('Y-m-d H:i:s'),
        ], ['id' => $blockId]);
    }

    /** Copy a block, and a section's children with it. */
    public static function duplicateBlock(int $blockId, int $pageId): ?int
    {
        $block = self::findBlock($blockId, $pageId);
        if ($block === null) {
            return null;
        }

        $newId = self::addBlock(
            $pageId,
            (string) $block['block_type'],
            $block['parent_id'] !== null ? (int) $block['parent_id'] : null,
            (int) $block['column_index'],
            $block['settings']
        );

        if ($block['parent_id'] === null) {
            $children = Database::all('SELECT * FROM page_blocks WHERE parent_id = ? ORDER BY column_index, sort_order', [$blockId]);
            foreach ($children as $child) {
                self::addBlock(
                    $pageId,
                    (string) $child['block_type'],
                    $newId,
                    (int) $child['column_index'],
                    self::decode($child['settings'] ?? null)
                );
            }
        }

        return $newId;
    }

    public static function deleteBlock(int $blockId, int $pageId): void
    {
        // Children go with the parent via the foreign key's ON DELETE CASCADE.
        Database::run('DELETE FROM page_blocks WHERE id = ? AND page_id = ?', [$blockId, $pageId]);
    }

    public static function toggleBlock(int $blockId, int $pageId): void
    {
        Database::run(
            'UPDATE page_blocks SET is_visible = 1 - is_visible, updated_at = ? WHERE id = ? AND page_id = ?',
            [date('Y-m-d H:i:s'), $blockId, $pageId]
        );
    }

    public static function blockCount(int $pageId): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM page_blocks WHERE page_id = ?', [$pageId], 0);
    }

    // -- Helpers ------------------------------------------------------------

    /**
     * Decode a settings blob.
     *
     * @return array<string,mixed>
     */
    public static function decode(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Sanitise a submitted settings array against the block's field definition.
     * Anything not declared in the registry is dropped.
     *
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function sanitizeSettings(string $type, array $input): array
    {
        $definition = Blocks::definition($type);
        if ($definition === null) {
            return [];
        }

        $clean = [];

        foreach ($definition['fields'] as $name => $field) {
            $fieldType = (string) $field['type'];

            if ($fieldType === 'repeater') {
                $rows = $input[$name] ?? [];
                $clean[$name] = is_array($rows) ? self::sanitizeRepeater($field, $rows) : [];
                continue;
            }

            $value = $input[$name] ?? null;

            $clean[$name] = match ($fieldType) {
                'bool'     => !empty($value) ? '1' : '0',
                'richtext' => Content::sanitizeHtml((string) ($value ?? '')),
                'textarea' => $name === 'code'
                    ? Content::sanitizeHtml((string) ($value ?? ''))
                    : mb_substr(trim((string) ($value ?? '')), 0, 5000),
                'number'   => $value === '' || $value === null ? '' : (string) (int) $value,
                'select'   => self::constrainToOptions((string) ($value ?? ''), $field),
                'image'    => (string) (int) ($value ?? 0) ?: '',
                default    => mb_substr(trim((string) ($value ?? '')), 0, 1000),
            };
        }

        return $clean;
    }

    /**
     * @param array<string,mixed> $field
     * @param array<int,mixed>    $rows
     * @return array<int,array<string,string>>
     */
    private static function sanitizeRepeater(array $field, array $rows): array
    {
        $primary = null;
        foreach ($field['fields'] as $subName => $subField) {
            if (!empty($subField['primary'])) {
                $primary = $subName;
                break;
            }
        }
        $primary ??= array_key_first($field['fields']);

        $clean = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            // A row whose main field is blank is an unused spare — drop it.
            if (trim((string) ($row[$primary] ?? '')) === '') {
                continue;
            }

            $entry = [];
            foreach ($field['fields'] as $subName => $subField) {
                $value = $row[$subName] ?? '';
                $entry[$subName] = match ((string) $subField['type']) {
                    'bool'   => !empty($value) ? '1' : '0',
                    'select' => self::constrainToOptions((string) $value, $subField),
                    default  => mb_substr(trim((string) $value), 0, 2000),
                };
            }
            $clean[] = $entry;

            if (count($clean) >= 40) {
                break;
            }
        }

        return $clean;
    }

    /** @param array<string,mixed> $field */
    private static function constrainToOptions(string $value, array $field): string
    {
        $options = $field['options'] ?? [];
        if (!is_array($options) || $options === []) {
            return $value;
        }
        return array_key_exists($value, $options) ? $value : (string) ($field['default'] ?? array_key_first($options));
    }

    public static function slugify(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? $slug;
        return trim($slug, '-');
    }
}
