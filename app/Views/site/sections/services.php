<?php
/**
 * @var array<int,array<string,mixed>> $services
 * @var string|null                    $ghostNum
 */
if (!$services) {
    return;
}
$intro = block('services_intro');
?>
<section id="services" class="tint">
  <?php if ($ghostNum): ?><div class="ghost-num" aria-hidden="true"><?= e($ghostNum) ?></div><?php endif; ?>
  <div class="wrap">
    <div class="section-head" style="margin-bottom:24px;">
      <div class="file-num mono"><?= e(block('services_file_num', 'FILE §02')) ?></div>
      <h2><?= e(block('services_heading', 'Our Services')) ?></h2>
      <?php if ($intro !== ''): ?><p><?= e($intro) ?></p><?php endif; ?>
    </div>

    <div class="svc-grid">
      <?php foreach ($services as $index => $service): ?>
        <div class="svc-tile">
          <span class="idx"><?= e(sprintf('%02d', $index + 1)) ?></span>
          <?= e($service['title']) ?>
          <?php if (!empty($service['description'])): ?>
            <small><?= e($service['description']) ?></small>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
