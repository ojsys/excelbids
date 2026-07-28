<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Uploader;
use App\Models\Bid;
use App\Models\Document;
use RuntimeException;

/**
 * Staff-side document handling. Files live outside the document root and are
 * only ever released through the download action below.
 */
final class DocumentController extends Controller
{
    public function upload(Request $request, array $params): void
    {
        Auth::authorize('documents.manage');

        $bid = Bid::find((int) $params['id']);
        if ($bid === null) {
            $this->notFound('That bid could not be found.');
        }

        $file = $_FILES['document'] ?? null;
        if (!is_array($file)) {
            Flash::error('No file was received.');
            $this->redirect('admin/bids/' . $bid['id'] . '#documents');
        }

        try {
            $stored = Uploader::store($file, 'documents');
        } catch (RuntimeException $e) {
            Flash::error($e->getMessage());
            $this->redirect('admin/bids/' . $bid['id'] . '#documents');
            return;
        }

        $category = (string) $request->input('category', 'general');
        if (!isset(Document::CATEGORIES[$category])) {
            $category = 'general';
        }

        $visible = $request->boolean('visible_to_client');

        $documentId = Document::record($stored, [
            'bid_id'            => (int) $bid['id'],
            'client_id'         => (int) $bid['client_id'],
            'category'          => $category,
            'uploader_type'     => 'staff',
            'uploader_id'       => Auth::id(Auth::STAFF),
            'visible_to_client' => $visible,
        ]);

        Bid::addEvent(
            (int) $bid['id'],
            'document',
            'Document added: ' . $stored['original_name'],
            $visible
        );
        Activity::log('document.uploaded', 'bid', (int) $bid['id'], 'Uploaded ' . $stored['original_name'] . ' to ' . $bid['reference']);

        Flash::success('"' . $stored['original_name'] . '" uploaded' . ($visible ? ' and shared with the client.' : '.'));
        $this->redirect('admin/bids/' . $bid['id'] . '#documents');
    }

    public function download(Request $request, array $params): void
    {
        $document = Document::find((int) $params['id']);
        if ($document === null) {
            $this->notFound('That document could not be found.');
        }

        $path = Uploader::path((string) $document['stored_name']);
        if (!is_file($path)) {
            Flash::error('That file is missing from storage. It may have been removed on the server.');
            $this->back('/admin/bids');
        }

        Activity::log('document.downloaded', 'document', (int) $document['id'], 'Downloaded ' . $document['original_name']);

        Response::download($path, (string) $document['original_name'], (string) $document['mime_type']);
    }

    public function toggleVisibility(Request $request, array $params): void
    {
        Auth::authorize('documents.manage');

        $document = Document::find((int) $params['id']);
        if ($document === null) {
            $this->notFound('That document could not be found.');
        }

        $visible = (int) $document['visible_to_client'] === 1 ? 0 : 1;
        Database::update('documents', ['visible_to_client' => $visible], ['id' => $document['id']]);

        if (!empty($document['bid_id'])) {
            Bid::addEvent(
                (int) $document['bid_id'],
                'document',
                ($visible ? 'Shared with client: ' : 'Unshared: ') . $document['original_name'],
                (bool) $visible
            );
        }

        Flash::success($visible ? 'That document is now visible in the client portal.' : 'That document is no longer visible to the client.');
        $this->back('/admin/bids');
    }

    public function destroy(Request $request, array $params): void
    {
        Auth::authorize('documents.manage');

        $document = Document::find((int) $params['id']);
        if ($document === null) {
            $this->notFound('That document could not be found.');
        }

        $bidId = $document['bid_id'] !== null ? (int) $document['bid_id'] : null;
        $name = (string) $document['original_name'];

        Document::remove((int) $document['id']);

        if ($bidId !== null) {
            Bid::addEvent($bidId, 'document', 'Document removed: ' . $name, false);
        }
        Activity::log('document.deleted', 'document', (int) $params['id'], 'Deleted ' . $name);

        Flash::success('"' . $name . '" deleted.');

        if ($bidId !== null) {
            $this->redirect('admin/bids/' . $bidId . '#documents');
        }
        $this->back('/admin/clients');
    }
}
