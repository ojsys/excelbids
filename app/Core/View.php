<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Plain-PHP templating with a single level of layout inheritance.
 *
 * A view calls View::render('admin/bids/index', $data, 'admin/partials/layout');
 * the layout receives the rendered view in $content.
 */
final class View
{
    /** @var array<string,mixed> Data shared with every view. */
    private static array $shared = [];

    public static function share(string $key, $value): void
    {
        self::$shared[$key] = $value;
    }

    /** @param array<string,mixed> $data */
    public static function render(string $template, array $data = [], ?string $layout = null): void
    {
        echo self::capture($template, $data, $layout);
    }

    /** @param array<string,mixed> $data */
    public static function capture(string $template, array $data = [], ?string $layout = null): string
    {
        $content = self::renderFile($template, $data);

        if ($layout !== null) {
            $content = self::renderFile($layout, array_merge($data, ['content' => $content]));
        }

        return $content;
    }

    /** Render a partial inline, for use inside another template. */
    public static function partial(string $template, array $data = []): string
    {
        return self::renderFile($template, $data);
    }

    /** @param array<string,mixed> $data */
    private static function renderFile(string $template, array $data): string
    {
        $path = EB_APP . '/Views/' . ltrim($template, '/') . '.php';
        if (!is_file($path)) {
            throw new RuntimeException("View not found: {$template}");
        }

        $scope = array_merge(self::$shared, $data);

        $render = static function () use ($path, $scope): void {
            extract($scope, EXTR_SKIP);
            include $path;
        };

        ob_start();
        try {
            $render();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }
}
