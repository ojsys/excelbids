<?php
/** @var array<int,array<string,mixed>> $portals */
if (!$portals) {
    return;
}
?>
<div class="strip">
  <div class="wrap strip-inner">
    <div class="strip-label"><?= e(block('portals_label', 'Portals we work in daily')) ?></div>
    <div class="stamp-row">
      <?php foreach ($portals as $portal): ?>
        <div class="mini-stamp"><?= e($portal['name']) ?><?php if ($portal['line_two'] !== ''): ?><br><?= e($portal['line_two']) ?><?php endif; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
