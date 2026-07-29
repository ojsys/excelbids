<?php
/**
 * A section: the full-width band that every other block sits inside.
 *
 * @var array<string,mixed>  $settings
 * @var array<int,string>    $columns  Already-rendered HTML per column
 */

use App\Core\BlockRenderer as R;
use App\Core\Blocks;

$background = R::get($settings, 'background', 'paper');
$width      = R::get($settings, 'width', 'normal');
$layout     = R::get($settings, 'columns', '1');
$alignItems = R::get($settings, 'align_items', 'start');
$anchor     = trim(R::get($settings, 'anchor'));
$ghost      = trim(R::get($settings, 'ghost_num'));

$eyebrow  = trim(R::get($settings, 'eyebrow'));
$fileNum  = trim(R::get($settings, 'file_num'));
$heading  = trim(R::get($settings, 'heading'));
$intro    = trim(R::get($settings, 'intro'));
$hasHead  = $eyebrow !== '' || $fileNum !== '' || $heading !== '' || $intro !== '';

$isDark = str_starts_with($background, 'navy');

$classes = [
    'pb-section',
    'pb-bg-' . str_replace('_', '-', $background),
    'pb-pt-' . R::spacing(R::get($settings, 'padding_top', 'normal')),
    'pb-pb-' . R::spacing(R::get($settings, 'padding_bottom', 'normal')),
];

$gridClass = 'pb-cols pb-cols-' . $layout . ' pb-align-' . $alignItems;
$hasContent = trim(implode('', $columns)) !== '';
?>
<section class="<?= e(implode(' ', $classes)) ?>"<?= $anchor !== '' ? ' id="' . e($anchor) . '"' : '' ?>>
  <?php if ($ghost !== ''): ?>
    <div class="ghost-num" aria-hidden="true"><?= e($ghost) ?></div>
  <?php endif; ?>

  <div class="wrap pb-w-<?= e($width) ?>">
    <?php if ($hasHead): ?>
      <div class="pb-section-head<?= $isDark ? ' on-dark' : '' ?>">
        <?php if ($fileNum !== ''): ?><div class="file-num mono"><?= e($fileNum) ?></div><?php endif; ?>
        <?php if ($eyebrow !== ''): ?><div class="eyebrow"><?= e($eyebrow) ?></div><?php endif; ?>
        <?php if ($heading !== ''): ?><h2><?= e($heading) ?></h2><?php endif; ?>
        <?php if ($intro !== ''): ?><p><?= nl2br(e($intro)) ?></p><?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($hasContent): ?>
      <?php if (Blocks::columnCount($layout) > 1): ?>
        <div class="<?= e($gridClass) ?>">
          <?php foreach ($columns as $html): ?>
            <div class="pb-col"><?= $html ?></div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="pb-col"><?= $columns[0] ?? '' ?></div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
