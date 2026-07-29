<?php
/** @var array<string,mixed> $settings */

use App\Core\BlockRenderer as R;

$items = R::rows($settings, 'items');
if (!$items) {
    return;
}
?>
<div class="pb-block sector-tags">
  <?php foreach ($items as $item): ?>
    <?php $label = trim((string) ($item['label'] ?? '')); if ($label === '') { continue; } ?>
    <span<?= (string) ($item['is_core'] ?? '0') === '1' ? ' class="core"' : '' ?>><?= e($label) ?></span>
  <?php endforeach; ?>
</div>
