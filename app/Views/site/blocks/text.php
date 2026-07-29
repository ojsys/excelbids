<?php
/** @var array<string,mixed> $settings */

use App\Core\BlockRenderer as R;

// Already sanitised on save by Content::sanitizeHtml().
$body = (string) ($settings['body'] ?? '');
if (trim(strip_tags($body)) === '') {
    return;
}

$size = R::get($settings, 'size', 'normal');
?>
<div class="pb-block pb-text pb-text-<?= e($size) ?>"><?= $body ?></div>
