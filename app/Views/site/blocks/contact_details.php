<?php
/** @var array<string,mixed> $settings */

use App\Core\BlockRenderer as R;
use App\Core\Settings;

// Fall back to the values in Settings so contact details live in one place.
$email   = trim(R::get($settings, 'email')) ?: (string) Settings::get('contact_email', '');
$phone   = trim(R::get($settings, 'phone')) ?: (string) Settings::get('contact_phone', '');
$address = trim(R::get($settings, 'address')) ?: (string) Settings::get('contact_location', '');
$hours   = trim(R::get($settings, 'hours'));
$heading = trim(R::get($settings, 'heading'));
$text    = trim(R::get($settings, 'text'));
$style   = R::get($settings, 'style', 'panel');

if ($email === '' && $phone === '' && $address === '' && $heading === '') {
    return;
}
?>
<div class="pb-block pb-contact pb-contact-<?= e($style) ?>">
  <?php if ($heading !== ''): ?><h3><?= e($heading) ?></h3><?php endif; ?>
  <?php if ($text !== ''): ?><p class="pb-contact-intro"><?= nl2br(e($text)) ?></p><?php endif; ?>

  <dl class="pb-contact-list">
    <?php if ($email !== ''): ?>
      <div><dt>Email</dt><dd><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></dd></div>
    <?php endif; ?>
    <?php if ($phone !== ''): ?>
      <div><dt>Phone</dt><dd><a href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a></dd></div>
    <?php endif; ?>
    <?php if ($address !== ''): ?>
      <div><dt>Address</dt><dd><?= nl2br(e($address)) ?></dd></div>
    <?php endif; ?>
    <?php if ($hours !== ''): ?>
      <div><dt>Hours</dt><dd><?= e($hours) ?></dd></div>
    <?php endif; ?>
  </dl>
</div>
