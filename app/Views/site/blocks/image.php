<?php
/** @var array<string,mixed> $settings */

use App\Core\BlockRenderer as R;
use App\Models\Media;

$mediaId = (int) R::get($settings, 'media_id', '0');
$url = Media::url($mediaId);
if ($url === null) {
    return;
}

$media = Media::find($mediaId);
$alt = trim(R::get($settings, 'alt')) ?: (string) ($media['alt_text'] ?? '');
$caption = trim(R::get($settings, 'caption'));
$style = R::get($settings, 'style', 'plain');
$align = R::get($settings, 'align', 'left');
$maxWidth = (int) R::get($settings, 'max_width', '0');

// Intrinsic size prevents the page jumping as images load.
$width = $media['width'] ?? null;
$height = $media['height'] ?? null;
?>
<figure class="pb-block pb-image pb-image-<?= e($style) ?> pb-align-<?= e($align) ?>"
        <?= $maxWidth > 0 ? 'style="max-width:' . (int) $maxWidth . 'px"' : '' ?>>
  <img src="<?= e($url) ?>" alt="<?= e($alt) ?>" loading="lazy" decoding="async"
       <?= $width ? 'width="' . (int) $width . '"' : '' ?>
       <?= $height ? 'height="' . (int) $height . '"' : '' ?>>
  <?php if ($caption !== ''): ?>
    <figcaption><?= e($caption) ?></figcaption>
  <?php endif; ?>
</figure>
