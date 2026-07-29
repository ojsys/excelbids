<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Blocks;

/**
 * Turns a page's block tree into HTML.
 *
 * Each block type has a template in Views/site/blocks. A missing template is
 * skipped rather than fatal, so a half-finished block never takes a page down.
 */
final class BlockRenderer
{
    /** @param array<int,array<string,mixed>> $sections */
    public static function render(array $sections): string
    {
        $html = '';

        foreach ($sections as $section) {
            $html .= self::renderSection($section);
        }

        return $html;
    }

    /** @param array<string,mixed> $section */
    private static function renderSection(array $section): string
    {
        $settings = $section['settings'] ?? [];
        $columns = $section['columns'] ?? [];

        // Render each column's children first, so the template only lays out.
        $rendered = [];
        foreach ($columns as $index => $blocks) {
            $inner = '';
            foreach ($blocks as $block) {
                $inner .= self::renderBlock($block);
            }
            $rendered[$index] = $inner;
        }

        return View::partial('site/blocks/section', [
            'settings' => $settings,
            'columns'  => $rendered,
            'block'    => $section,
        ]);
    }

    /** @param array<string,mixed> $block */
    public static function renderBlock(array $block): string
    {
        $type = (string) $block['block_type'];

        if (!Blocks::exists($type)) {
            return '';
        }

        $template = 'site/blocks/' . $type;
        if (!is_file(EB_APP . '/Views/' . $template . '.php')) {
            return '';
        }

        try {
            return View::partial($template, [
                'settings' => $block['settings'] ?? [],
                'block'    => $block,
            ]);
        } catch (\Throwable $e) {
            error_log('[blocks] ' . $type . ' failed to render: ' . $e->getMessage());
            return Config::isDebug()
                ? '<!-- block ' . eb_e($type) . ' failed: ' . eb_e($e->getMessage()) . ' -->'
                : '';
        }
    }

    // -- Helpers used by the templates --------------------------------------

    /** Read a setting with a fallback. */
    public static function get(array $settings, string $key, string $default = ''): string
    {
        $value = $settings[$key] ?? '';
        return ($value === '' || $value === null) ? $default : (string) $value;
    }

    public static function bool(array $settings, string $key, bool $default = false): bool
    {
        if (!array_key_exists($key, $settings) || $settings[$key] === '') {
            return $default;
        }
        return (string) $settings[$key] === '1';
    }

    /**
     * Repeater rows for a field.
     *
     * @return array<int,array<string,string>>
     */
    public static function rows(array $settings, string $key): array
    {
        $rows = $settings[$key] ?? [];
        return is_array($rows) ? $rows : [];
    }

    /** Map a spacing option to its CSS class suffix. */
    public static function spacing(string $value): string
    {
        return match ($value) {
            'none'  => '0',
            'small' => 's',
            'large' => 'l',
            default => 'm',
        };
    }

    /**
     * Turn a pasted YouTube, Vimeo or Google Maps link into an embeddable URL.
     * Anything else is rejected — this is the only place the site emits an iframe.
     */
    public static function embedUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        // YouTube
        if ($host === 'youtu.be') {
            $id = trim((string) parse_url($url, PHP_URL_PATH), '/');
            return self::youtube($id);
        }
        if ($host === 'youtube.com' || $host === 'm.youtube.com') {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            if (!empty($query['v'])) {
                return self::youtube((string) $query['v']);
            }
            if (preg_match('#/embed/([A-Za-z0-9_-]{6,20})#', $url, $m)) {
                return self::youtube($m[1]);
            }
        }

        // Vimeo
        if ($host === 'vimeo.com' || $host === 'player.vimeo.com') {
            if (preg_match('#(\d{6,})#', $url, $m)) {
                return 'https://player.vimeo.com/video/' . $m[1];
            }
        }

        // Google Maps — only the embed form, which needs no API key.
        if ($host === 'google.com' || $host === 'maps.google.com' || str_ends_with($host, '.google.com')) {
            if (str_contains($url, '/maps/embed')) {
                return $url;
            }
            $query = (string) parse_url($url, PHP_URL_QUERY);
            parse_str($query, $parts);
            $place = $parts['q'] ?? null;
            if ($place !== null) {
                return 'https://maps.google.com/maps?q=' . rawurlencode((string) $place) . '&output=embed';
            }
        }

        return null;
    }

    private static function youtube(string $id): ?string
    {
        $id = preg_replace('/[^A-Za-z0-9_-]/', '', $id) ?? '';
        return $id === '' ? null : 'https://www.youtube-nocookie.com/embed/' . $id;
    }

    /** Resolve a link that may be internal ("/about") or external. */
    public static function link(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '#';
        }
        if (preg_match('#^(https?://|mailto:|tel:|#)#i', $url)) {
            return $url;
        }
        return path($url);
    }
}
