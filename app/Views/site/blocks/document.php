<?php
/**
 * The tilted case-file panel from the hero — the site's signature device.
 *
 * @var array<string,mixed> $settings
 */

use App\Core\BlockRenderer as R;
use App\Core\Content;

$topline = trim(R::get($settings, 'topline'));
$body    = trim(R::get($settings, 'body'));
$note    = trim(R::get($settings, 'note'));
$stamp   = trim(R::get($settings, 'stamp'));
$sticky  = trim(R::get($settings, 'sticky'));
$stickyValue = trim(R::get($settings, 'sticky_value'));

if ($topline === '' && $body === '') {
    return;
}
?>
<div class="pb-block doc-wrap" aria-hidden="true">
  <div class="doc-page">
    <svg class="paperclip" viewBox="0 0 24 40" fill="none"><path d="M6 10V28a6 6 0 0012 0V8a4 4 0 00-8 0v18a2 2 0 004 0V10" stroke="#8A8A80" stroke-width="2" stroke-linecap="round"/></svg>

    <?php if ($topline !== ''): ?>
      <div class="doc-topline mono"><?= e($topline) ?></div>
    <?php endif; ?>

    <?php foreach (preg_split('/\n\s*\n/', $body) ?: [] as $paragraph): ?>
      <?php if (trim($paragraph) === '') { continue; } ?>
      <p class="doc-text"><?= Content::expand(trim($paragraph)) ?></p>
    <?php endforeach; ?>

    <?php if ($note !== ''): ?>
      <div class="doc-note doc-note-1 hand"><?= e($note) ?> ✓</div>
    <?php endif; ?>

    <?php if ($stamp !== ''): ?>
      <div class="stamp"><?= implode('<br>', array_map('eb_e', array_map('trim', explode(',', $stamp)))) ?></div>
    <?php endif; ?>
  </div>

  <?php if ($sticky !== ''): ?>
    <div class="sticky"><?= e($sticky) ?><span><?= e($stickyValue) ?></span></div>
  <?php endif; ?>
</div>
