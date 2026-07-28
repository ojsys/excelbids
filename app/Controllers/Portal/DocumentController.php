<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Core\Activity;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Settings;
use App\Core\Uploader;
use App\Models\Bid;
use App\Models\Document;
use RuntimeException;

/**
 * Client-side documents. Downloads are restricted to files staff have marked
 * visible, and every request is re-checked against the signed-in client.
 */
final class DocumentController extends Controller
{
    protected string $layout = 'portal/partials/layout';

    public function index(Request $request): void
    {
        $user = $this->client();

        $this->view('portal/documents', [
            'pageTitle' => 'Documents',
            'heading'   => 'Documents',
            'active'    => 'documents',
            'documents' => Document::forClient((int) $user['client_id'], true),
            'bids'      => \App\Models\Client::bids((int) $user['client_id']),
        ]);
    }

    public function download(Request $request, array $params): void
    {
        $user = $this->client();
        $document = Document::find((int) $params['id']);

        // Three separate conditions, all of which must hold.
        $allowed = $document !== null
            && (int) $document['client_id'] === (int) $user['client_id']
            && (int) $document['visible_to_client'] === 1;

        if (!$allowed) {
            $this->notFound('That document could not be found.');
        }

        $path = Uploader::path((string) $document['stored_name']);
        if (!is_file($path)) {
            Flash::error('That file is no longer available. Please ask us to re-send it.');
            $this->redirect('portal/documents');
        }

        Response::download($path, (string) $document['original_name'], (string) $document['mime_type']);
    }

    public function upload(Request $request, array $params): void
    {
        $user = $this->client();
        $clientId = (int) $user['client_id'];

        if (!Settings::bool('portal_uploads', true)) {
            Flash::error('Uploads are not enabled on this portal. Please email your documents to us instead.');
            $this->redirect('portal/bids/' . (int) $params['id']);
        }

        $bid = Bid::findForClient((int) $params['id'], $clientId);
        if ($bid === null) {
            $this->notFound('That bid could not be found.');
        }
        $bidId = (int) $bid['id'];

        $file = $_FILES['document'] ?? null;
        if (!is_array($file)) {
            Flash::error('No file was received.');
            $this->redirect('portal/bids/' . $bidId);
        }

        try {
            $stored = Uploader::store($file, 'documents');
        } catch (RuntimeException $e) {
            Flash::error($e->getMessage());
            $this->redirect('portal/bids/' . $bidId);
            return;
        }

        Document::record($stored, [
            'bid_id'            => $bidId,
            'client_id'         => $clientId,
            'category'          => 'evidence',
            'uploader_type'     => 'client',
            'uploader_id'       => (int) $user['id'],
            // Uploaded by the client, so of course they can see it.
            'visible_to_client' => true,
            'notes'             => mb_substr((string) $request->input('notes', ''), 0, 255),
        ]);

        // Put it on the bid timeline so the team sees it without being told.
        Bid::addEvent(
            $bidId,
            'document',
            $user['name'] . ' uploaded "' . $stored['original_name'] . '".',
            true,
            'client'
        );
        Activity::log('portal.document_uploaded', 'bid', $bidId, $user['name'] . ' uploaded ' . $stored['original_name']);

        Flash::success('"' . $stored['original_name'] . '" uploaded. Our team has been notified.');
        $this->redirect('portal/bids/' . $bidId);
    }
}
