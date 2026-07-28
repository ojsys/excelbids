<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Core\Activity;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Core\Settings;
use App\Models\Client;
use App\Models\Message;

/**
 * The client's side of the message thread with their bid team.
 */
final class MessageController extends Controller
{
    protected string $layout = 'portal/partials/layout';

    public function index(Request $request): void
    {
        if (!Settings::bool('portal_messaging', true)) {
            Response::error(404, 'Messaging is not enabled on this portal.');
        }

        $user = $this->client();
        $clientId = (int) $user['client_id'];

        if ($request->isPost()) {
            $this->send($request, $user);
            return;
        }

        Message::markRead($clientId, 'client');

        $this->view('portal/messages', [
            'pageTitle' => 'Messages',
            'heading'   => 'Messages',
            'active'    => 'messages',
            'messages'  => Message::thread($clientId),
            'bids'      => Client::bids($clientId),
        ]);
    }

    /** @param array<string,mixed> $user */
    private function send(Request $request, array $user): void
    {
        $clientId = (int) $user['client_id'];
        $body = trim((string) $request->raw('body', ''));

        if ($body === '') {
            Flash::error('Please write a message before sending.');
            $this->redirect('portal/messages');
        }

        $bidId = $request->int('bid_id', 0);
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
            'client',
            (int) $user['id'],
            (string) $user['name'],
            mb_substr($body, 0, 10000)
        );

        Activity::log('portal.message_sent', 'client', $clientId, $user['name'] . ' sent a message');
        $this->notifyTeam($user, $body);

        Flash::success('Message sent. We will come back to you shortly.');
        $this->redirect('portal/messages');
    }

    /** Alert the account manager, falling back to the general inbox. */
    private function notifyTeam(array $user, string $body): void
    {
        $client = Database::first(
            'SELECT c.organisation, u.name AS owner_name, u.email AS owner_email
             FROM clients c LEFT JOIN users u ON u.id = c.owner_user_id
             WHERE c.id = ?',
            [(int) $user['client_id']]
        );

        $to = $client['owner_email'] ?? (Settings::get('notify_email') ?? Settings::get('contact_email'));
        if (!$to) {
            return;
        }

        Mailer::to((string) $to, (string) ($client['owner_name'] ?? ''))
            ->subject('New portal message from ' . ($client['organisation'] ?? 'a client'))
            ->replyTo((string) $user['email'], (string) $user['name'])
            ->view('message-notification', [
                'name'       => (string) ($client['owner_name'] ?? 'team'),
                'senderName' => $user['name'] . ' (' . ($client['organisation'] ?? '') . ')',
                'body'       => str_excerpt($body, 400),
                'link'       => url('admin/messages/' . (int) $user['client_id']),
            ])
            ->send();
    }
}
