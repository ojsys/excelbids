<?php
/**
 * @var string $name
 * @var string $senderName
 * @var string $body
 * @var string $link
 */
?>

<p style="margin:0 0 6px;font-family:monospace;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#B23A2E;">
  New message
</p>

<h1 style="margin:0 0 18px;font-family:Georgia,serif;font-size:21px;font-weight:600;color:#1B1B17;">
  <?= e($senderName) ?> has sent you a message
</h1>

<p style="margin:0 0 16px;">Hello <?= e($name) ?>,</p>

<div style="background:#FBFAF5;border-left:3px solid #B23A2E;padding:16px 18px;font-size:14.5px;line-height:1.65;color:#1B1B17;white-space:pre-wrap;margin-bottom:22px;"><?= e($body) ?></div>

<p style="margin:0 0 18px;">
  <a href="<?= e($link) ?>"
     style="display:inline-block;background:#B23A2E;color:#ffffff;padding:12px 22px;border-radius:3px;text-decoration:none;font-weight:600;font-size:14px;">
    Reply in the portal
  </a>
</p>

<p style="margin:0;font-size:12.5px;color:#5B584C;">
  Please reply through the portal rather than by email, so the whole conversation stays in one place.
</p>
