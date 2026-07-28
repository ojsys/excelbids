<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Bid;
use App\Models\Client;
use App\Models\Enquiry;
use App\Models\Report;
use App\Models\User;

/**
 * The operational home screen: what is due, what needs attention, what changed.
 */
final class DashboardController extends Controller
{
    protected string $layout = 'admin/partials/layout';

    public function index(Request $request): void
    {
        $user = $this->staff();
        $userId = (int) $user['id'];

        $this->view('admin/dashboard', [
            'pageTitle'   => 'Dashboard',
            'heading'     => 'Good ' . $this->partOfDay() . ', ' . strtok((string) $user['name'], ' '),
            'crumb'       => date('l j F Y'),
            'active'      => 'dashboard',
            'summary'     => Report::summary(),
            'statusCounts' => Bid::countsByStatus(),
            'upcoming'    => Bid::upcoming(8),
            'overdue'     => $this->overdueBids(),
            'myWork'      => User::workload($userId),
            'myTasks'     => $this->myTasks($userId),
            'newEnquiries' => $this->recentEnquiries(),
            'unreadMessages' => Client::unreadMessageCount(),
            'activity'    => Auth::can('reports.view') ? Activity::recent(10) : [],
            'trend'       => Report::monthlyTrend(6),
        ]);
    }

    /** The full audit log, most recent first. */
    public function activity(Request $request): void
    {
        Auth::authorize('reports.view');

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $total = (int) Database::scalar('SELECT COUNT(*) FROM activity_log', [], 0);
        $entries = Database::all(
            sprintf('SELECT * FROM activity_log ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d', $perPage, $offset)
        );

        $this->view('admin/activity', [
            'pageTitle' => 'Activity log',
            'heading'   => 'Activity log',
            'crumb'     => 'Overview',
            'active'    => 'dashboard',
            'entries'   => $entries,
            'page'      => $page,
            'lastPage'  => max(1, (int) ceil($total / $perPage)),
            'total'     => $total,
        ]);
    }

    /**
     * Open bids whose deadline has passed — the thing that most needs chasing.
     *
     * @return array<int,array<string,mixed>>
     */
    private function overdueBids(): array
    {
        return Database::all(
            "SELECT b.*, c.organisation, u.name AS owner_name
             FROM bids b
             JOIN clients c ON c.id = b.client_id
             LEFT JOIN users u ON u.id = b.owner_user_id
             WHERE b.status IN ('draft','in_progress')
               AND b.submission_due IS NOT NULL
               AND b.submission_due < NOW()
             ORDER BY b.submission_due ASC
             LIMIT 6"
        );
    }

    /** @return array<int,array<string,mixed>> */
    private function myTasks(int $userId): array
    {
        return Database::all(
            'SELECT t.*, b.reference, b.title AS bid_title, c.organisation
             FROM bid_tasks t
             JOIN bids b ON b.id = t.bid_id
             JOIN clients c ON c.id = b.client_id
             WHERE t.assignee_id = ? AND t.is_done = 0
             ORDER BY t.due_date IS NULL, t.due_date ASC, t.id
             LIMIT 8',
            [$userId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    private function recentEnquiries(): array
    {
        return Database::all(
            "SELECT * FROM enquiries WHERE status = 'new' ORDER BY created_at DESC LIMIT 5"
        );
    }

    private function partOfDay(): string
    {
        $hour = (int) date('G');
        if ($hour < 12) {
            return 'morning';
        }
        return $hour < 18 ? 'afternoon' : 'evening';
    }
}
