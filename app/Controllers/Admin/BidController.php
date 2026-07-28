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
use App\Models\Bid;
use App\Models\Client;
use App\Models\Document;
use App\Models\Report;
use App\Models\User;

/**
 * Bid lifecycle management: create, edit, move through stages, QA sign-off,
 * tasks, documents and the client-visible timeline.
 */
final class BidController extends Controller
{
    protected string $layout = 'admin/partials/layout';

    private const PER_PAGE = 25;

    /** Columns a user is allowed to sort by, mapped to SQL. */
    private const SORTABLE = [
        'deadline' => 'b.submission_due',
        'created'  => 'b.created_at',
        'value'    => 'b.contract_value',
        'title'    => 'b.title',
        'client'   => 'c.organisation',
        'status'   => 'b.status',
    ];

    // -- Listing ------------------------------------------------------------

    public function index(Request $request): void
    {
        [$where, $params, $filters] = $this->buildFilters($request);

        $sort = (string) $request->query('sort', 'deadline');
        $direction = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $column = self::SORTABLE[$sort] ?? self::SORTABLE['deadline'];

        // NULL deadlines sort last regardless of direction — they are the least urgent.
        $orderBy = $sort === 'deadline'
            ? "b.submission_due IS NULL, b.submission_due {$direction}"
            : "{$column} {$direction}";

        $select = "SELECT b.*, c.organisation, u.name AS owner_name
                   FROM bids b
                   JOIN clients c ON c.id = b.client_id
                   LEFT JOIN users u ON u.id = b.owner_user_id
                   {$where}
                   ORDER BY {$orderBy}";

        $count = "SELECT COUNT(*) FROM bids b JOIN clients c ON c.id = b.client_id {$where}";

        $paginator = Paginator::make(
            $select,
            $count,
            $params,
            (int) $request->query('page', 1),
            self::PER_PAGE,
            $request->all()
        );

        $this->view('admin/bids/index', [
            'pageTitle'  => 'Bids',
            'heading'    => 'Bids',
            'crumb'      => 'Work',
            'active'     => 'bids',
            'paginator'  => $paginator,
            'filters'    => $filters,
            'sort'       => $sort,
            'dir'        => strtolower($direction),
            'clients'    => Client::options(),
            'owners'     => User::assignable(),
            'topActions' => Auth::can('bids.manage')
                ? '<a href="' . e(path('admin/bids/create')) . '" class="btn btn-red btn-sm">+ New bid</a>'
                : '',
        ]);
    }

    /** Kanban view, grouped by pipeline stage. */
    public function board(Request $request): void
    {
        $columns = [];
        foreach (array_keys(Bid::STAGES) as $stage) {
            $columns[$stage] = Database::all(
                "SELECT b.*, c.organisation, u.name AS owner_name
                 FROM bids b
                 JOIN clients c ON c.id = b.client_id
                 LEFT JOIN users u ON u.id = b.owner_user_id
                 WHERE b.stage = ? AND b.status IN ('draft','in_progress','submitted')
                 ORDER BY b.submission_due IS NULL, b.submission_due ASC
                 LIMIT 40",
                [$stage]
            );
        }

        $this->view('admin/bids/board', [
            'pageTitle' => 'Bid board',
            'heading'   => 'Bid board',
            'crumb'     => 'Work',
            'active'    => 'bids',
            'columns'   => $columns,
        ]);
    }

    /** Deadlines grouped by week. */
    public function calendar(Request $request): void
    {
        $days = min(180, max(14, (int) $request->query('days', 60)));
        $bids = Report::deadlineCalendar($days);

        $grouped = [];
        foreach ($bids as $bid) {
            $weekStart = date('Y-m-d', strtotime('monday this week', strtotime((string) $bid['submission_due'])));
            $grouped[$weekStart][] = $bid;
        }

        $this->view('admin/bids/calendar', [
            'pageTitle' => 'Deadline calendar',
            'heading'   => 'Deadline calendar',
            'crumb'     => 'Work',
            'active'    => 'bids',
            'grouped'   => $grouped,
            'days'      => $days,
        ]);
    }

    public function export(Request $request): void
    {
        Auth::authorize('reports.view');

        $rows = Report::bidExport(
            (string) $request->query('from', '') ?: null,
            (string) $request->query('to', '') ?: null,
            ['status' => $request->query('status'), 'client_id' => $request->query('client_id')]
        );

        Activity::log('bids.exported', 'bid', null, 'Exported ' . count($rows) . ' bids to CSV');

        Response::csv('excelbids-bids-' . date('Y-m-d') . '.csv', [
            'Reference', 'Client', 'Title', 'Buyer', 'Portal', 'Sector', 'Service', 'Stage', 'Status',
            'Contract value', 'Fee type', 'Fee amount', 'Submission due', 'Submitted at', 'Outcome date',
            'Score', 'Score out of', 'Owner', 'Created',
        ], $rows);
    }

    // -- Detail -------------------------------------------------------------

    public function show(Request $request, array $params): void
    {
        $bid = Bid::find((int) $params['id']);
        if ($bid === null) {
            $this->notFound('That bid could not be found.');
        }

        $id = (int) $bid['id'];

        $this->view('admin/bids/show', [
            'pageTitle'  => $bid['reference'],
            'heading'    => str_excerpt((string) $bid['title'], 70),
            'crumb'      => 'Bid ' . $bid['reference'],
            'active'     => 'bids',
            'bid'        => $bid,
            'qaChecks'   => Bid::qaChecks($id),
            'qaProgress' => Bid::qaProgress($id),
            'tasks'      => Bid::tasks($id),
            'events'     => Bid::events($id),
            'documents'  => Document::forBid($id),
            'staff'      => User::assignable(),
            'topActions' => Auth::can('bids.manage')
                ? '<a href="' . e(path('admin/bids/' . $id . '/edit')) . '" class="btn btn-ghost btn-sm">Edit bid</a>'
                : '',
        ]);
    }

    // -- Create / edit ------------------------------------------------------

    public function create(Request $request): void
    {
        Auth::authorize('bids.manage');

        if (!$request->isPost()) {
            $this->view('admin/bids/form', [
                'pageTitle' => 'New bid',
                'heading'   => 'New bid',
                'crumb'     => 'Bids',
                'active'    => 'bids',
                'bid'       => null,
                'clients'   => Client::options(true),
                'staff'     => User::assignable(),
                'sectors'   => $this->sectorOptions(),
                'reference' => Bid::nextReference(),
            ]);
            return;
        }

        $data = $this->validateBid($request, '/admin/bids/create');
        $id = Bid::create($data);

        Activity::log('bid.created', 'bid', $id, 'Created bid ' . $data['title']);
        Flash::success('Bid created. Add the deadline detail and documents next.');
        $this->redirect('admin/bids/' . $id);
    }

    public function edit(Request $request, array $params): void
    {
        Auth::authorize('bids.manage');

        $bid = Bid::find((int) $params['id']);
        if ($bid === null) {
            $this->notFound('That bid could not be found.');
        }
        $id = (int) $bid['id'];

        if (!$request->isPost()) {
            $this->view('admin/bids/form', [
                'pageTitle' => 'Edit ' . $bid['reference'],
                'heading'   => 'Edit bid',
                'crumb'     => (string) $bid['reference'],
                'active'    => 'bids',
                'bid'       => $bid,
                'clients'   => Client::options(true),
                'staff'     => User::assignable(),
                'sectors'   => $this->sectorOptions(),
                'reference' => (string) $bid['reference'],
            ]);
            return;
        }

        $data = $this->validateBid($request, '/admin/bids/' . $id . '/edit', $id);
        $previousStatus = (string) $bid['status'];

        Bid::update($id, $data);
        $this->recordStatusChange($id, $previousStatus, (string) $data['status'], $bid);

        Activity::log('bid.updated', 'bid', $id, 'Updated bid ' . $data['title']);
        Flash::success('Bid updated.');
        $this->redirect('admin/bids/' . $id);
    }

    public function destroy(Request $request, array $params): void
    {
        Auth::authorize('bids.manage');

        $bid = Bid::find((int) $params['id']);
        if ($bid === null) {
            $this->notFound('That bid could not be found.');
        }

        // Remove the stored files as well as the rows that reference them.
        foreach (Document::forBid((int) $bid['id']) as $document) {
            Document::remove((int) $document['id']);
        }

        Database::delete('bids', ['id' => $bid['id']]);
        Activity::log('bid.deleted', 'bid', (int) $bid['id'], 'Deleted bid ' . $bid['reference'] . ' — ' . $bid['title']);

        Flash::success('Bid ' . $bid['reference'] . ' has been deleted.');
        $this->redirect('admin/bids');
    }

    // -- Quick actions from the detail page ---------------------------------

    public function updateStage(Request $request, array $params): void
    {
        Auth::authorize('bids.manage');

        $bid = $this->requireBid($params);
        $stage = (string) $request->input('stage', '');

        if (!isset(Bid::STAGES[$stage])) {
            Flash::error('That is not a valid stage.');
            $this->redirect('admin/bids/' . $bid['id']);
        }

        Bid::update((int) $bid['id'], ['stage' => $stage]);
        Bid::addEvent((int) $bid['id'], 'stage', 'Stage moved to ' . Bid::STAGES[$stage] . '.', true);
        Activity::log('bid.stage', 'bid', (int) $bid['id'], $bid['reference'] . ' moved to ' . Bid::STAGES[$stage]);

        Flash::success('Stage updated to ' . Bid::STAGES[$stage] . '.');
        $this->redirect('admin/bids/' . $bid['id']);
    }

    public function updateStatus(Request $request, array $params): void
    {
        Auth::authorize('bids.manage');

        $bid = $this->requireBid($params);
        $status = (string) $request->input('status', '');

        if (!isset(Bid::STATUSES[$status])) {
            Flash::error('That is not a valid status.');
            $this->redirect('admin/bids/' . $bid['id']);
        }

        $update = ['status' => $status];

        // Stamp the milestone dates automatically so they are never forgotten.
        if ($status === 'submitted' && empty($bid['submitted_at'])) {
            $update['submitted_at'] = date('Y-m-d H:i:s');
            $update['stage'] = 'submission';
        }
        if (in_array($status, ['won', 'lost'], true)) {
            $update['outcome_on'] = date('Y-m-d');
            $update['stage'] = 'post_submission';
        }

        Bid::update((int) $bid['id'], $update);
        $this->recordStatusChange((int) $bid['id'], (string) $bid['status'], $status, $bid);

        Flash::success('Status updated to ' . Bid::STATUSES[$status] . '.');
        $this->redirect('admin/bids/' . $bid['id']);
    }

    public function addNote(Request $request, array $params): void
    {
        Auth::authorize('bids.manage');

        $bid = $this->requireBid($params);
        $body = trim((string) $request->raw('body', ''));

        if ($body === '') {
            Flash::error('Please write something before saving the note.');
            $this->redirect('admin/bids/' . $bid['id']);
        }

        $visible = $request->boolean('visible_to_client');
        Bid::addEvent((int) $bid['id'], 'note', mb_substr($body, 0, 5000), $visible);
        Activity::log('bid.note', 'bid', (int) $bid['id'], 'Added a note to ' . $bid['reference']);

        if ($visible) {
            $this->notifyClient($bid, 'A note has been added to your bid', $body);
        }

        Flash::success('Note added' . ($visible ? ' and shared with the client.' : '.'));
        $this->redirect('admin/bids/' . $bid['id']);
    }

    public function updateQaCheck(Request $request, array $params): void
    {
        Auth::authorize('bids.manage');

        $bid = $this->requireBid($params);
        $check = Database::first(
            'SELECT * FROM bid_qa_checks WHERE id = ? AND bid_id = ?',
            [(int) $params['checkId'], (int) $bid['id']]
        );

        if ($check === null) {
            $this->notFound('That QA check could not be found.');
        }

        $passed = !((int) $check['is_passed'] === 1);

        Database::update('bid_qa_checks', [
            'is_passed'  => $passed ? 1 : 0,
            'notes'      => mb_substr((string) $request->raw('notes', (string) $check['notes']), 0, 2000),
            'checked_by' => $passed ? Auth::id(Auth::STAFF) : null,
            'checked_at' => $passed ? date('Y-m-d H:i:s') : null,
        ], ['id' => $check['id']]);

        Bid::addEvent(
            (int) $bid['id'],
            'qa',
            ($passed ? 'QA passed: ' : 'QA re-opened: ') . $check['title'],
            false
        );

        if ($passed && Bid::isQaComplete((int) $bid['id'])) {
            Bid::addEvent((int) $bid['id'], 'qa', 'All QA checks passed — cleared for submission.', true);
            Flash::success('All QA checks are now passed. This bid is cleared for submission.');
        } else {
            Flash::success($passed ? 'QA check signed off.' : 'QA check re-opened.');
        }

        $this->redirect('admin/bids/' . $bid['id'] . '#qa');
    }

    public function addTask(Request $request, array $params): void
    {
        Auth::authorize('bids.manage');

        $bid = $this->requireBid($params);
        $title = trim((string) $request->input('title', ''));

        if ($title === '') {
            Flash::error('Please give the task a title.');
            $this->redirect('admin/bids/' . $bid['id'] . '#tasks');
        }

        $assignee = $request->int('assignee_id', 0);
        $nextSort = (int) Database::scalar(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM bid_tasks WHERE bid_id = ?',
            [$bid['id']],
            1
        );

        Database::insert('bid_tasks', [
            'bid_id'      => (int) $bid['id'],
            'title'       => mb_substr($title, 0, 255),
            'assignee_id' => $assignee > 0 ? $assignee : null,
            'due_date'    => $request->nullable('due_date'),
            'sort_order'  => $nextSort,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        Flash::success('Task added.');
        $this->redirect('admin/bids/' . $bid['id'] . '#tasks');
    }

    public function toggleTask(Request $request, array $params): void
    {
        Auth::authorize('bids.manage');

        $bid = $this->requireBid($params);
        $task = Database::first(
            'SELECT * FROM bid_tasks WHERE id = ? AND bid_id = ?',
            [(int) $params['taskId'], (int) $bid['id']]
        );

        if ($task === null) {
            $this->notFound('That task could not be found.');
        }

        $done = !((int) $task['is_done'] === 1);
        Database::update('bid_tasks', [
            'is_done'      => $done ? 1 : 0,
            'completed_at' => $done ? date('Y-m-d H:i:s') : null,
        ], ['id' => $task['id']]);

        $this->redirect('admin/bids/' . $bid['id'] . '#tasks');
    }

    public function deleteTask(Request $request, array $params): void
    {
        Auth::authorize('bids.manage');

        $bid = $this->requireBid($params);
        Database::run('DELETE FROM bid_tasks WHERE id = ? AND bid_id = ?', [(int) $params['taskId'], (int) $bid['id']]);

        Flash::success('Task removed.');
        $this->redirect('admin/bids/' . $bid['id'] . '#tasks');
    }

    // -- Internals ----------------------------------------------------------

    /** @return array<string,mixed> */
    private function requireBid(array $params): array
    {
        $bid = Bid::find((int) $params['id']);
        if ($bid === null) {
            $this->notFound('That bid could not be found.');
        }
        return $bid;
    }

    /**
     * Validate and normalise the bid form.
     *
     * @return array<string,mixed>
     */
    private function validateBid(Request $request, string $redirectTo, ?int $ignoreId = null): array
    {
        $validator = Validator::make($request->all(), [
            'client_id'       => 'required|integer|exists:clients',
            'title'           => 'required|min:3|max:255',
            'buyer'           => 'nullable|max:190',
            'portal'          => 'nullable|max:120',
            'portal_ref'      => 'nullable|max:120',
            'service_type'    => 'nullable|max:120',
            'sector'          => 'nullable|max:120',
            'contract_value'  => 'nullable|numeric|min:0',
            'contract_length' => 'nullable|max:60',
            'fee_type'        => 'required|in:' . implode(',', array_keys(Bid::FEE_TYPES)),
            'fee_amount'      => 'nullable|numeric|min:0',
            'stage'           => 'required|in:' . implode(',', array_keys(Bid::STAGES)),
            'status'          => 'required|in:' . implode(',', array_keys(Bid::STATUSES)),
            'win_probability' => 'nullable|integer|between:0,100',
            'clarification_due' => 'nullable|date',
            'submission_due'  => 'nullable|date',
            'decision_expected_on' => 'nullable|date',
            'evaluation_score' => 'nullable|numeric|min:0',
            'evaluation_max'  => 'nullable|numeric|min:1',
            'summary'         => 'nullable|max:5000',
            'outcome_notes'   => 'nullable|max:5000',
        ], [
            'client_id'      => 'Client',
            'title'          => 'Bid title',
            'contract_value' => 'Contract value',
            'fee_amount'     => 'Our fee',
            'submission_due' => 'Submission deadline',
            'evaluation_score' => 'Evaluation score',
        ]);

        // A score above the maximum is a data-entry slip worth catching.
        $score = $request->nullable('evaluation_score');
        $max = $request->nullable('evaluation_max');
        if ($score !== null && $max !== null && (float) $score > (float) $max) {
            $validator->addError('evaluation_score', 'The score cannot be higher than the maximum.');
        }

        if ($validator->fails()) {
            Flash::failValidation($validator->errors(), $request->all(), $redirectTo);
        }

        $submissionDue = $request->nullable('submission_due');
        $ownerId = $request->int('owner_user_id', 0);

        return [
            'client_id'        => $request->int('client_id'),
            'title'            => (string) $request->input('title'),
            'buyer'            => (string) $request->input('buyer', ''),
            'portal'           => (string) $request->input('portal', ''),
            'portal_ref'       => (string) $request->input('portal_ref', ''),
            'service_type'     => (string) $request->input('service_type', ''),
            'sector'           => (string) $request->input('sector', ''),
            'contract_value'   => (float) $request->input('contract_value', 0),
            'contract_length'  => (string) $request->input('contract_length', ''),
            'fee_type'         => (string) $request->input('fee_type'),
            'fee_amount'       => (float) $request->input('fee_amount', 0),
            'stage'            => (string) $request->input('stage'),
            'status'           => (string) $request->input('status'),
            'win_probability'  => max(0, min(100, $request->int('win_probability', 50))),
            'clarification_due' => $request->nullable('clarification_due'),
            // A date-only input needs a time component for the DATETIME column.
            'submission_due'   => $submissionDue !== null ? $this->normaliseDateTime($submissionDue) : null,
            'decision_expected_on' => $request->nullable('decision_expected_on'),
            'evaluation_score' => $score !== null ? (float) $score : null,
            'evaluation_max'   => $max !== null ? (float) $max : 100.0,
            'summary'          => $request->nullable('summary'),
            'outcome_notes'    => $request->nullable('outcome_notes'),
            'owner_user_id'    => $ownerId > 0 ? $ownerId : null,
        ];
    }

    /** Accept both "2026-08-14" and "2026-08-14T12:00" from the form. */
    private function normaliseDateTime(string $value): string
    {
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return date('Y-m-d H:i:s');
        }
        // A bare date means end of the working day, not midnight.
        if (!str_contains($value, ':')) {
            return date('Y-m-d', $timestamp) . ' 17:00:00';
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    /** @param array<string,mixed> $bid */
    private function recordStatusChange(int $bidId, string $from, string $to, array $bid): void
    {
        if ($from === $to) {
            return;
        }

        $label = Bid::STATUSES[$to] ?? $to;
        Bid::addEvent($bidId, 'status', 'Status changed to ' . $label . '.', true);
        Activity::log('bid.status', 'bid', $bidId, $bid['reference'] . ' status changed to ' . $label);

        // Outcomes are worth an email; routine progress is not.
        if (in_array($to, ['submitted', 'won', 'lost'], true)) {
            $headline = match ($to) {
                'submitted' => 'Your bid has been submitted',
                'won'       => 'Good news — your bid was successful',
                'lost'      => 'An update on your bid',
            };
            $body = match ($to) {
                'submitted' => "We have submitted this bid and confirmed receipt with the buyer. We will let you know as soon as there is an outcome.",
                'won'       => "The buyer has confirmed this bid was successful. Congratulations — we will be in touch about next steps.",
                'lost'      => "The buyer has confirmed this bid was not successful this time. We will request the scoring feedback and go through it with you.",
            };
            $this->notifyClient(array_merge($bid, ['id' => $bidId]), $headline, $body);
        }
    }

    /**
     * Email a bid update to the client's active portal users.
     *
     * @param array<string,mixed> $bid
     */
    private function notifyClient(array $bid, string $headline, string $body): void
    {
        if (!Settings::bool('portal_enabled', true)) {
            return;
        }

        $recipients = Database::all(
            'SELECT name, email FROM client_users WHERE client_id = ? AND is_active = 1 AND password_hash IS NOT NULL',
            [(int) $bid['client_id']]
        );

        foreach ($recipients as $recipient) {
            Mailer::to((string) $recipient['email'], (string) $recipient['name'])
                ->subject($headline . ' — ' . $bid['reference'])
                ->view('bid-update', [
                    'bid'      => $bid,
                    'name'     => (string) $recipient['name'],
                    'headline' => $headline,
                    'body'     => $body,
                ])
                ->send();
        }
    }

    /**
     * Build the WHERE clause for the bid list from the request filters.
     *
     * @return array{0:string,1:array<int,mixed>,2:array<string,mixed>}
     */
    private function buildFilters(Request $request): array
    {
        $clauses = [];
        $params = [];

        $filters = [
            'q'         => trim((string) $request->query('q', '')),
            'status'    => (string) $request->query('status', ''),
            'stage'     => (string) $request->query('stage', ''),
            'client_id' => (string) $request->query('client_id', ''),
            'owner'     => (string) $request->query('owner', ''),
            'due'       => (string) $request->query('due', ''),
        ];

        if ($filters['q'] !== '') {
            $clauses[] = '(b.title LIKE ? OR b.reference LIKE ? OR b.buyer LIKE ? OR c.organisation LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            array_push($params, $term, $term, $term, $term);
        }

        if ($filters['status'] === 'open') {
            $clauses[] = "b.status IN ('draft','in_progress','submitted')";
        } elseif (isset(Bid::STATUSES[$filters['status']])) {
            $clauses[] = 'b.status = ?';
            $params[] = $filters['status'];
        }

        if (isset(Bid::STAGES[$filters['stage']])) {
            $clauses[] = 'b.stage = ?';
            $params[] = $filters['stage'];
        }

        if ($filters['client_id'] !== '' && ctype_digit($filters['client_id'])) {
            $clauses[] = 'b.client_id = ?';
            $params[] = (int) $filters['client_id'];
        }

        if ($filters['owner'] !== '' && ctype_digit($filters['owner'])) {
            $clauses[] = 'b.owner_user_id = ?';
            $params[] = (int) $filters['owner'];
        }

        if ($filters['due'] === 'overdue') {
            $clauses[] = "b.submission_due < NOW() AND b.status IN ('draft','in_progress')";
        } elseif ($filters['due'] === 'week') {
            $clauses[] = 'b.submission_due BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)';
        } elseif ($filters['due'] === 'month') {
            $clauses[] = 'b.submission_due BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)';
        }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';

        return [$where, $params, $filters];
    }

    /** @return array<int,string> */
    private function sectorOptions(): array
    {
        $sectors = Database::all('SELECT name FROM sectors WHERE is_active = 1 ORDER BY sort_order, id');
        return array_column($sectors, 'name');
    }
}
