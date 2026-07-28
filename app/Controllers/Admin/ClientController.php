<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Settings;
use App\Core\Validator;
use App\Models\Client;
use App\Models\Document;
use App\Models\Message;
use App\Models\User;

/**
 * Client records, their portal users, and everything attached to them.
 */
final class ClientController extends Controller
{
    protected string $layout = 'admin/partials/layout';

    private const PER_PAGE = 25;

    public function index(Request $request): void
    {
        $clauses = [];
        $params = [];

        $filters = [
            'q'      => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'owner'  => (string) $request->query('owner', ''),
        ];

        if ($filters['q'] !== '') {
            $clauses[] = '(c.organisation LIKE ? OR c.contact_name LIKE ? OR c.email LIKE ? OR c.reference LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            array_push($params, $term, $term, $term, $term);
        }
        if (isset(Client::STATUSES[$filters['status']])) {
            $clauses[] = 'c.status = ?';
            $params[] = $filters['status'];
        }
        if ($filters['owner'] !== '' && ctype_digit($filters['owner'])) {
            $clauses[] = 'c.owner_user_id = ?';
            $params[] = (int) $filters['owner'];
        }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';

        // Bid counts are aggregated in the same query to avoid an N+1 in the view.
        $select = "SELECT c.*, u.name AS owner_name,
                          COUNT(b.id) AS bid_count,
                          SUM(CASE WHEN b.status IN ('draft','in_progress','submitted') THEN 1 ELSE 0 END) AS open_bids,
                          SUM(CASE WHEN b.status = 'won' THEN b.contract_value ELSE 0 END) AS value_won
                   FROM clients c
                   LEFT JOIN users u ON u.id = c.owner_user_id
                   LEFT JOIN bids b ON b.client_id = c.id
                   {$where}
                   GROUP BY c.id, u.name
                   ORDER BY c.organisation ASC";

        $count = "SELECT COUNT(*) FROM clients c {$where}";

        $paginator = Paginator::make($select, $count, $params, (int) $request->query('page', 1), self::PER_PAGE, $request->all());

        $this->view('admin/clients/index', [
            'pageTitle'  => 'Clients',
            'heading'    => 'Clients',
            'crumb'      => 'Work',
            'active'     => 'clients',
            'paginator'  => $paginator,
            'filters'    => $filters,
            'owners'     => User::assignable(),
            'topActions' => Auth::can('clients.manage')
                ? '<a href="' . e(path('admin/clients/create')) . '" class="btn btn-red btn-sm">+ New client</a>'
                : '',
        ]);
    }

    public function show(Request $request, array $params): void
    {
        $client = Client::find((int) $params['id']);
        if ($client === null) {
            $this->notFound('That client could not be found.');
        }
        $id = (int) $client['id'];

        $this->view('admin/clients/show', [
            'pageTitle'   => (string) $client['organisation'],
            'heading'     => (string) $client['organisation'],
            'crumb'       => 'Client ' . $client['reference'],
            'active'      => 'clients',
            'client'      => $client,
            'stats'       => Client::stats($id),
            'bids'        => Client::bids($id),
            'portalUsers' => Client::portalUsers($id),
            'documents'   => Document::forClient($id),
            'unread'      => Message::unreadForClient($id),
            'activity'    => Activity::forEntity('client', $id, 12),
            'topActions'  => Auth::can('clients.manage')
                ? '<a href="' . e(path('admin/clients/' . $id . '/edit')) . '" class="btn btn-ghost btn-sm">Edit client</a>'
                . ' <a href="' . e(path('admin/bids/create')) . '" class="btn btn-red btn-sm">+ New bid</a>'
                : '',
        ]);
    }

    public function create(Request $request): void
    {
        Auth::authorize('clients.manage');

        if (!$request->isPost()) {
            $this->view('admin/clients/form', [
                'pageTitle' => 'New client',
                'heading'   => 'New client',
                'crumb'     => 'Clients',
                'active'    => 'clients',
                'client'    => null,
                'staff'     => User::assignable(),
                'sectors'   => $this->sectorOptions(),
                'reference' => Client::nextReference(),
            ]);
            return;
        }

        $data = $this->validateClient($request, '/admin/clients/create');
        $id = Client::create($data);

        Activity::log('client.created', 'client', $id, 'Created client ' . $data['organisation']);
        Flash::success('Client created. You can now add a portal login and their first bid.');
        $this->redirect('admin/clients/' . $id);
    }

    public function edit(Request $request, array $params): void
    {
        Auth::authorize('clients.manage');

        $client = Client::find((int) $params['id']);
        if ($client === null) {
            $this->notFound('That client could not be found.');
        }
        $id = (int) $client['id'];

        if (!$request->isPost()) {
            $this->view('admin/clients/form', [
                'pageTitle' => 'Edit ' . $client['organisation'],
                'heading'   => 'Edit client',
                'crumb'     => (string) $client['reference'],
                'active'    => 'clients',
                'client'    => $client,
                'staff'     => User::assignable(),
                'sectors'   => $this->sectorOptions(),
                'reference' => (string) $client['reference'],
            ]);
            return;
        }

        $data = $this->validateClient($request, '/admin/clients/' . $id . '/edit', $id);
        Client::update($id, $data);

        Activity::log('client.updated', 'client', $id, 'Updated client ' . $data['organisation']);
        Flash::success('Client updated.');
        $this->redirect('admin/clients/' . $id);
    }

    public function destroy(Request $request, array $params): void
    {
        Auth::authorize('clients.manage');

        $client = Client::find((int) $params['id']);
        if ($client === null) {
            $this->notFound('That client could not be found.');
        }

        $bidCount = (int) Database::scalar('SELECT COUNT(*) FROM bids WHERE client_id = ?', [$client['id']], 0);

        // Deleting cascades to bids and documents, so make the user archive instead
        // unless they really have nothing attached.
        if ($bidCount > 0) {
            Flash::error(
                'This client has ' . $bidCount . ' bid' . ($bidCount === 1 ? '' : 's') . ' attached. '
                . 'Set their status to Archived instead of deleting, so the bid history is kept.'
            );
            $this->redirect('admin/clients/' . $client['id']);
        }

        foreach (Document::forClient((int) $client['id']) as $document) {
            Document::remove((int) $document['id']);
        }

        Database::delete('clients', ['id' => $client['id']]);
        Activity::log('client.deleted', 'client', (int) $client['id'], 'Deleted client ' . $client['organisation']);

        Flash::success('Client deleted.');
        $this->redirect('admin/clients');
    }

    public function export(Request $request): void
    {
        Auth::authorize('reports.view');

        $rows = Database::all(
            "SELECT c.reference, c.organisation, c.contact_name, c.email, c.phone, c.sector, c.status,
                    c.city, c.postcode, u.name AS owner, c.nda_signed_on, c.created_at,
                    COUNT(b.id) AS total_bids,
                    SUM(CASE WHEN b.status = 'won' THEN 1 ELSE 0 END) AS bids_won,
                    SUM(CASE WHEN b.status = 'won' THEN b.contract_value ELSE 0 END) AS value_won
             FROM clients c
             LEFT JOIN users u ON u.id = c.owner_user_id
             LEFT JOIN bids b ON b.client_id = c.id
             GROUP BY c.id, u.name
             ORDER BY c.organisation"
        );

        Activity::log('clients.exported', 'client', null, 'Exported ' . count($rows) . ' clients to CSV');

        Response::csv('excelbids-clients-' . date('Y-m-d') . '.csv', [
            'Reference', 'Organisation', 'Contact', 'Email', 'Phone', 'Sector', 'Status',
            'City', 'Postcode', 'Account manager', 'NDA signed', 'Created',
            'Total bids', 'Bids won', 'Value won',
        ], $rows);
    }

    // -- Portal users -------------------------------------------------------

    public function addPortalUser(Request $request, array $params): void
    {
        Auth::authorize('clients.manage');

        $client = Client::find((int) $params['id']);
        if ($client === null) {
            $this->notFound('That client could not be found.');
        }
        $id = (int) $client['id'];

        $validator = Validator::make($request->all(), [
            'name'  => 'required|min:2|max:140',
            'email' => 'required|email|max:190|unique:client_users,email',
            'job_title' => 'nullable|max:120',
            'phone' => 'nullable|phone|max:40',
        ], ['name' => 'Contact name', 'email' => 'Email address', 'job_title' => 'Job title']);

        // The same address cannot be both a staff account and a portal login.
        if ((int) Database::scalar('SELECT COUNT(*) FROM users WHERE email = ?', [mb_strtolower((string) $request->input('email'))], 0) > 0) {
            $validator->addError('email', 'That address is already used by a staff account.');
        }

        if ($validator->fails()) {
            Flash::failValidation($validator->errors(), $request->all(), '/admin/clients/' . $id);
        }

        $created = Client::createPortalUser($id, [
            'name'       => (string) $request->input('name'),
            'email'      => (string) $request->input('email'),
            'job_title'  => (string) $request->input('job_title', ''),
            'phone'      => (string) $request->input('phone', ''),
            'is_primary' => $request->boolean('is_primary'),
        ]);

        $this->sendInvite($created['id'], $created['token'], $client);

        Activity::log('client.portal_user_added', 'client', $id, 'Invited ' . $request->input('email') . ' to the portal');
        Flash::success('Portal invitation sent to ' . $request->input('email') . '.');
        $this->redirect('admin/clients/' . $id . '#portal');
    }

    public function resendInvite(Request $request, array $params): void
    {
        Auth::authorize('clients.manage');

        $client = Client::find((int) $params['id']);
        $portalUser = $this->requirePortalUser($params);

        $token = random_token();
        Database::update('client_users', [
            'invite_token'   => $token,
            'invite_expires' => date('Y-m-d H:i:s', time() + (7 * 86400)),
        ], ['id' => $portalUser['id']]);

        $this->sendInvite((int) $portalUser['id'], $token, $client);

        Flash::success('A fresh invitation has been sent to ' . $portalUser['email'] . '.');
        $this->redirect('admin/clients/' . $params['id'] . '#portal');
    }

    public function togglePortalUser(Request $request, array $params): void
    {
        Auth::authorize('clients.manage');

        $portalUser = $this->requirePortalUser($params);
        $active = (int) $portalUser['is_active'] === 1 ? 0 : 1;

        Database::update('client_users', [
            'is_active'  => $active,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $portalUser['id']]);

        Activity::log(
            'client.portal_user_toggled',
            'client',
            (int) $params['id'],
            ($active ? 'Re-enabled ' : 'Suspended ') . $portalUser['email']
        );

        Flash::success($active ? 'Portal access restored.' : 'Portal access suspended.');
        $this->redirect('admin/clients/' . $params['id'] . '#portal');
    }

    public function deletePortalUser(Request $request, array $params): void
    {
        Auth::authorize('clients.manage');

        $portalUser = $this->requirePortalUser($params);
        Database::delete('client_users', ['id' => $portalUser['id']]);

        Activity::log('client.portal_user_removed', 'client', (int) $params['id'], 'Removed portal login ' . $portalUser['email']);
        Flash::success('Portal login removed.');
        $this->redirect('admin/clients/' . $params['id'] . '#portal');
    }

    // -- Internals ----------------------------------------------------------

    /** @return array<string,mixed> */
    private function requirePortalUser(array $params): array
    {
        $portalUser = Database::first(
            'SELECT * FROM client_users WHERE id = ? AND client_id = ?',
            [(int) $params['userId'], (int) $params['id']]
        );

        if ($portalUser === null) {
            $this->notFound('That portal login could not be found.');
        }

        return $portalUser;
    }

    /** @param array<string,mixed>|null $client */
    private function sendInvite(int $portalUserId, string $token, ?array $client): void
    {
        if (!Settings::bool('portal_enabled', true)) {
            Flash::warning('The client portal is currently disabled in Settings, so the invitation link will not work yet.');
        }

        $portalUser = Database::first('SELECT * FROM client_users WHERE id = ?', [$portalUserId]);
        if ($portalUser === null) {
            return;
        }

        Mailer::to((string) $portalUser['email'], (string) $portalUser['name'])
            ->subject('Your ExcelBids client portal account')
            ->view('portal-invite', [
                'user'         => $portalUser,
                'link'         => url('portal/activate/' . $token),
                'organisation' => (string) ($client['organisation'] ?? ''),
            ])
            ->send();
    }

    /** @return array<string,mixed> */
    private function validateClient(Request $request, string $redirectTo, ?int $ignoreId = null): array
    {
        $validator = Validator::make($request->all(), [
            'organisation'  => 'required|min:2|max:190',
            'contact_name'  => 'nullable|max:140',
            'email'         => 'nullable|email|max:190',
            'phone'         => 'nullable|phone|max:40',
            'website'       => 'nullable|max:190',
            'company_no'    => 'nullable|max:40',
            'sector'        => 'nullable|max:120',
            'address_line1' => 'nullable|max:190',
            'address_line2' => 'nullable|max:190',
            'city'          => 'nullable|max:120',
            'postcode'      => 'nullable|max:20',
            'country'       => 'nullable|max:80',
            'status'        => 'required|in:' . implode(',', array_keys(Client::STATUSES)),
            'nda_signed_on' => 'nullable|date',
            'notes'         => 'nullable|max:10000',
        ], [
            'organisation' => 'Organisation name',
            'contact_name' => 'Main contact',
            'email'        => 'Email address',
            'company_no'   => 'Company number',
        ]);

        if ($validator->fails()) {
            Flash::failValidation($validator->errors(), $request->all(), $redirectTo);
        }

        $ownerId = $request->int('owner_user_id', 0);
        $website = (string) $request->input('website', '');
        if ($website !== '' && !preg_match('#^https?://#i', $website)) {
            $website = 'https://' . $website;
        }

        return [
            'organisation'  => (string) $request->input('organisation'),
            'contact_name'  => (string) $request->input('contact_name', ''),
            'email'         => mb_strtolower((string) $request->input('email', '')),
            'phone'         => (string) $request->input('phone', ''),
            'website'       => $website,
            'company_no'    => (string) $request->input('company_no', ''),
            'sector'        => (string) $request->input('sector', ''),
            'address_line1' => (string) $request->input('address_line1', ''),
            'address_line2' => (string) $request->input('address_line2', ''),
            'city'          => (string) $request->input('city', ''),
            'postcode'      => strtoupper((string) $request->input('postcode', '')),
            'country'       => (string) $request->input('country', 'United Kingdom'),
            'status'        => (string) $request->input('status'),
            'nda_signed_on' => $request->nullable('nda_signed_on'),
            'notes'         => $request->nullable('notes'),
            'owner_user_id' => $ownerId > 0 ? $ownerId : null,
        ];
    }

    /** @return array<int,string> */
    private function sectorOptions(): array
    {
        return array_column(
            Database::all('SELECT name FROM sectors WHERE is_active = 1 ORDER BY sort_order, id'),
            'name'
        );
    }
}
