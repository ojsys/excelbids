<?php
/** @var array<string,mixed> $settings */

use App\Core\BlockRenderer as R;

// Only recognised YouTube, Vimeo and Google Maps links produce an iframe.
$url = R::embedUrl(R::get($settings, 'url'));
if ($url === null) {
    return;
}

$ratio = R::get($settings, 'ratio', '16x9');
$title = trim(R::get($settings, 'title')) ?: 'Embedded content';
?>
<div class="pb-block pb-embed pb-embed-<?= e($ratio) ?>">
  <iframe src="<?= e($url) ?>" title="<?= e($title) ?>" loading="lazy"
          referrerpolicy="strict-origin-when-cross-origin"
          allow="accelerometer; clipboard-write; encrypted-media; picture-in-picture"
          allowfullscreen></iframe>
</div>
