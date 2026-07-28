<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Settings;

/**
 * The bid lifecycle: references, stages, statuses, QA sign-off and timeline.
 */
final class Bid
{
    /** Ordered pipeline stages, mirroring the seven-step process on the website. */
    public const STAGES = [
        'consultation'        => 'Consultation',
        'opportunity_review'  => 'Opportunity Review',
        'strategy'            => 'Strategy Session',
        'drafting'            => 'Drafting',
        'qa'                  => 'Compliance & QA',
        'submission'          => 'Submission',
        'post_submission'     => 'Post-Submission',
    ];

    public const STATUSES = [
        'draft'       => 'Draft',
        'in_progress' => 'In Progress',
        'submitted'   => 'Submitted',
        'won'         => 'Won',
        'lost'        => 'Lost',
        'withdrawn'   => 'Withdrawn',
        'no_bid'      => 'No Bid',
    ];

    /** Statuses that still consume team capacity. */
    public const OPEN_STATUSES = ['draft', 'in_progress', 'submitted'];

    /** Statuses where the outcome is known. */
    public const DECIDED_STATUSES = ['won', 'lost'];

    public const FEE_TYPES = [
        'fixed'     => 'Fixed fee',
        'day_rate'  => 'Day rate',
        'retainer'  => 'Retainer',
        'none'      => 'Not chargeable',
    ];

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::first(
            'SELECT b.*, c.organisation, c.reference AS client_reference, c.sector AS client_sector,
                    u.name AS owner_name
             FROM bids b
             JOIN clients c ON c.id = b.client_id
             LEFT JOIN users u ON u.id = b.owner_user_id
             WHERE b.id = ?',
            [$id]
        );
    }

    /** Look up a bid, restricted to one client — used by the portal. */
    public static function findForClient(int $id, int $clientId): ?array
    {
        $bid = self::find($id);
        return ($bid !== null && (int) $bid['client_id'] === $clientId) ? $bid : null;
    }

    /**
     * Generate the next reference, e.g. EB/2026/0042.
     * Uses MAX over the current year rather than a count, so deleting a bid
     * never causes a reference to be reused.
     */
    public static function nextReference(): string
    {
        $prefix = (string) Settings::get('bid_ref_prefix', 'EB');
        $year = date('Y');
        $like = $prefix . '/' . $year . '/%';

        $last = (string) Database::scalar(
            'SELECT reference FROM bids WHERE reference LIKE ? ORDER BY reference DESC LIMIT 1',
            [$like],
            ''
        );

        $next = 1;
        if ($last !== '') {
            $parts = explode('/', $last);
            $next = ((int) end($parts)) + 1;
        }

        return sprintf('%s/%s/%04d', $prefix, $year, $next);
    }

    /**
     * Create a bid together with its QA checklist and an opening timeline entry.
     *
     * @param array<string,mixed> $data
     */
    public static function create(array $data): int
    {
        return Database::transaction(static function () use ($data): int {
            $data['reference'] = $data['reference'] ?? self::nextReference();
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['created_by'] = Auth::id(Auth::STAFF);

            $id = Database::insert('bids', $data);

            self::seedQaChecklist($id);
            self::addEvent($id, 'created', 'Bid record created.', true);

            return $id;
        });
    }

    /** @param array<string,mixed> $data */
    public static function update(int $id, array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        Database::update('bids', $data, ['id' => $id]);
    }

    /** Copy the CMS-managed QA checklist onto a new bid. */
    public static function seedQaChecklist(int $bidId): void
    {
        $checks = Database::all('SELECT * FROM qa_checklist WHERE is_active = 1 ORDER BY sort_order, id');

        foreach ($checks as $check) {
            $exists = (int) Database::scalar(
                'SELECT COUNT(*) FROM bid_qa_checks WHERE bid_id = ? AND check_key = ?',
                [$bidId, $check['check_key']],
                0
            );
            if ($exists > 0) {
                continue;
            }

            Database::insert('bid_qa_checks', [
                'bid_id'     => $bidId,
                'check_key'  => $check['check_key'],
                'title'      => $check['title'],
                'sort_order' => $check['sort_order'],
            ]);
        }
    }

    /** @return array<int,array<string,mixed>> */
    public static function qaChecks(int $bidId): array
    {
        return Database::all(
            'SELECT q.*, u.name AS checked_by_name
             FROM bid_qa_checks q
             LEFT JOIN users u ON u.id = q.checked_by
             WHERE q.bid_id = ? ORDER BY q.sort_order, q.id',
            [$bidId]
        );
    }

    /** Percentage of QA checks passed, 0–100. */
    public static function qaProgress(int $bidId): int
    {
        $row = Database::first(
            'SELECT COUNT(*) AS total, SUM(is_passed) AS passed FROM bid_qa_checks WHERE bid_id = ?',
            [$bidId]
        );
        $total = (int) ($row['total'] ?? 0);
        if ($total === 0) {
            return 0;
        }
        return (int) round(((int) ($row['passed'] ?? 0) / $total) * 100);
    }

    public static function isQaComplete(int $bidId): bool
    {
        $outstanding = (int) Database::scalar(
            'SELECT COUNT(*) FROM bid_qa_checks WHERE bid_id = ? AND is_passed = 0',
            [$bidId],
            0
        );
        $total = (int) Database::scalar('SELECT COUNT(*) FROM bid_qa_checks WHERE bid_id = ?', [$bidId], 0);
        return $total > 0 && $outstanding === 0;
    }

    // -- Timeline -----------------------------------------------------------

    public static function addEvent(int $bidId, string $type, string $body, bool $visibleToClient = false, string $actorType = 'staff'): int
    {
        $staff = Auth::user(Auth::STAFF);
        $client = Auth::user(Auth::CLIENT);

        if ($actorType === 'staff' && $staff !== null) {
            $actorId = (int) $staff['id'];
            $actorName = (string) $staff['name'];
        } elseif ($actorType === 'client' && $client !== null) {
            $actorId = (int) $client['id'];
            $actorName = (string) $client['name'];
        } else {
            $actorType = 'system';
            $actorId = null;
            $actorName = 'System';
        }

        return Database::insert('bid_events', [
            'bid_id'            => $bidId,
            'event_type'        => $type,
            'body'              => $body,
            'actor_type'        => $actorType,
            'actor_id'          => $actorId ?? null,
            'actor_name'        => $actorName,
            'visible_to_client' => $visibleToClient ? 1 : 0,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function events(int $bidId, bool $clientVisibleOnly = false): array
    {
        $sql = 'SELECT * FROM bid_events WHERE bid_id = ?';
        if ($clientVisibleOnly) {
            $sql .= ' AND visible_to_client = 1';
        }
        $sql .= ' ORDER BY created_at DESC, id DESC';

        return Database::all($sql, [$bidId]);
    }

    // -- Tasks --------------------------------------------------------------

    /** @return array<int,array<string,mixed>> */
    public static function tasks(int $bidId): array
    {
        return Database::all(
            'SELECT t.*, u.name AS assignee_name
             FROM bid_tasks t LEFT JOIN users u ON u.id = t.assignee_id
             WHERE t.bid_id = ? ORDER BY t.is_done, t.sort_order, t.id',
            [$bidId]
        );
    }

    // -- Derived state ------------------------------------------------------

    /** Days until the submission deadline; negative when overdue, null when unset. */
    public static function daysUntilDue(?string $due): ?int
    {
        if (!$due || str_starts_with($due, '0000')) {
            return null;
        }
        $ts = strtotime($due);
        if (!$ts) {
            return null;
        }
        return (int) floor(($ts - time()) / 86400);
    }

    /**
     * A traffic-light label for a bid's deadline.
     *
     * @return array{level:string,label:string}
     */
    public static function deadlineState(array $bid): array
    {
        if (in_array($bid['status'], ['won', 'lost', 'withdrawn', 'no_bid'], true)) {
            return ['level' => 'neutral', 'label' => 'Closed'];
        }
        if ($bid['status'] === 'submitted') {
            return ['level' => 'ok', 'label' => 'Submitted'];
        }

        $days = self::daysUntilDue($bid['submission_due'] ?? null);
        if ($days === null) {
            return ['level' => 'neutral', 'label' => 'No deadline set'];
        }
        if ($days < 0) {
            return ['level' => 'overdue', 'label' => abs($days) . ' days overdue'];
        }
        if ($days === 0) {
            return ['level' => 'urgent', 'label' => 'Due today'];
        }
        if ($days <= 3) {
            return ['level' => 'urgent', 'label' => "Due in {$days} days"];
        }
        if ($days <= 10) {
            return ['level' => 'soon', 'label' => "Due in {$days} days"];
        }
        return ['level' => 'ok', 'label' => "Due in {$days} days"];
    }

    /** Zero-indexed position of a stage, for the progress bar. */
    public static function stageIndex(string $stage): int
    {
        $keys = array_keys(self::STAGES);
        $index = array_search($stage, $keys, true);
        return $index === false ? 0 : (int) $index;
    }

    public static function statusTone(string $status): string
    {
        return match ($status) {
            'won'                    => 'success',
            'lost'                   => 'danger',
            'submitted'              => 'info',
            'in_progress'            => 'warning',
            'withdrawn', 'no_bid'    => 'muted',
            default                  => 'neutral',
        };
    }

    // -- Aggregates ---------------------------------------------------------

    /** @return array<string,int> */
    public static function countsByStatus(?int $clientId = null): array
    {
        $sql = 'SELECT status, COUNT(*) FROM bids';
        $params = [];
        if ($clientId !== null) {
            $sql .= ' WHERE client_id = ?';
            $params[] = $clientId;
        }
        $sql .= ' GROUP BY status';

        $counts = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach (Database::pairs($sql, $params) as $status => $count) {
            $counts[$status] = (int) $count;
        }
        return $counts;
    }

    /**
     * Bids approaching their deadline, soonest first.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function upcoming(int $limit = 8, ?int $clientId = null): array
    {
        $limit = max(1, min(50, $limit));
        $placeholders = implode(',', array_fill(0, count(self::OPEN_STATUSES), '?'));
        $params = self::OPEN_STATUSES;

        $sql = "SELECT b.*, c.organisation, u.name AS owner_name
                FROM bids b
                JOIN clients c ON c.id = b.client_id
                LEFT JOIN users u ON u.id = b.owner_user_id
                WHERE b.status IN ({$placeholders}) AND b.submission_due IS NOT NULL";

        if ($clientId !== null) {
            $sql .= ' AND b.client_id = ?';
            $params[] = $clientId;
        }

        $sql .= " ORDER BY b.submission_due ASC LIMIT {$limit}";

        return Database::all($sql, $params);
    }

    /** Win rate as a percentage of decided bids. */
    public static function winRate(?string $from = null, ?string $to = null): float
    {
        [$where, $params] = self::dateWindow($from, $to);

        $row = Database::first(
            "SELECT
                SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) AS won,
                SUM(CASE WHEN status IN ('won','lost') THEN 1 ELSE 0 END) AS decided
             FROM bids {$where}",
            $params
        );

        $decided = (int) ($row['decided'] ?? 0);
        if ($decided === 0) {
            return 0.0;
        }
        return round(((int) ($row['won'] ?? 0) / $decided) * 100, 1);
    }

    /** @return array{0:string,1:array<int,mixed>} */
    public static function dateWindow(?string $from, ?string $to, string $column = 'created_at'): array
    {
        $clauses = [];
        $params = [];

        if ($from) {
            $clauses[] = "{$column} >= ?";
            $params[] = $from . ' 00:00:00';
        }
        if ($to) {
            $clauses[] = "{$column} <= ?";
            $params[] = $to . ' 23:59:59';
        }

        return [$clauses ? 'WHERE ' . implode(' AND ', $clauses) : '', $params];
    }
}
