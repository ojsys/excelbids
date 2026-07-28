<?php
/**
 * @var array<string,mixed>            $summary
 * @var array<string,int>              $statusCounts
 * @var array<int,array<string,mixed>> $upcoming
 * @var array<int,array<string,mixed>> $overdue
 * @var array<string,int>              $myWork
 * @var array<int,array<string,mixed>> $myTasks
 * @var array<int,array<string,mixed>> $newEnquiries
 * @var int                            $unreadMessages
 * @var array<int,array<string,mixed>> $activity
 * @var array<int,array<string,mixed>> $trend
 */

use App\Core\Auth;
use App\Core\View;
use App\Models\Bid;

$maxTrend = max(1, max(array_column($trend, 'total')));

// Overdue bids also satisfy the "upcoming" query, so merge on id to list each once.
$deadlineRows = $overdue;
$seen = array_column($overdue, 'id');
foreach ($upcoming as $bid) {
    if (!in_array($bid['id'], $seen, false)) {
        $deadlineRows[] = $bid;
    }
}
?>

<!-- Headline numbers -->
<div class="kpi-grid">
  <div class="kpi is-red">
    <div class="label">Open bids</div>
    <div class="value"><?= (int) $summary['open_bids'] ?></div>
    <div class="meta"><?= (int) $summary['submitted'] ?> awaiting a decision</div>
  </div>
  <div class="kpi is-navy">
    <div class="label">Pipeline value</div>
    <div class="value"><?= e(money($summary['pipeline_value'])) ?></div>
    <div class="meta">Across all live bids</div>
  </div>
  <div class="kpi is-green">
    <div class="label">Win rate</div>
    <div class="value"><?= e((string) $summary['win_rate']) ?><small>%</small></div>
    <div class="meta"><?= (int) $summary['won'] ?> won of <?= (int) $summary['decided'] ?> decided</div>
  </div>
  <div class="kpi">
    <div class="label">Value won</div>
    <div class="value"><?= e(money($summary['value_won'])) ?></div>
    <div class="meta"><?= $summary['avg_score'] !== null ? 'Avg. score ' . e((string) $summary['avg_score']) : 'No scores recorded yet' ?></div>
  </div>
</div>

<?php if ($overdue): ?>
  <div class="alert alert-error">
    <strong><?= count($overdue) ?> open <?= count($overdue) === 1 ? 'bid has' : 'bids have' ?> passed their submission deadline.</strong>
    Review them below and either submit, withdraw or update the deadline.
  </div>
<?php endif; ?>

<div class="grid grid-main">

  <div>
    <!-- Deadlines -->
    <section class="card">
      <div class="card-head">
        <div>
          <h2>Next deadlines</h2>
          <div class="sub">Open bids, soonest first</div>
        </div>
        <div class="head-actions">
          <a href="<?= e(path('admin/bids/calendar')) ?>" class="btn btn-ghost btn-sm">Calendar</a>
          <a href="<?= e(path('admin/bids')) ?>" class="btn btn-ghost btn-sm">All bids</a>
        </div>
      </div>

      <?php if ($deadlineRows): ?>
        <div class="table-wrap">
          <table class="data">
            <thead>
              <tr>
                <th>Bid</th><th>Client</th><th>Stage</th><th>Status</th><th>Deadline</th><th class="num">Value</th><th>Owner</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($deadlineRows as $bid): ?>
                <?= View::partial('admin/partials/bid-row', ['bid' => $bid, 'showClient' => true]) ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty">
          <span class="mark">▤</span>
          <h3>No live bids with a deadline</h3>
          <p>Once you add a bid and set its submission deadline, it will appear here.</p>
          <?php if (Auth::can('bids.manage')): ?>
            <a href="<?= e(path('admin/bids/create')) ?>" class="btn btn-red btn-sm">Add a bid</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- Trend -->
    <section class="card">
      <div class="card-head">
        <div>
          <h2>Bids over the last six months</h2>
          <div class="sub">Created, and how they were decided</div>
        </div>
        <?php if (Auth::can('reports.view')): ?>
          <div class="head-actions"><a href="<?= e(path('admin/reports')) ?>" class="btn btn-ghost btn-sm">Full reports</a></div>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <div class="bars">
          <?php foreach ($trend as $month): ?>
            <?php
              $other = max(0, (int) $month['total'] - (int) $month['won'] - (int) $month['lost']);
              $scale = static fn (int $n): string => $n === 0 ? '0' : max(3, (int) round(($n / $maxTrend) * 130)) . 'px';
            ?>
            <div class="bar-col">
              <span class="bar-value"><?= (int) $month['total'] ?></span>
              <div class="bar-stack">
                <?php if ($other > 0): ?><div class="bar" style="height:<?= e($scale($other)) ?>"></div><?php endif; ?>
                <?php if ((int) $month['won'] > 0): ?><div class="bar won" style="height:<?= e($scale((int) $month['won'])) ?>"></div><?php endif; ?>
                <?php if ((int) $month['lost'] > 0): ?><div class="bar lost" style="height:<?= e($scale((int) $month['lost'])) ?>"></div><?php endif; ?>
              </div>
              <span class="bar-label"><?= e((string) $month['label']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="legend">
          <span><i style="background:var(--navy)"></i> In progress or submitted</span>
          <span><i style="background:var(--green)"></i> Won</span>
          <span><i style="background:var(--red)"></i> Lost</span>
        </div>
      </div>
    </section>
  </div>

  <div>
    <!-- My work -->
    <section class="card">
      <div class="card-head"><h3>My workload</h3></div>
      <div class="card-body tight">
        <dl class="dl">
          <dt>Open bids I own</dt><dd><strong><?= (int) $myWork['open_bids'] ?></strong></dd>
          <dt>Due within 7 days</dt><dd><strong><?= (int) $myWork['due_this_week'] ?></strong></dd>
          <dt>Open tasks</dt><dd><strong><?= (int) $myWork['open_tasks'] ?></strong></dd>
        </dl>
      </div>

      <?php if ($myTasks): ?>
        <div class="card-body" style="border-top:1px solid var(--line-soft);">
          <?php foreach ($myTasks as $task): ?>
            <div class="u-between" style="padding:7px 0;border-bottom:1px solid var(--line-soft);">
              <div style="min-width:0;">
                <div class="u-small" style="font-weight:600;"><?= e(str_excerpt((string) $task['title'], 46)) ?></div>
                <a class="ref" href="<?= e(path('admin/bids/' . $task['bid_id'])) ?>"><?= e((string) $task['reference']) ?></a>
              </div>
              <?php if (!empty($task['due_date'])): ?>
                <?php $days = Bid::daysUntilDue((string) $task['due_date']); ?>
                <span class="deadline <?= $days !== null && $days < 0 ? 'overdue' : ($days !== null && $days <= 3 ? 'urgent' : 'ok') ?>">
                  <?= e(fdate((string) $task['due_date'], 'j M')) ?>
                </span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- Needs attention -->
    <section class="card">
      <div class="card-head"><h3>Needs attention</h3></div>
      <div class="card-body tight">
        <?php if (!$newEnquiries && $unreadMessages === 0): ?>
          <p class="u-small u-muted u-mb0">Nothing waiting. The inbox is clear.</p>
        <?php endif; ?>

        <?php if ($unreadMessages > 0 && Auth::can('messages.manage')): ?>
          <a href="<?= e(path('admin/messages')) ?>" class="u-between" style="padding:9px 0;border-bottom:1px solid var(--line-soft);">
            <span class="u-small"><strong><?= (int) $unreadMessages ?></strong> unread client <?= $unreadMessages === 1 ? 'message' : 'messages' ?></span>
            <span class="badge badge-danger">Reply</span>
          </a>
        <?php endif; ?>

        <?php foreach ($newEnquiries as $enquiry): ?>
          <a href="<?= e(path('admin/enquiries/' . $enquiry['id'])) ?>" class="u-between" style="padding:9px 0;border-bottom:1px solid var(--line-soft);">
            <span style="min-width:0;">
              <span class="u-small" style="font-weight:600;display:block;">
                <?= e(str_excerpt((string) ($enquiry['organisation'] !== '' ? $enquiry['organisation'] : $enquiry['name']), 30)) ?>
              </span>
              <span class="ref"><?= e((string) $enquiry['reference']) ?> · <?= e(relative_days((string) $enquiry['created_at'])) ?></span>
            </span>
            <span class="badge badge-warning">New</span>
          </a>
        <?php endforeach; ?>
      </div>
      <?php if ($newEnquiries && Auth::can('enquiries.view')): ?>
        <div class="card-foot"><a href="<?= e(path('admin/enquiries')) ?>" class="btn btn-ghost btn-sm btn-block">All consultation requests</a></div>
      <?php endif; ?>
    </section>

    <!-- Pipeline breakdown -->
    <section class="card">
      <div class="card-head"><h3>Pipeline by status</h3></div>
      <div class="card-body tight">
        <?php $totalBids = max(1, array_sum($statusCounts)); ?>
        <?php foreach (Bid::STATUSES as $key => $label): ?>
          <?php $count = $statusCounts[$key] ?? 0; ?>
          <div class="u-between" style="padding:6px 0;">
            <span class="u-small"><span class="badge badge-<?= e(Bid::statusTone($key)) ?>"><?= e($label) ?></span></span>
            <span class="u-flex" style="gap:9px;">
              <span class="meter" style="width:80px;"><span style="width:<?= (int) round(($count / $totalBids) * 100) ?>%"></span></span>
              <strong class="u-small" style="min-width:22px;text-align:right;"><?= (int) $count ?></strong>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <?php if ($activity): ?>
      <section class="card">
        <div class="card-head">
          <h3>Recent activity</h3>
          <div class="head-actions"><a href="<?= e(path('admin/activity')) ?>" class="btn btn-subtle btn-sm">View all</a></div>
        </div>
        <div class="card-body tight">
          <div class="timeline">
            <?php foreach ($activity as $entry): ?>
              <div class="tl-item">
                <div class="tl-meta"><?= e(fdatetime((string) $entry['created_at'], 'j M · H:i')) ?> — <?= e((string) $entry['actor_name']) ?></div>
                <div class="tl-body"><?= e((string) ($entry['description'] !== '' ? $entry['description'] : labelize((string) $entry['action']))) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>
  </div>

</div>
