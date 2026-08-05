<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Blocks;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Schema;
use App\Models\Media;
use App\Models\Page;

/**
 * The page builder.
 *
 * Deliberately server-rendered: every action is a normal form POST, so it works
 * on any host, degrades without JavaScript, and never loses a draft to a failed
 * background request. JavaScript only enhances — the WYSIWYG toolbar, repeater
 * rows and collapsing panels.
 */
final class PageBuilderController extends Controller
{
    protected string $layout = 'admin/partials/layout';

    public function index(Request $request, array $params): void
    {
        Schema::migrate();

        $page = Page::find((int) $params['id']);
        if ($page === null) {
            $this->notFound('That page could not be found.');
        }
        $pageId = (int) $page['id'];

        if (($page['layout_mode'] ?? 'blocks') !== 'blocks') {
            Flash::info('This page uses custom HTML. Switch it to the page builder in Page settings to use blocks.');
            $this->redirect('admin/cms/pages/' . $pageId . '/edit');
        }

        $this->view('admin/cms/builder', [
            'pageTitle'  => 'Editing ' . $page['title'],
            'heading'    => $page['title'],
            'crumb'      => 'Page builder',
            'active'     => 'cms',
            'page'       => $page,
            'sections'   => Page::blockTree($pageId),
            'blockTypes' => Blocks::pickerGroups(),
            'media'      => Media::all(60),
            'topActions' =>
                '<a href="' . e(path($page['slug'])) . '" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">Preview ↗</a> '
                . '<a href="' . e(path('admin/cms/pages/' . $pageId . '/edit')) . '" class="btn btn-ghost btn-sm">Page settings</a>',
        ]);
    }

    /** Add a section, or a block inside one. */
    public function addBlock(Request $request, array $params): void
    {
        $page = $this->requirePage($params);
        $pageId = (int) $page['id'];

        $type = (string) $request->input('block_type', '');
        if (!Blocks::exists($type)) {
            Flash::error('That block type does not exist.');
            $this->redirect('admin/cms/pages/' . $pageId . '/build');
        }

        $parentId = $request->int('parent_id', 0);
        $columnIndex = max(0, min(3, $request->int('column_index', 0)));

        // Only sections live at the top level; everything else needs a parent.
        if ($type === Blocks::SECTION) {
            $parentId = 0;
            $columnIndex = 0;
        } elseif ($parentId <= 0 || Page::findBlock($parentId, $pageId) === null) {
            Flash::error('Add a section first, then put blocks inside it.');
            $this->redirect('admin/cms/pages/' . $pageId . '/build');
        }

        $blockId = Page::addBlock($pageId, $type, $parentId > 0 ? $parentId : null, $columnIndex);

        Activity::log('page.block_added', 'page', $pageId, 'Added a ' . Blocks::label($type) . ' block to "' . $page['title'] . '"');
        Flash::success(Blocks::label($type) . ' added.');

        $this->redirect('admin/cms/pages/' . $pageId . '/build#block-' . $blockId);
    }

    /** Save one block's settings. */
    public function saveBlock(Request $request, array $params): void
    {
        $page = $this->requirePage($params);
        $pageId = (int) $page['id'];

        $block = Page::findBlock((int) $params['blockId'], $pageId);
        if ($block === null) {
            $this->notFound('That block could not be found.');
        }

        $type = (string) $block['block_type'];
        $submitted = $request->raw('settings', []);
        $settings = Page::sanitizeSettings($type, is_array($submitted) ? $submitted : []);

        Page::updateBlockSettings((int) $block['id'], $settings);
        $this->touch($pageId);

        Flash::success(Blocks::label($type) . ' saved.');
        $this->redirect('admin/cms/pages/' . $pageId . '/build#block-' . $block['id']);
    }

    /** Reorder, duplicate, hide or delete — all the per-block verbs. */
    public function blockAction(Request $request, array $params): void
    {
        $page = $this->requirePage($params);
        $pageId = (int) $page['id'];
        $blockId = (int) $params['blockId'];

        $block = Page::findBlock($blockId, $pageId);
        if ($block === null) {
            $this->notFound('That block could not be found.');
        }

        $action = (string) $params['action'];
        $anchor = '#block-' . $blockId;

        switch ($action) {
            case 'up':
            case 'down':
                Page::moveBlock($blockId, $pageId, $action);
                break;

            case 'column':
                Page::moveBlockToColumn($blockId, $pageId, max(0, min(3, $request->int('column_index', 0))));
                Flash::success('Block moved.');
                break;

            case 'duplicate':
                $newId = Page::duplicateBlock($blockId, $pageId);
                Flash::success(Blocks::label((string) $block['block_type']) . ' duplicated.');
                $anchor = $newId !== null ? '#block-' . $newId : '';
                break;

            case 'toggle':
                Page::toggleBlock($blockId, $pageId);
                Flash::success((int) $block['is_visible'] === 1 ? 'Block hidden from the live page.' : 'Block is live again.');
                break;

            case 'delete':
                Page::deleteBlock($blockId, $pageId);
                Activity::log('page.block_deleted', 'page', $pageId, 'Deleted a ' . Blocks::label((string) $block['block_type']) . ' block');
                Flash::success(Blocks::label((string) $block['block_type']) . ' deleted.');
                $anchor = '';
                break;

            default:
                $this->notFound('Unknown action.');
        }

        $this->touch($pageId);
        $this->redirect('admin/cms/pages/' . $pageId . '/build' . $anchor);
    }

    // -- Media library ------------------------------------------------------

    public function media(Request $request): void
    {
        Schema::migrate();

        $this->view('admin/cms/media', [
            'pageTitle' => 'Media library',
            'heading'   => 'Media library',
            'crumb'     => 'Website content',
            'active'    => 'cms',
            'media'     => Media::all(),
            'totalSize' => Media::totalBytes(),
        ]);
    }

    public function uploadMedia(Request $request): void
    {
        $file = $_FILES['image'] ?? null;
        $returnTo = (string) $request->input('return_to', '/admin/cms/media');

        if (!is_array($file)) {
            Flash::error('No image was received.');
            $this->redirect(ltrim($returnTo, '/'));
        }

        try {
            $stored = \App\Core\Uploader::storeImage($file, 'media');
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());
            $this->redirect(ltrim($returnTo, '/'));
            return;
        }

        $id = Media::record($stored, (string) $request->input('alt_text', ''));

        Activity::log('media.uploaded', 'media', $id, 'Uploaded ' . $stored['original_name']);
        Flash::success('"' . $stored['original_name'] . '" added to the media library.');

        $this->redirect(ltrim($returnTo, '/'));
    }

    public function deleteMedia(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $media = Media::find($id);

        if ($media === null) {
            $this->notFound('That image could not be found.');
        }

        // Warn rather than silently break a live page.
        $usage = Media::usage($id);
        if ($usage !== []) {
            $titles = implode(', ', array_column($usage, 'title'));
            Flash::error('That image is still used on: ' . $titles . '. Remove it from those pages first.');
            $this->redirect('admin/cms/media');
        }

        $letters = \App\Models\OutcomeLetter::usingMedia($id);
        if ($letters !== []) {
            $titles = implode(', ', array_column($letters, 'title'));
            Flash::error('That image is still used by these outcome letters: ' . $titles . '. Remove it from those first.');
            $this->redirect('admin/cms/media');
        }

        Media::remove($id);
        Activity::log('media.deleted', 'media', $id, 'Deleted ' . $media['original_name']);

        Flash::success('Image deleted.');
        $this->redirect('admin/cms/media');
    }

    // -- Internals ----------------------------------------------------------

    /** @return array<string,mixed> */
    private function requirePage(array $params): array
    {
        Schema::migrate();

        $page = Page::find((int) $params['id']);
        if ($page === null) {
            $this->notFound('That page could not be found.');
        }
        return $page;
    }

    /** Keep the page's updated_at honest so the sitemap reflects real edits. */
    private function touch(int $pageId): void
    {
        \App\Core\Database::update('pages', ['updated_at' => date('Y-m-d H:i:s')], ['id' => $pageId]);
    }
}
