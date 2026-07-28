<?php
/**
 * @var array<string,mixed>            $summary
 * @var array<int,array<string,mixed>> $trend
 * @var array<int,array<string,mixed>> $bySector
 * @var array<int,array<string,mixed>> $byPortal
 * @var string|null                    $from
 * @var string|null                    $to
 * @var string                         $preset
 * @var string                         $tab
 */

use App\Core\View;

echo View::partial('admin/reports/partials/filter', compact('tab', 'from', 'to', 'preset'));

$maxTrend = max(1, max(array_column($trend, 'total')));
$exportQuery = '?' . http_build_query(array_filter(['from' => $from, 'to' => $to]));
?>

<div class="kpi-grid">
  <div class="kpi is-navy">
    <div class="label">Bids created</div>
    <div class="value"><?= (int) $summary['total_bids'] ?></div>
    <div class="meta"><?= (int) $summary['open_bids'] ?> still open</div>
  </div>
  <div class="kpi is-green">
    <div class="label">Win rate</div>
    <div class="value"><?= e((string) $summary['win_rate']) ?><small>%</small></div>
    <div class="meta"><?= (int) $summary['won'] ?> won of <?= (int) $summary['decided'] ?> decided</div>
  </div>
  <div class="kpi">
    <div class="label">Contract value won</div>
    <div class="value"><?= e(money($summary['value_won'])) ?></div>
    <div class="meta"><?= e(money($summary['pipeline_value'])) ?> still in pipeline</div>
  </div>
  <div class="kpi is-red">
    <div class="label">Fees on won bids</div>
    <div class="value"><?= e(money($summary['fees_won'])) ?></div>
    <div class="meta"><?= $summary['avg_score'] !== null ? 'Avg. evaluation ' . e((string) $summary['avg_score']) : 'No scores recorded' ?></div>
  </div>
</div>

<div class="grid grid-2">
  <section class="card">
    <div class="card-head">
      <div><h2>Consultation requests</h2><div class="sub">How enquiries turn into clients</div></div>
    </div>
    <div class="card-body tight">
      <dl class="dl">
        <dt>Requests received</dt><dd><strong><?= (int) $summary['enquiries'] ?></strong></dd>
        <dt>Converted to clients</dt><dd><strong><?= (int) $summary['enquiries_converted'] ?></strong></dd>
        <dt>Conversion rate</dt>
        <dd>
          <span class="u-flex" style="gap:9px;">
            <span class="meter" style="width:90px;"><span style="width:<?= (int) round((float) $summary['conversion_rate']) ?>%"></span></span>
            <strong><?= e((string) $summary['conversion_rate']) ?>%</strong>
          </span>
        </dd>
        <dt>New client records</dt><dd><strong><?= (int) $summary['new_clients'] ?></strong></dd>
      </dl>
    </div>
  </section>

  <section class="card">
    <div class="card-head">
      <div><h2>Outcome split</h2><div class="sub">Of bids with a decision</div></div>
    </div>
    <div class="card-body tight">
      <?php $decided = max(1, (int) $summary['decided']); ?>
      <div class="u-between" style="padding:7px 0;">
        <span class="u-small"><span class="badge badge-success">Won</span></span>
        <span class="u-flex" style="gap:9px;">
          <span class="meter" style="width:110px;"><span style="width:<?= (int) round(((int) $summary['won'] / $decided) * 100) ?>%"></span></span>
          <strong class="u-small"><?= (int) $summary['won'] ?></strong>
        </span>
      </div>
      <div class="u-between" style="padding:7px 0;">
        <span class="u-small"><span class="badge badge-danger">Lost</span></span>
        <span class="u-flex" style="gap:9px;">
          <span class="meter is-low" style="width:110px;"><span style="width:<?= (int) round(((int) $summary['lost'] / $decided) * 100) ?>%"></span></span>
          <strong class="u-small"><?= (int) $summary['lost'] ?></strong>
        </span>
      </div>
      <div class="u-between" style="padding:7px 0;">
        <span class="u-small"><span class="badge badge-info">Submitted, awaiting decision</span></span>
        <strong class="u-small"><?= (int) $summary['submitted'] ?></strong>
      </div>
    </div>
  </section>
</div>

<section class="card">
  <div class="card-head">
    <div><h2>Twelve-month trend</h2><div class="sub">Bids created each month, and how they were decided</div></div>
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
      <span><i style="background:var(--navy)"></i> Open or withdrawn</span>
      <span><i style="background:var(--green)"></i> Won</span>
      <span><i style="background:var(--red)"></i> Lost</span>
    </div>
  </div>
</section>

<div class="grid grid-2">
  <section class="card">
    <div class="card-head">
      <div><h2>By sector</h2></div>
      <div class="head-actions">
        <a href="<?= e(path('admin/reports/export/sectors') . $exportQuery) ?>" class="btn btn-ghost btn-sm">CSV</a>
      </div>
    </div>
    <?php if (!$bySector): ?>
      <div class="card-body"><p class="u-small u-muted u-mb0">No bids have a sector recorded in this period.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Sector</th><th class="num">Bids</th><th class="num">Won</th><th class="num">Value won</th></tr></thead>
          <tbody>
            <?php foreach ($bySector as $row): ?>
              <tr>
                <td class="primary-cell"><?= e((string) $row['sector']) ?></td>
                <td class="num"><?= (int) $row['total'] ?></td>
                <td class="num"><?= (int) $row['won'] ?><span class="sub-cell"><?= (int) $row['lost'] ?> lost</span></td>
                <td class="num u-nowrap"><?= e(money($row['value_won'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="card-head"><div><h2>By portal</h2></div></div>
    <?php if (!$byPortal): ?>
      <div class="card-body"><p class="u-small u-muted u-mb0">No bids have a portal recorded in this period.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Portal</th><th class="num">Bids</th><th class="num">Won</th><th class="num">Value won</th></tr></thead>
          <tbody>
            <?php foreach ($byPortal as $row): ?>
              <tr>
                <td class="primary-cell"><?= e((string) $row['portal']) ?></td>
                <td class="num"><?= (int) $row['total'] ?></td>
                <td class="num"><?= (int) $row['won'] ?></td>
                <td class="num u-nowrap"><?= e(money($row['value_won'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>

<section class="card">
  <div class="card-head">
    <div><h2>Export</h2><div class="sub">Opens in Excel with the current date window applied</div></div>
  </div>
  <div class="card-body u-flex">
    <a href="<?= e(path('admin/reports/export/bids') . $exportQuery) ?>" class="btn btn-ghost btn-sm">Full bid report</a>
    <a href="<?= e(path('admin/reports/export/clients') . $exportQuery) ?>" class="btn btn-ghost btn-sm">Client report</a>
    <a href="<?= e(path('admin/reports/export/sectors') . $exportQuery) ?>" class="btn btn-ghost btn-sm">Sector report</a>
    <a href="<?= e(path('admin/reports/export/performance') . $exportQuery) ?>" class="btn btn-ghost btn-sm">Performance report</a>
  </div>
</section>
