<?php
/** @var array<string,mixed> $settings */

use App\Core\BlockRenderer as R;

$text = trim(R::get($settings, 'text'));
if ($text === '') {
    return;
}

$level = R::get($settings, 'level', 'h2');
$level = in_array($level, ['h2', 'h3', 'h4'], true) ? $level : 'h2';
$eyebrow = trim(R::get($settings, 'eyebrow'));
$align = R::get($settings, 'align', 'left');
?>
<div class="pb-block pb-heading pb-align-<?= e($align) ?>">
  <?php if ($eyebrow !== ''): ?><div class="eyebrow"><?= e($eyebrow) ?></div><?php endif; ?>
  <<?= $level ?> class="pb-h pb-<?= e($level) ?>"><?= e($text) ?></<?= $level ?>>
</div>
