<?php
/** @var array<string,mixed> $settings */

use App\Core\BlockRenderer as R;

$items = R::rows($settings, 'items');
if (!$items) {
    return;
}

$style = R::get($settings, 'style', 'bar');
$note = trim(R::get($settings, 'note'));
?>
<div class="pb-block pb-stats pb-stats-<?= e($style) ?>">
  <?php foreach ($items as $item): ?>
    <?php $value = trim((string) ($item['value'] ?? '')); if ($value === '') { continue; } ?>
    <div class="pb-stat">
      <strong><?= e($value) ?></strong>
      <span><?= e((string) ($item['label'] ?? '')) ?></span>
    </div>
  <?php endforeach; ?>
</div>
<?php if ($note !== ''): ?>
  <p class="pb-stats-note"><?= e($note) ?></p>
<?php endif; ?>
