<?php
/**
 * Sent to a client's portal users when a bid reaches a milestone.
 *
 * @var array<string,mixed> $bid
 * @var string              $name
 * @var string              $headline
 * @var string              $body
 */
?>

<p style="margin:0 0 6px;font-family:monospace;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#B23A2E;">
  Bid update
</p>

<h1 style="margin:0 0 4px;font-family:Georgia,serif;font-size:21px;font-weight:600;color:#1B1B17;">
  <?= e($headline) ?>
</h1>

<p style="margin:0 0 22px;font-family:monospace;font-size:12px;color:#5B584C;">
  <?= e((string) $bid['reference']) ?> · <?= e((string) $bid['title']) ?>
</p>

<p style="margin:0 0 16px;">Hello <?= e($name) ?>,</p>

<div style="margin:0 0 22px;font-size:14.5px;line-height:1.65;color:#1B1B17;white-space:pre-wrap;"><?= e($body) ?></div>

<?php if (!empty($bid['submission_due'])): ?>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #DFDACB;border-bottom:1px solid #DFDACB;margin-bottom:22px;">
    <tr>
      <td style="padding:10px 0;width:150px;color:#5B584C;font-size:13px;">Submission deadline</td>
      <td style="padding:10px 0;font-size:14px;color:#1B1B17;font-weight:600;">
        <?= e(date('j M Y, H:i', strtotime((string) $bid['submission_due']))) ?>
      </td>
    </tr>
  </table>
<?php endif; ?>

<p style="margin:0 0 18px;">
  <a href="<?= e(url('portal/bids/' . $bid['id'])) ?>"
     style="display:inline-block;background:#1B2A47;color:#ffffff;padding:12px 22px;border-radius:3px;text-decoration:none;font-weight:600;font-size:14px;">
    View this bid in your portal
  </a>
</p>
