<?php
/**
 * Email layout. Table-based with inline styles, because that is still what
 * Outlook and Gmail render reliably.
 *
 * @var string $content
 */

use App\Core\Settings;

$siteName = Settings::get('site_name', 'ExcelBids');
$contact = Settings::get('contact_email', '');
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e((string) $siteName) ?></title>
</head>
<body style="margin:0;padding:0;background:#FBFAF5;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FBFAF5;padding:28px 12px;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #DFDACB;border-radius:4px;">

        <tr>
          <td style="background:#0F1826;padding:20px 28px;">
            <span style="font-family:Georgia,serif;font-size:20px;font-weight:700;color:#ffffff;letter-spacing:-0.01em;">
              Excel<span style="color:#B23A2E;">Bids</span>
            </span>
          </td>
        </tr>

        <tr>
          <td style="padding:30px 28px;font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:1.65;color:#1B1B17;">
            <?= $content ?>
          </td>
        </tr>

        <tr>
          <td style="background:#F4F2EA;border-top:1px solid #DFDACB;padding:18px 28px;font-family:Helvetica,Arial,sans-serif;font-size:12px;color:#5B584C;line-height:1.6;">
            <?= e((string) $siteName) ?> — tender, bid &amp; grant writing consultancy.<br>
            <?php if ($contact): ?>
              <a href="mailto:<?= e($contact) ?>" style="color:#B23A2E;text-decoration:none;"><?= e($contact) ?></a>
            <?php endif; ?>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
