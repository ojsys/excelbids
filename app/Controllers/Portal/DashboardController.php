<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Bid;
use App\Models\Client;
use App\Models\Document;
use App\Models\Message;

/**
 * The client's landing screen: where their bids stand and what needs them.
 */
final class DashboardController extends Controller
{
    protected string $layout = 'portal/partials/layout';

    public function index(Request $request): void
    {
        $user = $this->client();
        $clientId = (int) $user['client_id'];

        $bids = Client::bids($clientId);

        // Only the events staff have chosen to share are ever shown here.
        $recentActivity = [];
        foreach (array_slice($bids, 0, 5) as $bid) {
            foreach (array_slice(Bid::events((int) $bid['id'], true), 0, 3) as $event) {
                $event['bid_reference'] = $bid['reference'];
                $event['bid_id'] = $bid['id'];
                $event['bid_title'] = $bid['title'];
                $recentActivity[] = $event;
            }
        }
        usort($recentActivity, static fn ($a, $b) => strcmp((string) $b['created_at'], (string) $a['created_at']));

        $this->view('portal/dashboard', [
            'pageTitle' => 'My bids',
            'heading'   => 'Welcome back, ' . strtok((string) $user['name'], ' '),
            'active'    => 'dashboard',
            'stats'     => Client::stats($clientId),
            'upcoming'  => Bid::upcoming(6, $clientId),
            'bids'      => $bids,
            'unread'    => Message::unreadForClient($clientId),
            'documents' => array_slice(Document::forClient($clientId, true), 0, 5),
            'activity'  => array_slice($recentActivity, 0, 8),
        ]);
    }
}
