<?php
/** @var array<int,array<string,mixed>> $rows */

use App\Core\View;

echo View::partial('admin/reports/partials/filter', compact('tab', 'from', 'to', 'preset'));

$exportQuery = '?' . http_build_query(array_filter(['from' => $from, 'to' => $to]));
?>

<section class="card">
  <div class="card-head">
    <div><h2>Bids by client</h2><div class="sub">Busiest clients first</div></div>
    <div class="head-actions">
      <a href="<?= e(path('admin/reports/export/clients') . $exportQuery) ?>" class="btn btn-ghost btn-sm">Export CSV</a>
    </div>
  </div>

  <?php if (!$rows): ?>
    <div class="empty">
      <span class="mark">◫</span>
      <h3>No client activity in this period</h3>
      <p>Widen the date range, or add the first bid for a client.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr>
            <th>Client</th><th class="num">Bids</th><th class="num">Won</th><th class="num">Lost</th>
            <th class="num">Win rate</th><th class="num">Value won</th><th class="num">Fees</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <?php
              $decided = (int) $row['won'] + (int) $row['lost'];
              $winRate = $decided > 0 ? round(((int) $row['won'] / $decided) * 100) : null;
            ?>
            <tr>
              <td>
                <span class="primary-cell"><a href="<?= e(path('admin/clients/' . $row['id'])) ?>"><?= e(str_excerpt((string) $row['organisation'], 40)) ?></a></span>
                <span class="sub-cell ref"><?= e((string) $row['reference']) ?></span>
              </td>
              <td class="num"><strong><?= (int) $row['total_bids'] ?></strong></td>
              <td class="num"><?= (int) $row['won'] ?></td>
              <td class="num"><?= (int) $row['lost'] ?></td>
              <td class="num">
                <?php if ($winRate !== null): ?>
                  <span class="u-flex" style="gap:8px;justify-content:flex-end;">
                    <span class="meter<?= $winRate < 34 ? ' is-low' : ($winRate < 67 ? ' is-mid' : '') ?>" style="width:52px;">
                      <span style="width:<?= (int) $winRate ?>%"></span>
                    </span>
                    <?= (int) $winRate ?>%
                  </span>
                <?php else: ?>
                  <span class="u-faint">—</span>
                <?php endif; ?>
              </td>
              <td class="num u-nowrap"><?= e(money($row['value_won'])) ?></td>
              <td class="num u-nowrap"><?= e(money($row['fees'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
