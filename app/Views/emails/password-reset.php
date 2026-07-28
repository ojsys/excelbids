<?php
/**
 * @var string $name
 * @var string $link
 * @var int    $expiresMinutes
 */
?>

<h1 style="margin:0 0 14px;font-family:Georgia,serif;font-size:22px;font-weight:600;color:#1B1B17;">
  Reset your password
</h1>

<p style="margin:0 0 16px;">Hello <?= e($name) ?>,</p>

<p style="margin:0 0 16px;">
  We received a request to reset the password on your ExcelBids account. Use the button below to choose a new one.
</p>

<p style="margin:0 0 24px;">
  <a href="<?= e($link) ?>"
     style="display:inline-block;background:#B23A2E;color:#ffffff;padding:13px 26px;border-radius:3px;text-decoration:none;font-weight:600;font-size:15px;">
    Choose a new password
  </a>
</p>

<p style="margin:0 0 16px;font-size:13.5px;color:#5B584C;">
  This link expires in <?= (int) $expiresMinutes ?> minutes and can only be used once.
</p>

<p style="margin:0 0 16px;font-size:13.5px;color:#5B584C;">
  <strong>If you did not ask for this</strong>, you can ignore this email — your password has not changed.
</p>

<p style="margin:0;font-size:12.5px;color:#5B584C;word-break:break-all;">
  If the button does not work, copy this address into your browser:<br>
  <span style="font-family:monospace;"><?= e($link) ?></span>
</p>
