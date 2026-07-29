<?php
/**
 * Raw HTML, already reduced to a safe subset by Content::sanitizeHtml() on save.
 *
 * @var array<string,mixed> $settings
 */

$code = (string) ($settings['code'] ?? '');
if (trim($code) === '') {
    return;
}
?>
<div class="pb-block pb-html"><?= $code ?></div>
