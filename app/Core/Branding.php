<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Brand assets — the logo, favicon and social share image.
 *
 * Files are stored under storage/uploads/branding and served through the
 * /branding route rather than written into the web root. That keeps uploads
 * working whichever way the project is deployed (public_html moved above the
 * web root, or the whole project inside it) and needs no extra writable folder.
 */
final class Branding
{
    /**
     * The editable brand settings, in the order they appear on the screen.
     * Also used to self-heal an existing installation that predates them.
     */
    public const FIELDS = [
        'logo_image' => [
            'group' => 'branding', 'type' => 'image', 'sort' => 1,
            'label' => 'Logo',
            'hint'  => 'Shown in the website header and in emails. A wide (landscape) PNG or SVG works best. Leave empty to use the ExcelBids wordmark.',
        ],
        'logo_image_dark' => [
            'group' => 'branding', 'type' => 'image', 'sort' => 2,
            'label' => 'Logo for dark backgrounds',
            'hint'  => 'Optional. The admin panel and client portal sidebars are dark navy — upload a white or light version here if your main logo would disappear against them.',
        ],
        'logo_height' => [
            'group' => 'branding', 'type' => 'number', 'sort' => 3,
            'label' => 'Logo height (pixels)', 'default' => '34',
            'hint'  => 'How tall the logo appears in the website header. The width adjusts automatically. Between 20 and 90.',
        ],
        'favicon_image' => [
            'group' => 'branding', 'type' => 'image', 'sort' => 4,
            'label' => 'Favicon',
            'hint'  => 'The small icon in the browser tab. A square PNG of at least 180×180 is ideal — it is also used as the icon when someone saves the site to a phone home screen.',
        ],
        'og_image' => [
            'group' => 'branding', 'type' => 'image', 'sort' => 5,
            'label' => 'Social sharing image',
            'hint'  => 'Shown when a link to your site is posted on LinkedIn, X or WhatsApp. Landscape, ideally 1200×630.',
        ],
    ];

    /** Make sure every brand setting exists, so the screen renders after an upgrade. */
    public static function ensureSettings(): void
    {
        foreach (self::FIELDS as $key => $definition) {
            Settings::ensure($key, $definition);
        }
    }

    /** Public URL for a stored brand file, cache-busted by its content. */
    public static function url(string $settingKey): ?string
    {
        $stored = Settings::get($settingKey);
        if ($stored === null || $stored === '') {
            return null;
        }

        $path = Uploader::path($stored);
        if (!is_file($path)) {
            return null;
        }

        // Names are already random, so the version only changes on re-upload.
        return Config::url('branding/' . rawurlencode(basename($stored))) . '?v=' . substr((string) filemtime($path), -6);
    }

    /** Absolute URL, for emails and og: tags. */
    public static function absoluteUrl(string $settingKey): ?string
    {
        $url = self::url($settingKey);
        if ($url === null) {
            return null;
        }
        return str_starts_with($url, 'http') ? $url : Config::origin() . $url;
    }

    public static function logoUrl(bool $forDarkBackground = false): ?string
    {
        // No silent fallback to the main logo on dark surfaces: a dark-ink logo
        // would simply vanish against the navy sidebar. Returning null lets the
        // wordmark render instead, which is always legible.
        if ($forDarkBackground) {
            return self::url('logo_image_dark');
        }
        return self::url('logo_image');
    }

    public static function logoHeight(): int
    {
        return max(20, min(90, Settings::int('logo_height', 34)));
    }

    /**
     * The logo, as either an uploaded image or the typographic wordmark.
     *
     * @param string $context 'site' | 'sidebar' | 'auth'
     */
    public static function logoHtml(string $context = 'site'): string
    {
        $onDark = $context === 'sidebar';
        $url = self::logoUrl($onDark);
        $siteName = (string) Settings::get('site_name', 'ExcelBids');

        if ($url !== null) {
            $height = match ($context) {
                'sidebar' => 26,
                'auth'    => 40,
                default   => self::logoHeight(),
            };
            return '<img src="' . eb_e($url) . '" alt="' . eb_e($siteName) . '" class="logo-img" style="height:' . $height . 'px">';
        }

        return self::wordmark();
    }

    /**
     * The fallback typographic logo.
     *
     * The two-tone treatment is specific to the ExcelBids name, so any other
     * site name is rendered plainly rather than split at an arbitrary point.
     */
    public static function wordmark(): string
    {
        $siteName = trim((string) Settings::get('site_name', 'ExcelBids'));

        if (strcasecmp($siteName, 'ExcelBids') === 0) {
            return 'Excel<span>Bids</span>';
        }

        return eb_e($siteName);
    }

    /** Favicon and touch-icon tags for the document head. */
    public static function faviconTags(): string
    {
        $url = self::url('favicon_image');
        if ($url === null) {
            return '';
        }

        $stored = (string) Settings::get('favicon_image', '');
        $extension = strtolower((string) pathinfo($stored, PATHINFO_EXTENSION));
        $type = match ($extension) {
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
            default => 'image/png',
        };

        return '<link rel="icon" type="' . eb_e($type) . '" href="' . eb_e($url) . '">' . "\n"
             . '<link rel="apple-touch-icon" href="' . eb_e($url) . '">';
    }

    /** Absolute image URL for og:image, falling back to the logo. */
    public static function shareImageUrl(): ?string
    {
        return self::absoluteUrl('og_image') ?? self::absoluteUrl('logo_image');
    }

    /**
     * Resolve a public /branding/{file} request back to a stored path.
     *
     * Only files currently referenced by a brand setting are served, so the
     * route cannot be used to enumerate or read anything else in storage.
     */
    public static function resolveStoredFile(string $basename): ?string
    {
        $basename = basename($basename);

        foreach (array_keys(self::FIELDS) as $key) {
            $stored = (string) Settings::get($key, '');
            if ($stored !== '' && basename($stored) === $basename) {
                $path = Uploader::path($stored);
                return is_file($path) ? $path : null;
            }
        }

        return null;
    }

    /** Delete the file behind a setting and clear it. */
    public static function remove(string $settingKey): void
    {
        $stored = (string) Settings::get($settingKey, '');
        if ($stored !== '') {
            Uploader::delete($stored);
        }
        Settings::set($settingKey, '');
    }
}
