<?php
/**
 * @var array<string,mixed>            $summary
 * @var array<int,array<string,mixed>> $deadlines
 */

use App\Core\View;
use App\Models\Bid;

echo View::partial('admin/reports/partials/filter', compact('tab', 'from', 'to', 'preset'));
?>

<div class="kpi-grid">
  <div class="kpi is-red">
    <div class="label">Open bids</div>
    <div class="value"><?= (int) $summary['open_bids'] ?></div>
    <div class="meta">Draft, in progress or submitted</div>
  </div>
  <div class="kpi is-navy">
    <div class="label">Pipeline value</div>
    <div class="value"><?= e(money($summary['pipeline_value'])) ?></div>
    <div class="meta">Total contract value at stake</div>
  </div>
  <div class="kpi">
    <div class="label">Awaiting decision</div>
    <div class="value"><?= (int) $summary['submitted'] ?></div>
    <div class="meta">Submitted, not yet decided</div>
  </div>
  <div class="kpi is-green">
    <div class="label">Deadlines in 90 days</div>
    <div class="value"><?= count($deadlines) ?></div>
    <div class="meta">Across all open bids</div>
  </div>
</div>

<section class="card">
  <div class="card-head">
    <div><h2>Every deadline in the next 90 days</h2><div class="sub">Soonest first — the workload ahead</div></div>
    <div class="head-actions"><a href="<?= e(path('admin/bids/calendar')) ?>" class="btn btn-ghost btn-sm">Calendar view</a></div>
  </div>

  <?php if (!$deadlines): ?>
    <div class="empty">
      <span class="mark">◷</span>
      <h3>No deadlines in the next 90 days</h3>
      <p>Open bids with a submission deadline inside the window will be listed here.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th>Due</th><th>Bid</th><th>Client</th><th>Stage</th><th>Status</th><th>Owner</th></tr>
        </thead>
        <tbody>
          <?php foreach ($deadlines as $bid): ?>
            <?php $deadline = Bid::deadlineState($bid); ?>
            <tr>
              <td class="u-nowrap">
                <strong class="u-small"><?= e(fdatetime((string) $bid['submission_due'], 'j M Y')) ?></strong>
                <span class="sub-cell"><span class="deadline <?= e($deadline['level']) ?>"><?= e($deadline['label']) ?></span></span>
              </td>
              <td>
                <span class="primary-cell"><a href="<?= e(path('admin/bids/' . $bid['id'])) ?>"><?= e(str_excerpt((string) $bid['title'], 54)) ?></a></span>
                <span class="sub-cell ref"><?= e((string) $bid['reference']) ?></span>
              </td>
              <td class="u-small"><?= e(str_excerpt((string) $bid['organisation'], 28)) ?></td>
              <td><span class="badge badge-neutral"><?= e(Bid::STAGES[$bid['stage']] ?? '') ?></span></td>
              <td><span class="badge badge-<?= e(Bid::statusTone((string) $bid['status'])) ?>"><?= e(Bid::STATUSES[$bid['status']] ?? '') ?></span></td>
              <td class="u-small u-muted"><?= e((string) ($bid['owner_name'] ?? '—')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
