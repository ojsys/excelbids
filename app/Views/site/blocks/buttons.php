<?php
/** @var array<string,mixed> $settings */

use App\Core\BlockRenderer as R;

$items = R::rows($settings, 'items');
if (!$items) {
    return;
}

$align = R::get($settings, 'align', 'left');
?>
<div class="pb-block pb-buttons pb-align-<?= e($align) ?>">
  <?php foreach ($items as $item): ?>
    <?php
      $label = trim((string) ($item['label'] ?? ''));
      if ($label === '') { continue; }
      $style = (string) ($item['style'] ?? 'red');
      $href = R::link((string) ($item['url'] ?? ''));
      $external = str_starts_with($href, 'http');
    ?>
    <a class="btn btn-<?= e($style) ?>" href="<?= e($href) ?>"
       <?= $external ? 'target="_blank" rel="noopener"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</div>
