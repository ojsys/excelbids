<?php
/**
 * @var array<int,array<string,mixed>> $bids
 * @var string                         $filter
 * @var array<string,int>              $counts
 */

use App\Models\Bid;
?>

<div class="tabs">
  <a href="<?= e(path('portal/bids')) ?>" class="<?= $filter === '' ? 'active' : '' ?>">
    All (<?= array_sum($counts) ?>)
  </a>
  <?php foreach (Bid::STATUSES as $key => $label): ?>
    <?php if (($counts[$key] ?? 0) === 0) { continue; } ?>
    <a href="<?= e(path('portal/bids') . '?status=' . $key) ?>" class="<?= $filter === $key ? 'active' : '' ?>">
      <?= e($label) ?> (<?= (int) $counts[$key] ?>)
    </a>
  <?php endforeach; ?>
</div>

<div class="card">
  <?php if (!$bids): ?>
    <div class="empty">
      <span class="mark">▤</span>
      <h3>No bids to show</h3>
      <p>
        <?= $filter !== '' ? 'Nothing matches that filter.' : 'When we start work on an opportunity for you, it will appear here.' ?>
      </p>
      <?php if ($filter !== ''): ?>
        <a href="<?= e(path('portal/bids')) ?>" class="btn btn-ghost btn-sm">Show all bids</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th>Bid</th><th>Buyer</th><th>Stage</th><th>Status</th><th>Deadline</th><th class="num">Value</th></tr>
        </thead>
        <tbody>
          <?php foreach ($bids as $bid): ?>
            <?php $deadline = Bid::deadlineState($bid); ?>
            <tr>
              <td>
                <span class="primary-cell"><a href="<?= e(path('portal/bids/' . $bid['id'])) ?>"><?= e(str_excerpt((string) $bid['title'], 58)) ?></a></span>
                <span class="sub-cell ref"><?= e((string) $bid['reference']) ?></span>
              </td>
              <td class="u-small u-muted"><?= e(str_excerpt((string) ($bid['buyer'] !== '' ? $bid['buyer'] : '—'), 30)) ?></td>
              <td><span class="badge badge-neutral"><?= e(Bid::STAGES[$bid['stage']] ?? '') ?></span></td>
              <td><span class="badge badge-<?= e(Bid::statusTone((string) $bid['status'])) ?>"><?= e(Bid::STATUSES[$bid['status']] ?? '') ?></span></td>
              <td>
                <?php if (!empty($bid['submission_due'])): ?>
                  <span class="u-small u-nowrap"><?= e(fdatetime((string) $bid['submission_due'], 'j M Y')) ?></span><br>
                  <span class="deadline <?= e($deadline['level']) ?>"><?= e($deadline['label']) ?></span>
                <?php else: ?>
                  <span class="deadline neutral">To be confirmed</span>
                <?php endif; ?>
              </td>
              <td class="num u-nowrap">
                <?= (float) $bid['contract_value'] > 0 ? e(money($bid['contract_value'])) : '<span class="u-faint">—</span>' ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
