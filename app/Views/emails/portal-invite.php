<?php
/**
 * @var array<string,mixed> $user
 * @var string              $link
 * @var string              $organisation
 */
?>

<h1 style="margin:0 0 14px;font-family:Georgia,serif;font-size:22px;font-weight:600;color:#1B1B17;">
  Your ExcelBids client portal is ready.
</h1>

<p style="margin:0 0 16px;">Hello <?= e((string) $user['name']) ?>,</p>

<p style="margin:0 0 16px;">
  We have set up a portal account for <strong><?= e($organisation) ?></strong>. From there you can follow
  every bid we are working on, see the deadlines, download and upload documents, and message the team directly.
</p>

<p style="margin:0 0 24px;">
  <a href="<?= e($link) ?>"
     style="display:inline-block;background:#B23A2E;color:#ffffff;padding:13px 26px;border-radius:3px;text-decoration:none;font-weight:600;font-size:15px;">
    Choose your password
  </a>
</p>

<p style="margin:0 0 16px;font-size:13.5px;color:#5B584C;">
  This link is valid for seven days. If it expires, ask us and we will send a fresh one.
</p>

<p style="margin:0;font-size:12.5px;color:#5B584C;word-break:break-all;">
  If the button does not work, copy this address into your browser:<br>
  <span style="font-family:monospace;"><?= e($link) ?></span>
</p>
