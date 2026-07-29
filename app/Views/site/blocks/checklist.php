<?php
/**
 * The QA sign-off sheet. Self-contained styling — see document.php for why.
 *
 * @var array<string,mixed> $settings
 */

use App\Core\BlockRenderer as R;

$items = R::rows($settings, 'items');
if (!$items) {
    return;
}

$signature = trim(R::get($settings, 'signature', 'Cleared for submission'));
$signatureMeta = trim(R::get($settings, 'signature_meta'));
?>
<div class="pb-block pb-signoff">
  <?php foreach ($items as $item): ?>
    <?php $title = trim((string) ($item['title'] ?? '')); if ($title === '') { continue; } ?>
    <div class="pb-signoff-row">
      <span class="pb-signoff-box" aria-hidden="true"></span>
      <div>
        <h4><?= e($title) ?></h4>
        <?php if (trim((string) ($item['text'] ?? '')) !== ''): ?>
          <p><?= e((string) $item['text']) ?></p>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if ($signature !== '' || $signatureMeta !== ''): ?>
    <div class="pb-signoff-sign">
      <div class="pb-signoff-sig"><?= e($signature) ?></div>
      <?php if ($signatureMeta !== ''): ?>
        <div class="pb-signoff-meta"><?= nl2br(e($signatureMeta)) ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
