<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Bid;
use App\Models\Client;
use App\Models\Document;

/**
 * A client's view of their own bids. Every lookup is scoped to their client id,
 * so an id in the URL can never reach another organisation's record.
 */
final class BidController extends Controller
{
    protected string $layout = 'portal/partials/layout';

    public function index(Request $request): void
    {
        $user = $this->client();
        $clientId = (int) $user['client_id'];

        $filter = (string) $request->query('status', '');
        $status = isset(Bid::STATUSES[$filter]) ? $filter : null;

        $this->view('portal/bids/index', [
            'pageTitle' => 'My bids',
            'heading'   => 'My bids',
            'active'    => 'bids',
            'bids'      => Client::bids($clientId, $status),
            'filter'    => $filter,
            'counts'    => Bid::countsByStatus($clientId),
        ]);
    }

    public function show(Request $request, array $params): void
    {
        $user = $this->client();
        $clientId = (int) $user['client_id'];

        $bid = Bid::findForClient((int) $params['id'], $clientId);
        if ($bid === null) {
            $this->notFound('That bid could not be found.');
        }
        $bidId = (int) $bid['id'];

        $this->view('portal/bids/show', [
            'pageTitle'  => (string) $bid['reference'],
            'heading'    => str_excerpt((string) $bid['title'], 70),
            'active'     => 'bids',
            'bid'        => $bid,
            'events'     => Bid::events($bidId, true),
            'documents'  => Document::forBid($bidId, true),
            'qaProgress' => Bid::qaProgress($bidId),
            'qaChecks'   => Bid::qaChecks($bidId),
        ]);
    }
}
