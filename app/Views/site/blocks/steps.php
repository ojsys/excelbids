<?php
/** @var array<string,mixed> $settings */

use App\Core\BlockRenderer as R;

$items = R::rows($settings, 'items');
if (!$items) {
    return;
}
?>
<div class="pb-block stepper">
  <?php foreach ($items as $index => $item): ?>
    <?php $title = trim((string) ($item['title'] ?? '')); if ($title === '') { continue; } ?>
    <div class="step">
      <span class="step-num mono"><?= e(sprintf('%02d', $index + 1)) ?></span>
      <span>
        <?= e($title) ?>
        <?php if (trim((string) ($item['text'] ?? '')) !== ''): ?>
          <small><?= e((string) $item['text']) ?></small>
        <?php endif; ?>
      </span>
    </div>
  <?php endforeach; ?>
</div>
