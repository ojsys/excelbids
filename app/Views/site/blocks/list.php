<?php
/** @var array<string,mixed> $settings */

use App\Core\BlockRenderer as R;

$items = R::rows($settings, 'items');
if (!$items) {
    return;
}

$marker = R::get($settings, 'marker', 'tick');
$tag = $marker === 'number' ? 'ol' : 'ul';
?>
<<?= $tag ?> class="pb-block pb-list pb-list-<?= e($marker) ?>">
  <?php foreach ($items as $index => $item): ?>
    <?php
      $text = trim((string) ($item['text'] ?? ''));
      if ($text === '') { continue; }
      $note = trim((string) ($item['note'] ?? ''));
    ?>
    <li>
      <?php if ($marker === 'tick'): ?>
        <span class="pb-tick" aria-hidden="true">✓</span>
      <?php elseif ($marker === 'dash'): ?>
        <span class="pb-tick pb-dash" aria-hidden="true">—</span>
      <?php endif; ?>
      <span class="pb-list-body">
        <strong><?= e($text) ?></strong>
        <?php if ($note !== ''): ?><span class="pb-list-note"><?= e($note) ?></span><?php endif; ?>
      </span>
    </li>
  <?php endforeach; ?>
</<?= $tag ?>>
