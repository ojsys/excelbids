<?php
/** @var array<string,mixed> $enquiry */
?>

<h1 style="margin:0 0 14px;font-family:Georgia,serif;font-size:22px;font-weight:600;color:#1B1B17;">
  Thank you — we have your request.
</h1>

<p style="margin:0 0 16px;">Hello <?= e((string) $enquiry['name']) ?>,</p>

<p style="margin:0 0 16px;">
  We have received your consultation request and one of our bid writers will come back to you,
  usually within one working day. Your reference is below — please quote it if you need to add anything.
</p>

<p style="margin:0 0 22px;font-family:monospace;font-size:13px;color:#1B1B17;background:#FBFAF5;border:1px dashed #DFDACB;padding:12px 16px;display:inline-block;">
  REFERENCE — <?= e((string) $enquiry['reference']) ?>
</p>

<p style="margin:0 0 8px;font-weight:600;font-size:13px;color:#1B1B17;">What you told us</p>
<div style="background:#FBFAF5;border:1px solid #DFDACB;border-radius:3px;padding:16px;font-size:14px;line-height:1.65;color:#5B584C;white-space:pre-wrap;margin-bottom:22px;"><?= e((string) $enquiry['message']) ?></div>

<p style="margin:0 0 16px;">
  Everything you share with us is treated as confidential, and an NDA is put in place before any
  tender material changes hands.
</p>

<p style="margin:0;color:#5B584C;font-size:13.5px;">
  This is an automated confirmation — there is no need to reply to it, but you can if you want to add anything.
</p>
