<?php
/** @var array<string,mixed> $enquiry */

$row = static function (string $label, ?string $value): void {
    if ($value === null || trim($value) === '') {
        return;
    }
    ?>
    <tr>
      <td style="padding:7px 0;width:150px;color:#5B584C;font-size:13px;vertical-align:top;"><?= e($label) ?></td>
      <td style="padding:7px 0;font-size:14px;color:#1B1B17;"><?= e($value) ?></td>
    </tr>
    <?php
};
?>

<p style="margin:0 0 6px;font-family:monospace;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#B23A2E;">
  New consultation request
</p>
<h1 style="margin:0 0 4px;font-family:Georgia,serif;font-size:22px;font-weight:600;color:#1B1B17;">
  <?= e((string) ($enquiry['organisation'] !== '' ? $enquiry['organisation'] : $enquiry['name'])) ?>
</h1>
<p style="margin:0 0 22px;font-family:monospace;font-size:12px;color:#5B584C;">
  Reference <?= e((string) $enquiry['reference']) ?> · <?= e(date('j M Y, H:i', strtotime((string) $enquiry['created_at']))) ?>
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #DFDACB;border-bottom:1px solid #DFDACB;margin-bottom:22px;">
  <?php
    $row('Contact', (string) $enquiry['name']);
    $row('Email', (string) $enquiry['email']);
    $row('Phone', (string) $enquiry['phone']);
    $row('Service needed', (string) $enquiry['service']);
    $row('Sector', (string) $enquiry['sector']);
    $row('Their deadline', $enquiry['deadline'] ? date('j M Y', strtotime((string) $enquiry['deadline'])) : null);
  ?>
</table>

<p style="margin:0 0 6px;font-weight:600;font-size:13px;color:#1B1B17;">About the opportunity</p>
<div style="background:#FBFAF5;border:1px solid #DFDACB;border-radius:3px;padding:16px;font-size:14px;line-height:1.65;color:#1B1B17;white-space:pre-wrap;"><?= e((string) $enquiry['message']) ?></div>

<p style="margin:26px 0 0;">
  <a href="<?= e(url('admin/enquiries/' . $enquiry['id'])) ?>"
     style="display:inline-block;background:#B23A2E;color:#ffffff;padding:12px 22px;border-radius:3px;text-decoration:none;font-weight:600;font-size:14px;">
    Open in the admin panel
  </a>
</p>

<p style="margin:18px 0 0;font-size:12.5px;color:#5B584C;">
  Reply directly to this email to reach <?= e((string) $enquiry['name']) ?>.
</p>
