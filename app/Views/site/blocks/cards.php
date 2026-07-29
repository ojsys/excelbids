<?php
/** @var array<string,mixed> $settings */

use App\Core\BlockRenderer as R;

$items = R::rows($settings, 'items');
if (!$items) {
    return;
}

$columns = R::get($settings, 'columns', '3');
$style = R::get($settings, 'style', 'numbered');
?>
<div class="pb-block pb-cards pb-cards-<?= e($columns) ?> pb-cards-<?= e($style) ?>">
  <?php foreach ($items as $index => $item): ?>
    <?php
      $title = trim((string) ($item['title'] ?? ''));
      if ($title === '') { continue; }
      $text = trim((string) ($item['text'] ?? ''));
      $icon = trim((string) ($item['icon'] ?? ''));
      $url  = trim((string) ($item['url'] ?? ''));
      $tag  = $url !== '' ? 'a' : 'div';
    ?>
    <<?= $tag ?> class="pb-card"<?= $url !== '' ? ' href="' . e(R::link($url)) . '"' : '' ?>>
      <?php if ($style === 'numbered'): ?>
        <span class="pb-card-idx"><?= e(sprintf('%02d', $index + 1)) ?></span>
      <?php elseif ($style === 'seal'): ?>
        <span class="pb-card-seal" aria-hidden="true"><?= e($icon !== '' ? $icon : '◈') ?></span>
      <?php endif; ?>

      <h4 class="pb-card-title"><?= e($title) ?></h4>
      <?php if ($text !== ''): ?><p class="pb-card-text"><?= nl2br(e($text)) ?></p><?php endif; ?>
      <?php if ($url !== ''): ?><span class="pb-card-more">Read more →</span><?php endif; ?>
    </<?= $tag ?>>
  <?php endforeach; ?>
</div>
