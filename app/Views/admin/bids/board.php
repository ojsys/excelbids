<?php
/** @var array<string,array<int,array<string,mixed>>> $columns */

use App\Models\Bid;
?>

<div class="u-between u-mb">
  <p class="u-small u-muted u-mb0">Open bids only, grouped by the stage they have reached.</p>
  <a href="<?= e(path('admin/bids')) ?>" class="btn btn-ghost btn-sm">List view</a>
</div>

<div style="overflow-x:auto; padding-bottom:8px;">
  <div style="display:flex; gap:14px; min-width:1000px; align-items:flex-start;">
    <?php foreach (Bid::STAGES as $stage => $label): ?>
      <?php $bids = $columns[$stage] ?? []; ?>
      <div style="flex:1 1 0; min-width:180px;">
        <div class="card u-mb0" style="background:#F7F5EE;">
          <div class="card-head" style="padding:11px 14px;">
            <div>
              <h3 style="font-size:13px;"><?= e($label) ?></h3>
              <div class="sub"><?= count($bids) ?> bid<?= count($bids) === 1 ? '' : 's' ?></div>
            </div>
          </div>
        </div>

        <div style="display:flex; flex-direction:column; gap:9px; margin-top:9px;">
          <?php foreach ($bids as $bid): ?>
            <?php $deadline = Bid::deadlineState($bid); ?>
            <a href="<?= e(path('admin/bids/' . $bid['id'])) ?>" class="card u-mb0"
               style="padding:12px 13px; display:block; border-left:3px solid var(--<?= $deadline['level'] === 'overdue' ? 'red' : ($deadline['level'] === 'urgent' ? 'gold' : 'line') ?>);">
              <div class="ref"><?= e((string) $bid['reference']) ?></div>
              <div class="u-small" style="font-weight:600; margin:3px 0 5px; line-height:1.4;">
                <?= e(str_excerpt((string) $bid['title'], 58)) ?>
              </div>
              <div class="u-small u-faint"><?= e(str_excerpt((string) $bid['organisation'], 28)) ?></div>
              <div class="u-between" style="margin-top:8px;">
                <span class="deadline <?= e($deadline['level']) ?>" style="font-size:11px;"><?= e($deadline['label']) ?></span>
                <?php if ((float) $bid['contract_value'] > 0): ?>
                  <span class="u-small u-faint u-mono"><?= e(money($bid['contract_value'])) ?></span>
                <?php endif; ?>
              </div>
            </a>
          <?php endforeach; ?>

          <?php if (!$bids): ?>
            <p class="u-small u-faint u-center" style="padding:16px 8px;">Nothing here</p>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
