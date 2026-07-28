<?php

use App\Core\Content;
use App\Core\Settings;

$email = Settings::get('contact_email', '');
$portalOn = Settings::bool('portal_enabled', true);
?>
<section id="quote" style="padding-top:0;">
  <div class="wrap">
    <div class="cta-wrap">
      <div class="cta-inner">
        <div>
          <h2><?= e(block('cta_heading')) ?></h2>
          <?php if ($sub = block('cta_sub')): ?><p class="sub"><?= e($sub) ?></p><?php endif; ?>
          <?php if ($email): ?><a class="cta-email mono" href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif; ?>
        </div>
        <div class="cta-actions">
          <?php if ($note = block('cta_note')): ?>
            <div class="point-note hand"><?= e($note) ?> &rarr;</div>
          <?php endif; ?>
          <a href="<?= e(path('consultation')) ?>" class="btn btn-red"><?= e(block('cta_btn_primary', 'Submit a Consultation Request')) ?></a>
          <?php if ($portalOn): ?>
            <a href="<?= e(path('portal/login')) ?>" class="btn btn-ghost-light"><?= e(block('cta_btn_secondary', 'Log in to Client Portal')) ?></a>
          <?php endif; ?>
        </div>
      </div>
      <?php if (block('cta_stamp') !== ''): ?>
        <div class="approved" aria-hidden="true"><?= Content::lines('cta_stamp') ?></div>
      <?php endif; ?>
    </div>
  </div>
</section>
