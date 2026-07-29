<?php
/** @var array<string,mixed> $settings */

use App\Core\BlockRenderer as R;

$heading = trim(R::get($settings, 'heading'));
if ($heading === '') {
    return;
}

$text  = trim(R::get($settings, 'text'));
$note  = trim(R::get($settings, 'note'));
$stamp = trim(R::get($settings, 'stamp'));
$email = trim(R::get($settings, 'email'));

$buttons = [];
foreach ([['button_label', 'button_url', 'red'], ['button2_label', 'button2_url', 'ghost-light']] as [$labelKey, $urlKey, $style]) {
    $label = trim(R::get($settings, $labelKey));
    if ($label !== '') {
        $buttons[] = ['label' => $label, 'url' => R::link(R::get($settings, $urlKey)), 'style' => $style];
    }
}
?>
<div class="pb-block cta-wrap">
  <div class="cta-inner">
    <div>
      <h2><?= e($heading) ?></h2>
      <?php if ($text !== ''): ?><p class="sub"><?= nl2br(e($text)) ?></p><?php endif; ?>
      <?php if ($email !== ''): ?>
        <a class="cta-email mono" href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
      <?php endif; ?>
    </div>

    <?php if ($buttons): ?>
      <div class="cta-actions">
        <?php if ($note !== ''): ?><div class="point-note hand"><?= e($note) ?> &rarr;</div><?php endif; ?>
        <?php foreach ($buttons as $button): ?>
          <a href="<?= e($button['url']) ?>" class="btn btn-<?= e($button['style']) ?>"><?= e($button['label']) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($stamp !== ''): ?>
    <div class="approved" aria-hidden="true"><?= implode('<br>', array_map('eb_e', array_map('trim', explode(',', $stamp)))) ?></div>
  <?php endif; ?>
</div>
