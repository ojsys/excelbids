<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Reporting queries. Everything here is read-only aggregation over bids,
 * clients and enquiries within a date window.
 */
final class Report
{
    /**
     * Headline KPIs for the dashboard and the reports overview.
     *
     * @return array<string,mixed>
     */
    public static function summary(?string $from = null, ?string $to = null): array
    {
        [$where, $params] = self::window($from, $to);

        $bids = Database::first(
            "SELECT
                COUNT(*)                                                              AS total_bids,
                SUM(CASE WHEN status IN ('draft','in_progress','submitted') THEN 1 ELSE 0 END) AS open_bids,
                SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END)                 AS submitted,
                SUM(CASE WHEN status = 'won'  THEN 1 ELSE 0 END)                      AS won,
                SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END)                      AS lost,
                SUM(CASE WHEN status = 'won'  THEN contract_value ELSE 0 END)         AS value_won,
                SUM(CASE WHEN status IN ('draft','in_progress','submitted') THEN contract_value ELSE 0 END) AS pipeline_value,
                SUM(CASE WHEN status = 'won' THEN fee_amount ELSE 0 END)              AS fees_won,
                AVG(CASE WHEN evaluation_score IS NOT NULL THEN evaluation_score END) AS avg_score
             FROM bids {$where}",
            $params
        ) ?? [];

        $decided = (int) ($bids['won'] ?? 0) + (int) ($bids['lost'] ?? 0);

        [$clientWhere, $clientParams] = self::window($from, $to);
        $newClients = (int) Database::scalar("SELECT COUNT(*) FROM clients {$clientWhere}", $clientParams, 0);

        [$enquiryWhere, $enquiryParams] = self::window($from, $to);
        $enquiries = Database::first(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) AS converted
             FROM enquiries {$enquiryWhere}",
            $enquiryParams
        ) ?? [];

        $totalEnquiries = (int) ($enquiries['total'] ?? 0);

        return [
            'total_bids'      => (int) ($bids['total_bids'] ?? 0),
            'open_bids'       => (int) ($bids['open_bids'] ?? 0),
            'submitted'       => (int) ($bids['submitted'] ?? 0),
            'won'             => (int) ($bids['won'] ?? 0),
            'lost'            => (int) ($bids['lost'] ?? 0),
            'decided'         => $decided,
            'win_rate'        => $decided > 0 ? round(((int) ($bids['won'] ?? 0) / $decided) * 100, 1) : 0.0,
            'value_won'       => (float) ($bids['value_won'] ?? 0),
            'pipeline_value'  => (float) ($bids['pipeline_value'] ?? 0),
            'fees_won'        => (float) ($bids['fees_won'] ?? 0),
            'avg_score'       => $bids['avg_score'] !== null ? round((float) $bids['avg_score'], 1) : null,
            'new_clients'     => $newClients,
            'enquiries'       => $totalEnquiries,
            'enquiries_converted' => (int) ($enquiries['converted'] ?? 0),
            'conversion_rate' => $totalEnquiries > 0
                ? round(((int) ($enquiries['converted'] ?? 0) / $totalEnquiries) * 100, 1)
                : 0.0,
        ];
    }

    /**
     * Bid volume and outcome by month, for the trend chart.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function monthlyTrend(int $months = 12): array
    {
        $months = max(1, min(36, $months));
        $since = date('Y-m-01 00:00:00', strtotime("-" . ($months - 1) . " months"));

        $rows = Database::all(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS period,
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'won'  THEN 1 ELSE 0 END) AS won,
                    SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) AS lost,
                    SUM(CASE WHEN status = 'won'  THEN contract_value ELSE 0 END) AS value_won
             FROM bids
             WHERE created_at >= ?
             GROUP BY period
             ORDER BY period",
            [$since]
        );

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(string) $row['period']] = $row;
        }

        // Fill gaps so the chart has one column per month.
        $series = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $key = date('Y-m', strtotime("-{$i} months"));
            $row = $indexed[$key] ?? ['total' => 0, 'won' => 0, 'lost' => 0, 'value_won' => 0];
            $series[] = [
                'period'    => $key,
                'label'     => date('M y', strtotime($key . '-01')),
                'total'     => (int) $row['total'],
                'won'       => (int) $row['won'],
                'lost'      => (int) $row['lost'],
                'value_won' => (float) $row['value_won'],
            ];
        }

        return $series;
    }

    /** @return array<int,array<string,mixed>> */
    public static function byClient(?string $from = null, ?string $to = null, int $limit = 25): array
    {
        [$where, $params] = self::window($from, $to, 'b.created_at');
        $limit = max(1, min(200, $limit));

        return Database::all(
            "SELECT c.id, c.organisation, c.reference, c.status,
                    COUNT(b.id) AS total_bids,
                    SUM(CASE WHEN b.status = 'won'  THEN 1 ELSE 0 END) AS won,
                    SUM(CASE WHEN b.status = 'lost' THEN 1 ELSE 0 END) AS lost,
                    SUM(CASE WHEN b.status = 'won'  THEN b.contract_value ELSE 0 END) AS value_won,
                    SUM(b.fee_amount) AS fees
             FROM clients c
             JOIN bids b ON b.client_id = c.id
             {$where}
             GROUP BY c.id, c.organisation, c.reference, c.status
             ORDER BY total_bids DESC, value_won DESC
             LIMIT {$limit}",
            $params
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function bySector(?string $from = null, ?string $to = null): array
    {
        [$where, $params] = self::window($from, $to);
        $condition = $where === '' ? "WHERE sector <> ''" : $where . " AND sector <> ''";

        return Database::all(
            "SELECT sector,
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'won'  THEN 1 ELSE 0 END) AS won,
                    SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) AS lost,
                    SUM(CASE WHEN status = 'won'  THEN contract_value ELSE 0 END) AS value_won
             FROM bids
             {$condition}
             GROUP BY sector
             ORDER BY total DESC",
            $params
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function byOwner(?string $from = null, ?string $to = null): array
    {
        [$where, $params] = self::window($from, $to, 'b.created_at');

        return Database::all(
            "SELECT u.id, u.name, u.role,
                    COUNT(b.id) AS total,
                    SUM(CASE WHEN b.status = 'won'  THEN 1 ELSE 0 END) AS won,
                    SUM(CASE WHEN b.status = 'lost' THEN 1 ELSE 0 END) AS lost,
                    SUM(CASE WHEN b.status IN ('draft','in_progress','submitted') THEN 1 ELSE 0 END) AS open_bids,
                    AVG(CASE WHEN b.evaluation_score IS NOT NULL THEN b.evaluation_score END) AS avg_score
             FROM users u
             JOIN bids b ON b.owner_user_id = u.id
             {$where}
             GROUP BY u.id, u.name, u.role
             ORDER BY total DESC",
            $params
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function byPortal(?string $from = null, ?string $to = null): array
    {
        [$where, $params] = self::window($from, $to);
        $condition = $where === '' ? "WHERE portal <> ''" : $where . " AND portal <> ''";

        return Database::all(
            "SELECT portal,
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) AS won,
                    SUM(CASE WHEN status = 'won' THEN contract_value ELSE 0 END) AS value_won
             FROM bids {$condition}
             GROUP BY portal
             ORDER BY total DESC",
            $params
        );
    }

    /**
     * Average QA pass rate across bids that have reached QA.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function qaPerformance(): array
    {
        return Database::all(
            "SELECT q.title,
                    COUNT(*) AS assessed,
                    SUM(q.is_passed) AS passed,
                    ROUND(AVG(q.is_passed) * 100, 1) AS pass_rate
             FROM bid_qa_checks q
             JOIN bids b ON b.id = q.bid_id
             WHERE b.stage IN ('qa','submission','post_submission') OR b.status IN ('submitted','won','lost')
             GROUP BY q.check_key, q.title
             ORDER BY q.title"
        );
    }

    /**
     * Every bid in the window, flattened for CSV export.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function bidExport(?string $from = null, ?string $to = null, array $filters = []): array
    {
        [$where, $params] = self::window($from, $to, 'b.created_at');
        $clauses = $where === '' ? [] : [substr($where, 6)]; // strip leading "WHERE "

        if (!empty($filters['status'])) {
            $clauses[] = 'b.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['client_id'])) {
            $clauses[] = 'b.client_id = ?';
            $params[] = (int) $filters['client_id'];
        }

        $sql = "SELECT b.reference, c.organisation AS client, b.title, b.buyer, b.portal, b.sector,
                       b.service_type, b.stage, b.status, b.contract_value, b.fee_type, b.fee_amount,
                       b.submission_due, b.submitted_at, b.outcome_on, b.evaluation_score, b.evaluation_max,
                       u.name AS owner, b.created_at
                FROM bids b
                JOIN clients c ON c.id = b.client_id
                LEFT JOIN users u ON u.id = b.owner_user_id";

        if ($clauses) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }
        $sql .= ' ORDER BY b.created_at DESC';

        return Database::all($sql, $params);
    }

    /**
     * Deadlines in the next N days, for the calendar view.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function deadlineCalendar(int $days = 60): array
    {
        $days = max(1, min(365, $days));
        return Database::all(
            "SELECT b.id, b.reference, b.title, b.submission_due, b.status, b.stage,
                    c.organisation, u.name AS owner_name
             FROM bids b
             JOIN clients c ON c.id = b.client_id
             LEFT JOIN users u ON u.id = b.owner_user_id
             WHERE b.submission_due IS NOT NULL
               AND b.status IN ('draft','in_progress','submitted')
               AND b.submission_due <= DATE_ADD(NOW(), INTERVAL {$days} DAY)
             ORDER BY b.submission_due ASC"
        );
    }

    /** @return array{0:string,1:array<int,mixed>} */
    private static function window(?string $from, ?string $to, string $column = 'created_at'): array
    {
        $clauses = [];
        $params = [];

        if ($from !== null && $from !== '') {
            $clauses[] = "{$column} >= ?";
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== null && $to !== '') {
            $clauses[] = "{$column} <= ?";
            $params[] = $to . ' 23:59:59';
        }

        return [$clauses ? 'WHERE ' . implode(' AND ', $clauses) : '', $params];
    }
}
