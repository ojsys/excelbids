<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Models\Client;
use App\Models\Enquiry;
use App\Models\User;

/**
 * The consultation request inbox — where every website enquiry lands.
 */
final class EnquiryController extends Controller
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
        ];

        if ($filters['q'] !== '') {
            $clauses[] = '(e.name LIKE ? OR e.organisation LIKE ? OR e.email LIKE ? OR e.reference LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            array_push($params, $term, $term, $term, $term);
        }
        if (isset(Enquiry::STATUSES[$filters['status']])) {
            $clauses[] = 'e.status = ?';
            $params[] = $filters['status'];
        }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';

        $select = "SELECT e.*, u.name AS assigned_name
                   FROM enquiries e LEFT JOIN users u ON u.id = e.assigned_to
                   {$where}
                   ORDER BY e.status = 'new' DESC, e.created_at DESC";

        $paginator = Paginator::make(
            $select,
            "SELECT COUNT(*) FROM enquiries e {$where}",
            $params,
            (int) $request->query('page', 1),
            self::PER_PAGE,
            $request->all()
        );

        $this->view('admin/enquiries/index', [
            'pageTitle' => 'Consultation requests',
            'heading'   => 'Consultation requests',
            'crumb'     => 'Work',
            'active'    => 'enquiries',
            'paginator' => $paginator,
            'filters'   => $filters,
        ]);
    }

    public function show(Request $request, array $params): void
    {
        $enquiry = Enquiry::find((int) $params['id']);
        if ($enquiry === null) {
            $this->notFound('That consultation request could not be found.');
        }

        // Opening a new request is the natural moment to mark it as seen.
        if ($enquiry['status'] === 'new' && Auth::can('enquiries.manage')) {
            Database::update('enquiries', [
                'status'      => 'contacted',
                'assigned_to' => $enquiry['assigned_to'] ?? Auth::id(Auth::STAFF),
                'updated_at'  => date('Y-m-d H:i:s'),
            ], ['id' => $enquiry['id']]);
            $enquiry = Enquiry::find((int) $params['id']);
        }

        $this->view('admin/enquiries/show', [
            'pageTitle' => (string) $enquiry['reference'],
            'heading'   => (string) ($enquiry['organisation'] !== '' ? $enquiry['organisation'] : $enquiry['name']),
            'crumb'     => 'Request ' . $enquiry['reference'],
            'active'    => 'enquiries',
            'enquiry'   => $enquiry,
            'staff'     => User::assignable(),
        ]);
    }

    public function updateStatus(Request $request, array $params): void
    {
        Auth::authorize('enquiries.manage');

        $enquiry = $this->requireEnquiry($params);
        $status = (string) $request->input('status', '');

        if (!isset(Enquiry::STATUSES[$status])) {
            Flash::error('That is not a valid status.');
            $this->redirect('admin/enquiries/' . $enquiry['id']);
        }

        $assigned = $request->int('assigned_to', 0);

        Database::update('enquiries', [
            'status'      => $status,
            'assigned_to' => $assigned > 0 ? $assigned : null,
            'updated_at'  => date('Y-m-d H:i:s'),
        ], ['id' => $enquiry['id']]);

        Activity::log('enquiry.status', 'enquiry', (int) $enquiry['id'], $enquiry['reference'] . ' set to ' . Enquiry::STATUSES[$status]);
        Flash::success('Request updated.');
        $this->redirect('admin/enquiries/' . $enquiry['id']);
    }

    public function updateNotes(Request $request, array $params): void
    {
        Auth::authorize('enquiries.manage');

        $enquiry = $this->requireEnquiry($params);

        Database::update('enquiries', [
            'admin_notes' => mb_substr((string) $request->raw('admin_notes', ''), 0, 10000),
            'updated_at'  => date('Y-m-d H:i:s'),
        ], ['id' => $enquiry['id']]);

        Flash::success('Notes saved.');
        $this->redirect('admin/enquiries/' . $enquiry['id']);
    }

    /** Turn a request into a client record, carrying the details across. */
    public function convert(Request $request, array $params): void
    {
        Auth::authorize('clients.manage');

        $enquiry = $this->requireEnquiry($params);

        if (!empty($enquiry['client_id'])) {
            Flash::info('This request has already been converted.');
            $this->redirect('admin/clients/' . $enquiry['client_id']);
        }

        $clientId = Client::fromEnquiry($enquiry);

        Activity::log('enquiry.converted', 'enquiry', (int) $enquiry['id'], 'Converted ' . $enquiry['reference'] . ' into a client');
        Activity::log('client.created', 'client', $clientId, 'Created from consultation request ' . $enquiry['reference']);

        Flash::success('Client created from ' . $enquiry['reference'] . '. Add their portal login and first bid next.');
        $this->redirect('admin/clients/' . $clientId);
    }

    public function destroy(Request $request, array $params): void
    {
        Auth::authorize('enquiries.manage');

        $enquiry = $this->requireEnquiry($params);
        Database::delete('enquiries', ['id' => $enquiry['id']]);

        Activity::log('enquiry.deleted', 'enquiry', (int) $enquiry['id'], 'Deleted request ' . $enquiry['reference']);
        Flash::success('Request deleted.');
        $this->redirect('admin/enquiries');
    }

    public function export(Request $request): void
    {
        Auth::authorize('reports.view');

        $rows = Database::all(
            'SELECT e.reference, e.name, e.organisation, e.email, e.phone, e.service, e.sector,
                    e.deadline, e.status, u.name AS assigned_to, e.message, e.created_at
             FROM enquiries e LEFT JOIN users u ON u.id = e.assigned_to
             ORDER BY e.created_at DESC'
        );

        Response::csv('excelbids-consultation-requests-' . date('Y-m-d') . '.csv', [
            'Reference', 'Name', 'Organisation', 'Email', 'Phone', 'Service', 'Sector',
            'Their deadline', 'Status', 'Assigned to', 'Message', 'Received',
        ], $rows);
    }

    /** @return array<string,mixed> */
    private function requireEnquiry(array $params): array
    {
        $enquiry = Enquiry::find((int) $params['id']);
        if ($enquiry === null) {
            $this->notFound('That consultation request could not be found.');
        }
        return $enquiry;
    }
}
