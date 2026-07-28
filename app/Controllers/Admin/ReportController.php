<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Report;

/**
 * Reporting. Everything is driven by a date window that persists across the
 * four report tabs and the CSV exports.
 */
final class ReportController extends Controller
{
    protected string $layout = 'admin/partials/layout';

    public function index(Request $request): void
    {
        [$from, $to, $preset] = $this->window($request);

        $this->view('admin/reports/index', [
            'pageTitle' => 'Reports',
            'heading'   => 'Reports',
            'crumb'     => 'Overview',
            'active'    => 'reports',
            'tab'       => 'overview',
            'from'      => $from,
            'to'        => $to,
            'preset'    => $preset,
            'summary'   => Report::summary($from, $to),
            'trend'     => Report::monthlyTrend(12),
            'bySector'  => Report::bySector($from, $to),
            'byPortal'  => Report::byPortal($from, $to),
        ]);
    }

    public function pipeline(Request $request): void
    {
        [$from, $to, $preset] = $this->window($request);

        $this->view('admin/reports/pipeline', [
            'pageTitle' => 'Pipeline report',
            'heading'   => 'Pipeline',
            'crumb'     => 'Reports',
            'active'    => 'reports',
            'tab'       => 'pipeline',
            'from'      => $from,
            'to'        => $to,
            'preset'    => $preset,
            'summary'   => Report::summary($from, $to),
            'deadlines' => Report::deadlineCalendar(90),
        ]);
    }

    public function clients(Request $request): void
    {
        [$from, $to, $preset] = $this->window($request);

        $this->view('admin/reports/clients', [
            'pageTitle' => 'Client report',
            'heading'   => 'Clients',
            'crumb'     => 'Reports',
            'active'    => 'reports',
            'tab'       => 'clients',
            'from'      => $from,
            'to'        => $to,
            'preset'    => $preset,
            'rows'      => Report::byClient($from, $to, 100),
        ]);
    }

    public function performance(Request $request): void
    {
        [$from, $to, $preset] = $this->window($request);

        $this->view('admin/reports/performance', [
            'pageTitle' => 'Performance report',
            'heading'   => 'Team performance & QA',
            'crumb'     => 'Reports',
            'active'    => 'reports',
            'tab'       => 'performance',
            'from'      => $from,
            'to'        => $to,
            'preset'    => $preset,
            'byOwner'   => Report::byOwner($from, $to),
            'qa'        => Report::qaPerformance(),
        ]);
    }

    public function export(Request $request, array $params): void
    {
        [$from, $to] = $this->window($request);
        $type = (string) $params['type'];
        $suffix = date('Y-m-d');

        switch ($type) {
            case 'bids':
                $rows = Report::bidExport($from, $to);
                Activity::log('reports.exported', 'report', null, 'Exported the bid report');
                Response::csv("excelbids-bid-report-{$suffix}.csv", [
                    'Reference', 'Client', 'Title', 'Buyer', 'Portal', 'Sector', 'Service', 'Stage', 'Status',
                    'Contract value', 'Fee type', 'Fee amount', 'Submission due', 'Submitted at', 'Outcome date',
                    'Score', 'Score out of', 'Owner', 'Created',
                ], $rows);
                return;

            case 'clients':
                $rows = Report::byClient($from, $to, 500);
                Activity::log('reports.exported', 'report', null, 'Exported the client report');
                Response::csv("excelbids-client-report-{$suffix}.csv", [
                    'Client ID', 'Organisation', 'Reference', 'Status', 'Total bids', 'Won', 'Lost', 'Value won', 'Fees',
                ], $rows);
                return;

            case 'sectors':
                $rows = Report::bySector($from, $to);
                Response::csv("excelbids-sector-report-{$suffix}.csv", [
                    'Sector', 'Total bids', 'Won', 'Lost', 'Value won',
                ], $rows);
                return;

            case 'performance':
                $rows = Report::byOwner($from, $to);
                Response::csv("excelbids-performance-report-{$suffix}.csv", [
                    'User ID', 'Name', 'Role', 'Total bids', 'Won', 'Lost', 'Open', 'Average score',
                ], $rows);
                return;

            default:
                $this->notFound('That report does not exist.');
        }
    }

    /**
     * Resolve the reporting window from a preset or explicit dates.
     *
     * @return array{0:?string,1:?string,2:string}
     */
    private function window(Request $request): array
    {
        $preset = (string) $request->query('preset', 'all');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        // Explicit dates always win over a preset.
        if ($from !== '' || $to !== '') {
            return [
                $from !== '' ? date('Y-m-d', (int) strtotime($from)) : null,
                $to !== '' ? date('Y-m-d', (int) strtotime($to)) : null,
                'custom',
            ];
        }

        return match ($preset) {
            'this_month'    => [date('Y-m-01'), date('Y-m-t'), $preset],
            'last_month'    => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month')), $preset],
            'quarter'       => [date('Y-m-d', strtotime('-3 months')), date('Y-m-d'), $preset],
            'year'          => [date('Y-01-01'), date('Y-12-31'), $preset],
            'last_12'       => [date('Y-m-d', strtotime('-12 months')), date('Y-m-d'), $preset],
            default         => [null, null, 'all'],
        };
    }
}
