<?php
/**
 * @var array<int,array<string,mixed>> $processSteps
 * @var string|null                    $ghostNum
 */
if (!$processSteps) {
    return;
}
$intro = block('process_intro');
?>
<section id="process" class="tint">
  <?php if ($ghostNum): ?><div class="ghost-num" aria-hidden="true"><?= e($ghostNum) ?></div><?php endif; ?>
  <div class="wrap">
    <div class="section-head" style="margin-bottom:24px;">
      <div class="file-num mono"><?= e(block('process_file_num', 'FILE §04')) ?></div>
      <h2><?= e(block('process_heading', 'Our Bid Writing Process')) ?></h2>
      <?php if ($intro !== ''): ?><p><?= e($intro) ?></p><?php endif; ?>
    </div>

    <div class="stepper">
      <?php foreach ($processSteps as $index => $step): ?>
        <div class="step">
          <span class="step-num mono"><?= e(sprintf('%02d', $index + 1)) ?></span>
          <span>
            <?= e($step['title']) ?>
            <?php if (!empty($step['description'])): ?><small><?= e($step['description']) ?></small><?php endif; ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
