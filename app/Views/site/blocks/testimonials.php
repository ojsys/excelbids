<?php
/** @var array<string,mixed> $settings */

use App\Core\BlockRenderer as R;

$items = R::rows($settings, 'items');
if (!$items) {
    return;
}
?>
<div class="pb-block t-grid">
  <?php foreach ($items as $item): ?>
    <?php $quote = trim((string) ($item['quote'] ?? '')); if ($quote === '') { continue; } ?>
    <div class="t-card">
      <div class="qmark" aria-hidden="true">&ldquo;</div>
      <p class="quote"><?= e($quote) ?></p>
      <?php if (trim((string) ($item['author'] ?? '')) !== '' || trim((string) ($item['org'] ?? '')) !== ''): ?>
        <div class="who">
          <strong><?= e((string) ($item['author'] ?? '')) ?></strong>
          <span><?= e((string) ($item['org'] ?? '')) ?></span>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
