<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Content;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Validator;

/**
 * The CMS. Everything on the public site — copy, lists, sections, pages and
 * menus — is edited here, so the marketing site never needs a code change.
 */
final class CmsController extends Controller
{
    protected string $layout = 'admin/partials/layout';

    /**
     * The repeatable content types, each mapped to its table and editable
     * columns. Adding a type here is all it takes to expose a new list editor.
     */
    private const COLLECTIONS = [
        'services' => [
            'table'   => 'services',
            'label'   => 'Services',
            'singular' => 'service',
            'intro'   => 'The numbered tiles in the Services section. Order here is the order on the site.',
            'columns' => [
                'title'       => ['label' => 'Title', 'type' => 'text', 'max' => 160, 'required' => true],
                'description' => ['label' => 'Short description', 'type' => 'textarea', 'max' => 500],
            ],
        ],
        'sectors' => [
            'table'   => 'sectors',
            'label'   => 'Sectors',
            'singular' => 'sector',
            'intro'   => 'The pill tags in the Sectors section. "Core" tags are shown filled in dark.',
            'columns' => [
                'name'    => ['label' => 'Sector name', 'type' => 'text', 'max' => 120, 'required' => true],
                'is_core' => ['label' => 'Core sector', 'type' => 'bool'],
            ],
        ],
        'process' => [
            'table'   => 'process_steps',
            'label'   => 'Process steps',
            'singular' => 'step',
            'intro'   => 'The numbered stepper in "Our Bid Writing Process". These also drive the bid pipeline labels.',
            'columns' => [
                'title'       => ['label' => 'Step title', 'type' => 'text', 'max' => 160, 'required' => true],
                'description' => ['label' => 'Description', 'type' => 'textarea', 'max' => 500],
            ],
        ],
        'qa' => [
            'table'   => 'qa_checklist',
            'label'   => 'QA checklist',
            'singular' => 'check',
            'intro'   => 'Shown on the website and copied onto every new bid as its sign-off checklist.',
            'columns' => [
                'check_key'   => ['label' => 'Key', 'type' => 'text', 'max' => 60, 'required' => true, 'help' => 'Lowercase, no spaces. Used internally — do not change once bids exist.'],
                'title'       => ['label' => 'Check title', 'type' => 'text', 'max' => 160, 'required' => true],
                'description' => ['label' => 'Description', 'type' => 'text', 'max' => 255],
            ],
        ],
        'why' => [
            'table'   => 'why_cards',
            'label'   => 'Why choose us',
            'singular' => 'card',
            'intro'   => 'The gold-seal cards on the navy "Why Choose Excel Bids" band.',
            'columns' => [
                'seal'        => ['label' => 'Seal symbol', 'type' => 'text', 'max' => 8, 'required' => true, 'help' => 'A single character, e.g. ✓ ✎ ◈ £ ⏱'],
                'title'       => ['label' => 'Title', 'type' => 'text', 'max' => 160, 'required' => true],
                'description' => ['label' => 'Description', 'type' => 'text', 'max' => 255],
            ],
        ],
        'stats' => [
            'table'   => 'stats',
            'label'   => 'Statistics bar',
            'singular' => 'statistic',
            'intro'   => 'The four figures on the dark band under the hero. Use your verified numbers before launch.',
            'columns' => [
                'value' => ['label' => 'Figure', 'type' => 'text', 'max' => 40, 'required' => true, 'help' => 'e.g. 92%, £1.4M, 7'],
                'label' => ['label' => 'Caption', 'type' => 'text', 'max' => 190, 'required' => true],
            ],
        ],
        'portals' => [
            'table'   => 'portals',
            'label'   => 'Procurement portals',
            'singular' => 'portal',
            'intro'   => 'The circular stamps in the "Portals we work in daily" strip. Also offered when creating a bid.',
            'columns' => [
                'name'     => ['label' => 'First line', 'type' => 'text', 'max' => 60, 'required' => true],
                'line_two' => ['label' => 'Second line', 'type' => 'text', 'max' => 60, 'help' => 'Optional — the stamps are small.'],
            ],
        ],
        'faqs' => [
            'table'   => 'faqs',
            'label'   => 'FAQs',
            'singular' => 'question',
            'intro'   => 'The accordion at the foot of the home page. Also published as FAQ structured data for search engines.',
            'columns' => [
                'question' => ['label' => 'Question', 'type' => 'text', 'max' => 255, 'required' => true],
                'answer'   => ['label' => 'Answer', 'type' => 'textarea', 'max' => 2000, 'required' => true],
            ],
        ],
        'testimonials' => [
            'table'   => 'testimonials',
            'label'   => 'Testimonials',
            'singular' => 'testimonial',
            'intro'   => 'The tilted note cards in Proof of Work. Only publish quotes you have permission to use.',
            'columns' => [
                'quote'       => ['label' => 'Quote', 'type' => 'textarea', 'max' => 500, 'required' => true],
                'author_role' => ['label' => 'Job title', 'type' => 'text', 'max' => 140],
                'author_org'  => ['label' => 'Sector or organisation', 'type' => 'text', 'max' => 140],
            ],
        ],
        'case-studies' => [
            'table'   => 'case_studies',
            'label'   => 'Case studies',
            'singular' => 'case study',
            'intro'   => 'The white panel in Proof of Work. The first active one is shown on the home page.',
            'columns' => [
                'eyebrow'        => ['label' => 'Eyebrow label', 'type' => 'text', 'max' => 60],
                'title'          => ['label' => 'Headline', 'type' => 'text', 'max' => 255, 'required' => true],
                'intro'          => ['label' => 'Intro line', 'type' => 'text', 'max' => 255],
                'result_1_value' => ['label' => 'Result 1 — figure', 'type' => 'text', 'max' => 40],
                'result_1_label' => ['label' => 'Result 1 — caption', 'type' => 'text', 'max' => 190],
                'result_2_value' => ['label' => 'Result 2 — figure', 'type' => 'text', 'max' => 40],
                'result_2_label' => ['label' => 'Result 2 — caption', 'type' => 'text', 'max' => 190],
                'result_3_value' => ['label' => 'Result 3 — figure', 'type' => 'text', 'max' => 40],
                'result_3_label' => ['label' => 'Result 3 — caption', 'type' => 'text', 'max' => 190],
                'footnote'       => ['label' => 'Footnote', 'type' => 'text', 'max' => 255],
            ],
        ],
        'outcome-letters' => [
            'table'   => 'outcome_letters',
            'label'   => 'Outcome letters',
            'singular' => 'outcome letter',
            'intro'   => 'The public Outcome Letters page. Nothing appears on the website until "Client has approved publication" is ticked — upload a redacted copy, never the original.',
            // A second gate beyond "Live": the list shows why a letter is still
            // off the site, so an unapproved one is never mistaken for published.
            'gate'    => 'is_approved',
            'columns' => [
                'title'        => ['label' => 'Letter title', 'type' => 'text', 'max' => 190, 'required' => true, 'help' => 'What the letter is about, e.g. "Domiciliary care framework — award confirmation".'],
                'outcome'      => ['label' => 'Outcome', 'type' => 'text', 'max' => 60, 'help' => 'The stamp across the card, e.g. Contract awarded, Shortlisted, Framework place.'],
                'organisation' => ['label' => 'Client or buyer', 'type' => 'text', 'max' => 140, 'help' => 'Leave blank, or write "Anonymised", if you do not have permission to name them.'],
                'sector'       => ['label' => 'Sector', 'type' => 'text', 'max' => 140],
                'received_on'  => ['label' => 'Date received', 'type' => 'date'],
                'summary'      => ['label' => 'Summary', 'type' => 'textarea', 'max' => 500, 'help' => 'A sentence or two of context shown above the letter.'],
                'media_id'     => ['label' => 'Letter image', 'type' => 'media', 'help' => 'A redacted scan of the letter, as JPG, PNG or WebP. Black out names, prices and any other bidder\'s scores before exporting.'],
                'quote'        => ['label' => 'Client feedback', 'type' => 'textarea', 'max' => 600, 'help' => 'Optional — what the client said about the result.'],
                'author_role'  => ['label' => 'Feedback — job title', 'type' => 'text', 'max' => 140],
                'author_org'   => ['label' => 'Feedback — organisation', 'type' => 'text', 'max' => 140],
                'is_approved'  => ['label' => 'Client has approved publication', 'type' => 'bool', 'help' => 'Tick only once you hold written permission. Until this is ticked the letter stays off the website.'],
            ],
        ],
    ];

    /** The editable copy groups, in the order they appear on the page. */
    private const SECTIONS = [
        'header'      => 'Header & navigation',
        'hero'        => 'Hero',
        'portals'     => 'Portals strip',
        'proof_strip' => 'Statistics bar',
        'about'       => 'About (File §01)',
        'services'    => 'Services heading (File §02)',
        'sectors'     => 'Sectors (File §03)',
        'process'     => 'Process heading (File §04)',
        'qa'          => 'QA sign-off (File §05)',
        'why'         => 'Why choose us (File §06)',
        'proof'       => 'Proof of work (File §07)',
        'outcome'     => 'Outcome letters page',
        'faq'         => 'FAQ heading (File §08)',
        'cta'         => 'Closing call to action',
        'footer'      => 'Footer',
        'quote'       => 'Consultation form',
    ];

    public function index(Request $request): void
    {
        // Counts let the overview show how much content sits behind each editor.
        $counts = [];
        foreach (self::COLLECTIONS as $key => $collection) {
            $counts[$key] = (int) Database::scalar(
                "SELECT COUNT(*) FROM `{$collection['table']}` WHERE is_active = 1",
                [],
                0
            );
        }

        $this->view('admin/cms/index', [
            'pageTitle'   => 'Website content',
            'heading'     => 'Website content',
            'crumb'       => 'Configure',
            'active'      => 'cms',
            'sections'    => self::SECTIONS,
            'collections' => self::COLLECTIONS,
            'counts'      => $counts,
            'pageCount'   => (int) Database::scalar('SELECT COUNT(*) FROM pages', [], 0),
            'topActions'  => '<a href="' . e(path('/')) . '" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">View site ↗</a>',
        ]);
    }

    // -- Section visibility and order ---------------------------------------

    public function sections(Request $request): void
    {
        if ($request->isPost()) {
            $order = $request->arrayInput('order');
            $visible = $request->arrayInput('visible');

            foreach ($order as $id => $sortOrder) {
                Database::update('page_sections', [
                    'sort_order' => (int) $sortOrder,
                    'is_visible' => in_array((string) $id, array_map('strval', $visible), true) ? 1 : 0,
                ], ['id' => (int) $id]);
            }

            Activity::log('cms.sections', 'cms', null, 'Updated home page section order and visibility');
            Flash::success('Home page sections updated.');
            $this->redirect('admin/cms/sections');
        }

        $this->view('admin/cms/sections', [
            'pageTitle' => 'Home page sections',
            'heading'   => 'Home page sections',
            'crumb'     => 'Website content',
            'active'    => 'cms',
            'sections'  => Content::sections(),
        ]);
    }

    // -- Editable copy ------------------------------------------------------

    public function content(Request $request, array $params): void
    {
        $section = (string) $params['section'];
        if (!isset(self::SECTIONS[$section])) {
            $this->notFound('That content section does not exist.');
        }

        if ($request->isPost()) {
            foreach (Content::section($section) as $block) {
                $key = (string) $block['key'];
                if (!array_key_exists($key, $request->all())) {
                    continue;
                }
                Content::set($key, mb_substr((string) $request->raw($key, ''), 0, 65535));
            }

            Activity::log('cms.content', 'cms', null, 'Edited "' . self::SECTIONS[$section] . '" content');
            Flash::success(self::SECTIONS[$section] . ' updated.');
            $this->redirect('admin/cms/content/' . $section);
        }

        $this->view('admin/cms/content', [
            'pageTitle'    => self::SECTIONS[$section],
            'heading'      => self::SECTIONS[$section],
            'crumb'        => 'Website content',
            'active'       => 'cms',
            'section'      => $section,
            'sectionLabel' => self::SECTIONS[$section],
            'blocks'       => Content::section($section),
            'allSections'  => self::SECTIONS,
        ]);
    }

    // -- Repeatable lists ---------------------------------------------------

    public function collection(Request $request, array $params): void
    {
        $type = (string) $params['type'];
        $collection = self::COLLECTIONS[$type] ?? null;
        if ($collection === null) {
            $this->notFound('That content list does not exist.');
        }

        // Bulk save: order and active flags for every row at once.
        if ($request->isPost()) {
            $order = $request->arrayInput('order');
            $active = array_map('strval', $request->arrayInput('active'));

            foreach ($order as $id => $sortOrder) {
                Database::update($collection['table'], [
                    'sort_order' => (int) $sortOrder,
                    'is_active'  => in_array((string) $id, $active, true) ? 1 : 0,
                ], ['id' => (int) $id]);
            }

            Activity::log('cms.collection', 'cms', null, 'Reordered ' . $collection['label']);
            Flash::success($collection['label'] . ' updated.');
            $this->redirect('admin/cms/list/' . $type);
        }

        $items = Database::all("SELECT * FROM `{$collection['table']}` ORDER BY sort_order, id");

        $this->view('admin/cms/collection', [
            'pageTitle'   => $collection['label'],
            'heading'     => $collection['label'],
            'crumb'       => 'Website content',
            'active'      => 'cms',
            'type'        => $type,
            'collection'  => $collection,
            'items'       => $items,
            'collections' => self::COLLECTIONS,
        ]);
    }

    /** Create or update a single row in a collection. */
    public function saveCollectionItem(Request $request, array $params): void
    {
        $type = (string) $params['type'];
        $collection = self::COLLECTIONS[$type] ?? null;
        if ($collection === null) {
            $this->notFound('That content list does not exist.');
        }

        $id = $request->int('id', 0);
        $data = [];
        $rules = [];
        $labels = [];
        $uploadErrors = [];

        foreach ($collection['columns'] as $column => $definition) {
            $columnType = $definition['type'] ?? 'text';

            if ($columnType === 'bool') {
                $data[$column] = $request->boolean($column) ? 1 : 0;
                continue;
            }

            if ($columnType === 'media') {
                $data[$column] = $this->resolveMediaColumn($request, $column, $uploadErrors);
                $labels[$column] = (string) $definition['label'];
                continue;
            }

            if ($columnType === 'date') {
                $value = trim((string) $request->input($column, ''));
                $data[$column] = $value === '' ? null : $value;
                $rules[$column] = 'nullable|date';
                $labels[$column] = (string) $definition['label'];
                continue;
            }

            $value = (string) $request->raw($column, '');
            $data[$column] = mb_substr(trim($value), 0, (int) ($definition['max'] ?? 255));

            $rule = [];
            if (!empty($definition['required'])) {
                $rule[] = 'required';
            }
            $rule[] = 'max:' . (int) ($definition['max'] ?? 255);
            $rules[$column] = implode('|', $rule);
            $labels[$column] = (string) $definition['label'];
        }

        $validator = Validator::make($request->all(), $rules, $labels);

        // A rejected upload is reported alongside any other field errors rather
        // than on its own, so the editor sees everything wrong in one pass.
        foreach ($uploadErrors as $column => $message) {
            $validator->addError($column, $message);
        }

        if ($validator->fails()) {
            Flash::failValidation($validator->errors(), $request->all(), '/admin/cms/list/' . $type);
        }

        if ($id > 0) {
            Database::update($collection['table'], $data, ['id' => $id]);
            Flash::success(ucfirst($collection['singular']) . ' updated.');
        } else {
            $data['is_active'] = 1;
            $data['sort_order'] = (int) Database::scalar(
                "SELECT COALESCE(MAX(sort_order), 0) + 1 FROM `{$collection['table']}`",
                [],
                1
            );
            Database::insert($collection['table'], $data);
            Flash::success(ucfirst($collection['singular']) . ' added.');
        }

        Activity::log('cms.collection_item', 'cms', $id > 0 ? $id : null, ($id > 0 ? 'Updated' : 'Added') . ' a ' . $collection['singular']);
        $this->redirect('admin/cms/list/' . $type);
    }

    public function deleteCollectionItem(Request $request, array $params): void
    {
        $type = (string) $params['type'];
        $collection = self::COLLECTIONS[$type] ?? null;
        if ($collection === null) {
            $this->notFound('That content list does not exist.');
        }

        Database::delete($collection['table'], ['id' => (int) $params['id']]);

        Activity::log('cms.collection_item_deleted', 'cms', (int) $params['id'], 'Deleted a ' . $collection['singular']);
        Flash::success(ucfirst($collection['singular']) . ' deleted.');
        $this->redirect('admin/cms/list/' . $type);
    }

    // -- Standalone pages ---------------------------------------------------

    public function pages(Request $request): void
    {
        \App\Core\Schema::migrate();
        $this->view('admin/cms/pages', [
            'pageTitle' => 'Pages',
            'heading'   => 'Pages',
            'crumb'     => 'Website content',
            'active'    => 'cms',
            'pages'     => Database::all('SELECT * FROM pages ORDER BY sort_order, id'),
            'topActions' => '<a href="' . e(path('admin/cms/pages/create')) . '" class="btn btn-red btn-sm">+ New page</a>',
        ]);
    }

    public function createPage(Request $request): void
    {
        if (!$request->isPost()) {
            $this->view('admin/cms/page-form', [
                'pageTitle' => 'New page',
                'heading'   => 'New page',
                'crumb'     => 'Pages',
                'active'    => 'cms',
                'page'      => null,
            ]);
            return;
        }

        $data = $this->validatePage($request, '/admin/cms/pages/create');
        $data['created_at'] = date('Y-m-d H:i:s');
        $id = Database::insert('pages', $data);

        Activity::log('cms.page_created', 'page', $id, 'Created page "' . $data['title'] . '"');
        Flash::success('Page created.');
        $this->redirect('admin/cms/pages');
    }

    public function editPage(Request $request, array $params): void
    {
        $page = Database::first('SELECT * FROM pages WHERE id = ?', [(int) $params['id']]);
        if ($page === null) {
            $this->notFound('That page could not be found.');
        }
        $id = (int) $page['id'];

        if (!$request->isPost()) {
            $this->view('admin/cms/page-form', [
                'pageTitle' => 'Edit ' . $page['title'],
                'heading'   => 'Edit page',
                'crumb'     => 'Pages',
                'active'    => 'cms',
                'page'      => $page,
            ]);
            return;
        }

        $data = $this->validatePage($request, '/admin/cms/pages/' . $id . '/edit', $id);
        $data['updated_at'] = date('Y-m-d H:i:s');
        Database::update('pages', $data, ['id' => $id]);

        Activity::log('cms.page_updated', 'page', $id, 'Updated page "' . $data['title'] . '"');
        Flash::success('Page updated.');
        $this->redirect('admin/cms/pages');
    }

    public function deletePage(Request $request, array $params): void
    {
        $page = Database::first('SELECT * FROM pages WHERE id = ?', [(int) $params['id']]);
        if ($page === null) {
            $this->notFound('That page could not be found.');
        }

        Database::delete('pages', ['id' => $page['id']]);
        Activity::log('cms.page_deleted', 'page', (int) $page['id'], 'Deleted page "' . $page['title'] . '"');

        Flash::success('Page deleted.');
        $this->redirect('admin/cms/pages');
    }

    // -- Menus --------------------------------------------------------------

    public function menus(Request $request): void
    {
        $locations = [
            'primary'        => 'Main navigation',
            'footer_company' => 'Footer — Company',
            'footer_start'   => 'Footer — Get Started',
        ];

        if ($request->isPost()) {
            $action = (string) $request->input('action', 'save');

            if ($action === 'add') {
                $location = (string) $request->input('location', 'primary');
                if (!isset($locations[$location])) {
                    $location = 'primary';
                }

                $label = trim((string) $request->input('label', ''));
                $url = trim((string) $request->input('url', ''));

                if ($label === '' || $url === '') {
                    Flash::error('A menu item needs both a label and a link.');
                    $this->redirect('admin/cms/menus');
                }

                Database::insert('menu_items', [
                    'location'   => $location,
                    'label'      => mb_substr($label, 0, 120),
                    'url'        => mb_substr($url, 0, 255),
                    'is_active'  => 1,
                    'sort_order' => (int) Database::scalar(
                        'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM menu_items WHERE location = ?',
                        [$location],
                        1
                    ),
                ]);
                Flash::success('Menu item added.');
            } elseif ($action === 'delete') {
                Database::delete('menu_items', ['id' => $request->int('id')]);
                Flash::success('Menu item removed.');
            } else {
                $order = $request->arrayInput('order');
                $active = array_map('strval', $request->arrayInput('active'));
                $labels = $request->arrayInput('label');
                $urls = $request->arrayInput('url');

                foreach ($order as $id => $sortOrder) {
                    Database::update('menu_items', [
                        'sort_order' => (int) $sortOrder,
                        'is_active'  => in_array((string) $id, $active, true) ? 1 : 0,
                        'label'      => mb_substr(trim((string) ($labels[$id] ?? '')), 0, 120),
                        'url'        => mb_substr(trim((string) ($urls[$id] ?? '')), 0, 255),
                    ], ['id' => (int) $id]);
                }
                Flash::success('Menus updated.');
            }

            Activity::log('cms.menus', 'cms', null, 'Updated site navigation');
            $this->redirect('admin/cms/menus');
        }

        $items = [];
        foreach (array_keys($locations) as $location) {
            $items[$location] = Database::all(
                'SELECT * FROM menu_items WHERE location = ? ORDER BY sort_order, id',
                [$location]
            );
        }

        $this->view('admin/cms/menus', [
            'pageTitle' => 'Navigation',
            'heading'   => 'Navigation menus',
            'crumb'     => 'Website content',
            'active'    => 'cms',
            'locations' => $locations,
            'items'     => $items,
        ]);
    }

    // -- Internals ----------------------------------------------------------

    /**
     * Work out the media id for an image column.
     *
     * A new upload wins; otherwise the row keeps the image it already had,
     * unless the editor asked for it to be removed. Images go into the shared
     * media library, so the public /media/{id} route serves them with no extra
     * plumbing.
     *
     * @param array<string,string> $errors Collected by reference for the validator.
     */
    private function resolveMediaColumn(Request $request, string $column, array &$errors): ?int
    {
        $existing = $request->int($column . '_existing', 0);
        $current = $existing > 0 ? $existing : null;

        $file = $_FILES[$column] ?? null;
        $wasUploaded = is_array($file)
            && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($wasUploaded) {
            try {
                $stored = \App\Core\Uploader::storeImage($file, 'media');
            } catch (\RuntimeException $e) {
                $errors[$column] = $e->getMessage();
                return $current;
            }

            return \App\Models\Media::record($stored, (string) $request->input('title', ''));
        }

        if ($request->boolean($column . '_remove')) {
            return null;
        }

        return $current;
    }

    /** @return array<string,mixed> */
    private function validatePage(Request $request, string $redirectTo, ?int $ignoreId = null): array
    {
        $slug = (string) $request->input('slug', '');
        if ($slug === '') {
            $slug = $this->slugify((string) $request->input('title', ''));
        }
        $slug = $this->slugify($slug);

        $validator = Validator::make(
            array_merge($request->all(), ['slug' => $slug]),
            [
                'title'            => 'required|min:2|max:190',
                'slug'             => 'required|slug|max:120|unique:pages,slug' . ($ignoreId !== null ? ',' . $ignoreId : ''),
                'meta_title'       => 'nullable|max:190',
                'meta_description' => 'nullable|max:255',
            ],
            ['title' => 'Page title', 'slug' => 'URL slug', 'meta_title' => 'Meta title', 'meta_description' => 'Meta description']
        );

        // Reserved paths would be shadowed by the app's own routes.
        if (in_array($slug, ['admin', 'portal', 'install', 'consultation', 'assets', 'media', 'branding', 'outcome-letters', 'robots.txt', 'sitemap.xml'], true)) {
            $validator->addError('slug', 'That URL is reserved by the system. Please choose another.');
        }

        if ($validator->fails()) {
            Flash::failValidation($validator->errors(), $request->all(), $redirectTo);
        }

        return [
            'title'            => (string) $request->input('title'),
            'slug'             => $slug,
            'body'             => Content::sanitizeHtml((string) $request->raw('body', '')),
            'layout_mode'      => $request->input('layout_mode', 'blocks') === 'html' ? 'html' : 'blocks',
            'show_page_header' => $request->boolean('show_page_header') ? 1 : 0,
            'hero_eyebrow'     => (string) $request->input('hero_eyebrow', ''),
            'hero_intro'       => (string) $request->input('hero_intro', ''),
            'meta_title'       => (string) $request->input('meta_title', ''),
            'meta_description' => (string) $request->input('meta_description', ''),
            'is_published'     => $request->boolean('is_published') ? 1 : 0,
            'show_in_footer'   => $request->boolean('show_in_footer') ? 1 : 0,
            'sort_order'       => $request->int('sort_order', 0),
        ];
    }

    private function slugify(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? $slug;
        return trim($slug, '-');
    }
}
