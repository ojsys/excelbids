<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CMS content blocks and the small markup dialect editors use inside them:
 *
 *   [c]word[/c]  → hand-drawn circle around a word (hero headline)
 *   [m]phrase[/m] → highlighter mark (the document mock-up)
 *
 * Values are escaped first, so the markers are the only HTML that can appear.
 */
final class Content
{
    /** @var array<string,string|null>|null */
    private static ?array $cache = null;

    /** @return array<string,string|null> */
    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = Database::pairs('SELECT `key`, `value` FROM content_blocks');
        }
        return self::$cache;
    }

    public static function block(string $key, string $default = ''): string
    {
        $value = self::all()[$key] ?? null;
        return ($value === null || $value === '') ? $default : (string) $value;
    }

    public static function set(string $key, ?string $value): void
    {
        Database::update('content_blocks', [
            'value'      => $value,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['key' => $key]);

        if (self::$cache !== null) {
            self::$cache[$key] = $value;
        }
    }

    /**
     * Editable blocks for one section, in display order.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function section(string $section): array
    {
        return Database::all(
            'SELECT * FROM content_blocks WHERE section = ? ORDER BY sort_order, `key`',
            [$section]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function sections(): array
    {
        return Database::all('SELECT * FROM page_sections ORDER BY sort_order, id');
    }

    /** @return array<string,bool> section_key => visible */
    public static function sectionVisibility(): array
    {
        $out = [];
        foreach (self::sections() as $row) {
            $out[(string) $row['section_key']] = (bool) $row['is_visible'];
        }
        return $out;
    }

    // -- Rendering ----------------------------------------------------------

    /** Escape, then expand the editor markers into styled spans. */
    public static function rich(string $key, string $default = ''): string
    {
        return self::expand(self::block($key, $default));
    }

    public static function expand(string $text): string
    {
        $html = eb_e($text);
        $html = preg_replace('/\[c\](.*?)\[\/c\]/su', '<span class="circled">$1</span>', $html);
        $html = preg_replace('/\[m\](.*?)\[\/m\]/su', '<span class="mark">$1</span>', $html);
        return (string) $html;
    }

    /** Escape and convert newlines to <br> — used by stamps and multi-line labels. */
    public static function lines(string $key, string $default = ''): string
    {
        $value = self::block($key, $default);
        // Seeded values use a literal backslash-n so they survive SQL import.
        $value = str_replace('\\n', "\n", $value);
        return nl2br(eb_e(trim($value)), false);
    }

    /**
     * Sanitise the HTML accepted by the page editor. Allows a conservative set
     * of formatting tags and strips everything else, including all attributes
     * other than href/title on links.
     */
    public static function sanitizeHtml(string $html): string
    {
        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><blockquote><a><hr><table><thead><tbody><tr><th><td>';
        $clean = strip_tags($html, $allowed);

        // Drop every attribute except href/title, and reject non-http(s) hrefs.
        $clean = preg_replace_callback('/<a\b([^>]*)>/i', static function (array $m): string {
            preg_match('/href\s*=\s*("|\')(.*?)\1/i', $m[1], $href);
            $url = trim($href[2] ?? '');
            if ($url === '' || preg_match('/^\s*javascript:/i', $url)) {
                return '<a>';
            }
            return '<a href="' . eb_e($url) . '" rel="noopener">';
        }, $clean) ?? $clean;

        $clean = preg_replace_callback('/<(?!a\b)([a-z0-9]+)\b[^>]*>/i', static function (array $m): string {
            return '<' . strtolower($m[1]) . '>';
        }, $clean) ?? $clean;

        return trim($clean);
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
