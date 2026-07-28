<?php
/**
 * @var array<int,array<string,mixed>> $byOwner
 * @var array<int,array<string,mixed>> $qa
 */

use App\Core\View;

echo View::partial('admin/reports/partials/filter', compact('tab', 'from', 'to', 'preset'));

$exportQuery = '?' . http_build_query(array_filter(['from' => $from, 'to' => $to]));
?>

<section class="card">
  <div class="card-head">
    <div><h2>By bid owner</h2><div class="sub">Bids each team member is responsible for</div></div>
    <div class="head-actions">
      <a href="<?= e(path('admin/reports/export/performance') . $exportQuery) ?>" class="btn btn-ghost btn-sm">Export CSV</a>
    </div>
  </div>

  <?php if (!$byOwner): ?>
    <div class="empty">
      <span class="mark">◍</span>
      <h3>No bids have an owner in this period</h3>
      <p>Assign a bid owner when creating or editing a bid and this report fills in.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr>
            <th>Team member</th><th class="num">Total</th><th class="num">Open</th>
            <th class="num">Won</th><th class="num">Lost</th><th class="num">Win rate</th><th class="num">Avg. score</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($byOwner as $row): ?>
            <?php
              $decided = (int) $row['won'] + (int) $row['lost'];
              $winRate = $decided > 0 ? round(((int) $row['won'] / $decided) * 100) : null;
            ?>
            <tr>
              <td>
                <span class="primary-cell"><?= e((string) $row['name']) ?></span>
                <span class="sub-cell"><?= e(labelize((string) $row['role'])) ?></span>
              </td>
              <td class="num"><strong><?= (int) $row['total'] ?></strong></td>
              <td class="num"><?= (int) $row['open_bids'] ?></td>
              <td class="num"><?= (int) $row['won'] ?></td>
              <td class="num"><?= (int) $row['lost'] ?></td>
              <td class="num">
                <?= $winRate !== null ? (int) $winRate . '%' : '<span class="u-faint">—</span>' ?>
              </td>
              <td class="num">
                <?= $row['avg_score'] !== null ? e((string) round((float) $row['avg_score'], 1)) : '<span class="u-faint">—</span>' ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<section class="card">
  <div class="card-head">
    <div>
      <h2>Quality assurance</h2>
      <div class="sub">Pass rate for each check across bids that have reached QA</div>
    </div>
  </div>

  <?php if (!$qa): ?>
    <div class="card-body">
      <p class="u-small u-muted u-mb0">
        No bids have reached the QA stage yet. Once they do, this shows which checks most often need rework —
        useful for spotting where drafting quality slips.
      </p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Check</th><th class="num">Assessed</th><th class="num">Passed</th><th>Pass rate</th></tr></thead>
        <tbody>
          <?php foreach ($qa as $row): ?>
            <?php $rate = (float) $row['pass_rate']; ?>
            <tr>
              <td class="primary-cell"><?= e((string) $row['title']) ?></td>
              <td class="num"><?= (int) $row['assessed'] ?></td>
              <td class="num"><?= (int) $row['passed'] ?></td>
              <td>
                <span class="u-flex" style="gap:10px;">
                  <span class="meter<?= $rate < 50 ? ' is-low' : ($rate < 85 ? ' is-mid' : '') ?>" style="width:110px;">
                    <span style="width:<?= (int) round($rate) ?>%"></span>
                  </span>
                  <strong class="u-small"><?= e((string) $rate) ?>%</strong>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
