<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Settings;
use App\Models\Client;
use App\Models\Message;

/**
 * The staff side of the two-way client messaging thread.
 */
final class MessageController extends Controller
{
    protected string $layout = 'admin/partials/layout';

    public function index(Request $request): void
    {
        $this->view('admin/messages/index', [
            'pageTitle'     => 'Client messages',
            'heading'       => 'Client messages',
            'crumb'         => 'Work',
            'active'        => 'messages',
            'conversations' => Message::conversations(),
        ]);
    }

    public function thread(Request $request, array $params): void
    {
        $client = Client::find((int) $params['clientId']);
        if ($client === null) {
            $this->notFound('That client could not be found.');
        }
        $clientId = (int) $client['id'];

        if ($request->isPost()) {
            $this->send($request, $client);
            return;
        }

        // Opening the thread marks the client's messages as read.
        Message::markRead($clientId, 'staff');

        $this->view('admin/messages/thread', [
            'pageTitle' => 'Messages — ' . $client['organisation'],
            'heading'   => (string) $client['organisation'],
            'crumb'     => 'Messages',
            'active'    => 'messages',
            'client'    => $client,
            'messages'  => Message::thread($clientId),
            'bids'      => Client::bids($clientId),
        ]);
    }

    /** @param array<string,mixed> $client */
    private function send(Request $request, array $client): void
    {
        $body = trim((string) $request->raw('body', ''));
        $clientId = (int) $client['id'];

        if ($body === '') {
            Flash::error('Please write a message before sending.');
            $this->redirect('admin/messages/' . $clientId);
        }

        $user = $this->staff();
        $bidId = $request->int('bid_id', 0);

        // Only accept a bid reference that actually belongs to this client.
        if ($bidId > 0) {
            $owns = (int) Database::scalar(
                'SELECT COUNT(*) FROM bids WHERE id = ? AND client_id = ?',
                [$bidId, $clientId],
                0
            );
            if ($owns === 0) {
                $bidId = 0;
            }
        }

        Message::send(
            $clientId,
            $bidId > 0 ? $bidId : null,
            'staff',
            (int) $user['id'],
            (string) $user['name'],
            mb_substr($body, 0, 10000)
        );

        Activity::log('message.sent', 'client', $clientId, 'Messaged ' . $client['organisation']);
        $this->notifyClient($client, (string) $user['name'], $body);

        Flash::success('Message sent.');
        $this->redirect('admin/messages/' . $clientId);
    }

    /** @param array<string,mixed> $client */
    private function notifyClient(array $client, string $senderName, string $body): void
    {
        if (!Settings::bool('portal_messaging', true) || !Settings::bool('portal_enabled', true)) {
            return;
        }

        $recipients = Database::all(
            'SELECT name, email FROM client_users WHERE client_id = ? AND is_active = 1 AND password_hash IS NOT NULL',
            [(int) $client['id']]
        );

        foreach ($recipients as $recipient) {
            Mailer::to((string) $recipient['email'], (string) $recipient['name'])
                ->subject('New message from ' . Settings::get('site_name', 'ExcelBids'))
                ->view('message-notification', [
                    'name'       => (string) $recipient['name'],
                    'senderName' => $senderName,
                    'body'       => str_excerpt($body, 400),
                    'link'       => url('portal/messages'),
                ])
                ->send();
        }
    }
}
