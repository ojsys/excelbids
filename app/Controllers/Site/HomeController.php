<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\BlockRenderer;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Settings;
use App\Models\Page;

/**
 * The public marketing site. Every piece of copy comes from the CMS.
 */
final class HomeController extends Controller
{
    protected string $layout = 'site/partials/layout';

    public function index(Request $request): void
    {
        \App\Core\Schema::migrate();
        $sections = Database::all('SELECT * FROM page_sections WHERE is_visible = 1 ORDER BY sort_order, id');

        $this->view('site/home', [
            'sections' => $sections,
            'data'     => $this->contentData(),
        ]);
    }

    /** A CMS-managed page: builder blocks, or hand-written HTML. */
    public function page(Request $request, array $params): void
    {
        \App\Core\Schema::migrate();
        $page = Page::findBySlug((string) ($params['slug'] ?? ''));

        if ($page === null) {
            $this->notFound('That page could not be found.');
        }

        $blocksHtml = '';
        if (($page['layout_mode'] ?? 'html') === 'blocks') {
            $blocksHtml = BlockRenderer::render(Page::blockTree((int) $page['id'], true));
        }

        $this->view('site/page', [
            'page'            => $page,
            'blocksHtml'      => $blocksHtml,
            'pageTitle'       => ($page['meta_title'] !== '' ? $page['meta_title'] : $page['title'])
                                 . ' — ' . Settings::get('site_name', 'ExcelBids'),
            'metaDescription' => $page['meta_description'],
        ]);
    }

    public function sitemap(Request $request): void
    {
        $pages = Database::all('SELECT slug, updated_at, created_at FROM pages WHERE is_published = 1');

        $urls = [
            ['loc' => url('/'), 'priority' => '1.0', 'lastmod' => date('Y-m-d')],
            ['loc' => url('consultation'), 'priority' => '0.9', 'lastmod' => date('Y-m-d')],
        ];

        foreach ($pages as $page) {
            $urls[] = [
                'loc'      => url($page['slug']),
                'priority' => '0.7',
                'lastmod'  => date('Y-m-d', strtotime((string) ($page['updated_at'] ?: $page['created_at']))),
            ];
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            echo "  <url>\n";
            echo '    <loc>' . e($url['loc']) . "</loc>\n";
            echo '    <lastmod>' . e($url['lastmod']) . "</lastmod>\n";
            echo '    <priority>' . e($url['priority']) . "</priority>\n";
            echo "  </url>\n";
        }
        echo '</urlset>';
        exit;
    }

    public function robots(Request $request): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n";
        echo "Disallow: /admin\n";
        echo "Disallow: /portal\n";
        echo "Disallow: /install\n";
        echo "\nSitemap: " . url('sitemap.xml') . "\n";
        exit;
    }

    /**
     * Everything the home page sections need, in one pass.
     *
     * @return array<string,mixed>
     */
    private function contentData(): array
    {
        return [
            'services'     => Database::all('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order, id'),
            'sectors'      => Database::all('SELECT * FROM sectors WHERE is_active = 1 ORDER BY sort_order, id'),
            'processSteps' => Database::all('SELECT * FROM process_steps WHERE is_active = 1 ORDER BY sort_order, id'),
            'qaChecks'     => Database::all('SELECT * FROM qa_checklist WHERE is_active = 1 ORDER BY sort_order, id'),
            'whyCards'     => Database::all('SELECT * FROM why_cards WHERE is_active = 1 ORDER BY sort_order, id'),
            'stats'        => Database::all('SELECT * FROM stats WHERE is_active = 1 ORDER BY sort_order, id'),
            'portals'      => Database::all('SELECT * FROM portals WHERE is_active = 1 ORDER BY sort_order, id'),
            'faqs'         => Database::all('SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order, id'),
            'testimonials' => Database::all('SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order, id'),
            'caseStudy'    => Database::first('SELECT * FROM case_studies WHERE is_active = 1 ORDER BY sort_order, id LIMIT 1'),
        ];
    }
}
