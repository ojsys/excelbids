<?php
/**
 * The tilted case-file panel — the site's signature device.
 *
 * Uses its own pb-doc-* classes rather than the home page hero's, so redesigning
 * the hero can never leave this block unstyled.
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
<div class="pb-block pb-doc" aria-hidden="true">
  <div class="pb-doc-page<?= $stamp !== '' ? ' has-stamp' : '' ?>">
    <!-- Explicit width/height: a viewBox-only SVG scales to its container, which
         would render this as a full-width paperclip if the CSS ever went missing. -->
    <svg class="pb-doc-clip" width="34" height="50" viewBox="0 0 24 40" fill="none" aria-hidden="true">
      <path d="M6 10V28a6 6 0 0012 0V8a4 4 0 00-8 0v18a2 2 0 004 0V10"
            stroke="#8A8A80" stroke-width="2" stroke-linecap="round"/>
    </svg>

    <?php if ($topline !== ''): ?>
      <div class="pb-doc-topline"><?= e($topline) ?></div>
    <?php endif; ?>

    <?php foreach (preg_split('/\n\s*\n/', $body) ?: [] as $paragraph): ?>
      <?php if (trim($paragraph) === '') { continue; } ?>
      <p class="pb-doc-text"><?= Content::expand(trim($paragraph)) ?></p>
    <?php endforeach; ?>

    <?php if ($note !== ''): ?>
      <div class="pb-doc-note"><?= e($note) ?> ✓</div>
    <?php endif; ?>

    <?php if ($stamp !== ''): ?>
      <div class="pb-doc-stamp"><?= implode('<br>', array_map('eb_e', array_map('trim', explode(',', $stamp)))) ?></div>
    <?php endif; ?>
  </div>

  <?php if ($sticky !== ''): ?>
    <div class="pb-doc-sticky"><?= e($sticky) ?><span><?= e($stickyValue) ?></span></div>
  <?php endif; ?>
</div>
