<?php
/**
 * One row in a bid table. Shared by the bid list, client record and dashboard.
 *
 * @var array<string,mixed> $bid
 * @var bool                $showClient
 */

use App\Models\Bid;

$deadline = Bid::deadlineState($bid);
$showClient = $showClient ?? true;
?>
<tr>
  <td>
    <span class="primary-cell">
      <a href="<?= e(path('admin/bids/' . $bid['id'])) ?>"><?= e(str_excerpt((string) $bid['title'], 62)) ?></a>
    </span>
    <span class="sub-cell ref"><?= e((string) $bid['reference']) ?><?php if (!empty($bid['buyer'])): ?> · <?= e(str_excerpt((string) $bid['buyer'], 40)) ?><?php endif; ?></span>
  </td>

  <?php if ($showClient): ?>
    <td>
      <a href="<?= e(path('admin/clients/' . $bid['client_id'])) ?>"><?= e(str_excerpt((string) ($bid['organisation'] ?? ''), 34)) ?></a>
    </td>
  <?php endif; ?>

  <td><span class="badge badge-neutral"><?= e(Bid::STAGES[$bid['stage']] ?? labelize((string) $bid['stage'])) ?></span></td>

  <td>
    <span class="badge badge-<?= e(Bid::statusTone((string) $bid['status'])) ?>">
      <?= e(Bid::STATUSES[$bid['status']] ?? labelize((string) $bid['status'])) ?>
    </span>
  </td>

  <td>
    <?php if (!empty($bid['submission_due'])): ?>
      <span class="u-small u-nowrap"><?= e(fdatetime((string) $bid['submission_due'], 'j M Y')) ?></span><br>
      <span class="deadline <?= e($deadline['level']) ?>"><?= e($deadline['label']) ?></span>
    <?php else: ?>
      <span class="deadline neutral">Not set</span>
    <?php endif; ?>
  </td>

  <td class="num u-nowrap"><?= (float) $bid['contract_value'] > 0 ? e(money($bid['contract_value'])) : '<span class="u-faint">—</span>' ?></td>

  <td class="u-small u-muted"><?= e((string) ($bid['owner_name'] ?? '—')) ?></td>
</tr>
