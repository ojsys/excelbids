<?php
/**
 * A working enquiry form. Posts to /forms/{blockId}, which validates and files
 * the submission as a consultation request.
 *
 * @var array<string,mixed> $settings
 * @var array<string,mixed> $block
 */

use App\Core\BlockRenderer as R;
use App\Core\Database;
use App\Core\Flash;

$blockId = (int) $block['id'];
$errors = Flash::errors();
$heading = trim(R::get($settings, 'heading'));
$text = trim(R::get($settings, 'text'));
$buttonLabel = trim(R::get($settings, 'button_label', 'Send enquiry'));
$messageLabel = trim(R::get($settings, 'message_label', 'How can we help?'));

// Only show the success panel for the form that was actually submitted.
$submitted = (int) ($_SESSION['_form_success_block'] ?? 0) === $blockId;
if ($submitted) {
    unset($_SESSION['_form_success_block']);
}

$showOrg      = R::bool($settings, 'show_org', true);
$showPhone    = R::bool($settings, 'show_phone', true);
$showService  = R::bool($settings, 'show_service', true);
$showSector   = R::bool($settings, 'show_sector', false);
$showDeadline = R::bool($settings, 'show_deadline', false);

$services = $showService
    ? Database::all('SELECT title FROM services WHERE is_active = 1 ORDER BY sort_order, id')
    : [];
$sectors = $showSector
    ? Database::all('SELECT name FROM sectors WHERE is_active = 1 ORDER BY sort_order, id')
    : [];

$field = static function (string $name, string $label, string $type = 'text', bool $required = false) use ($errors): void {
    $error = $errors[$name] ?? null;
    ?>
    <div class="field<?= $error ? ' has-error' : '' ?>">
      <label for="f<?= e($name) ?>"><?= e($label) ?><?= $required ? ' <span class="req">*</span>' : '' ?></label>
      <input class="input" type="<?= e($type) ?>" id="f<?= e($name) ?>" name="<?= e($name) ?>"
             value="<?= e((string) old($name)) ?>" <?= $required ? 'required' : '' ?>
             <?= $type === 'date' ? 'data-min-today' : '' ?>>
      <?php if ($error): ?><span class="field-error"><?= e($error) ?></span><?php endif; ?>
    </div>
    <?php
};
?>
<div class="pb-block pb-form" id="form-<?= (int) $blockId ?>">
  <?php if ($submitted): ?>
    <div class="pb-form-success">
      <div class="confirm-stamp" aria-hidden="true">MESSAGE<br>RECEIVED</div>
      <p><?= nl2br(e(R::get($settings, 'success', 'Thank you — your message has been received. We will be in touch shortly.'))) ?></p>
    </div>
  <?php else: ?>
    <?php if ($heading !== ''): ?><h3 class="pb-form-heading"><?= e($heading) ?></h3><?php endif; ?>
    <?php if ($text !== ''): ?><p class="pb-form-intro"><?= nl2br(e($text)) ?></p><?php endif; ?>

    <form method="post" action="<?= e(path('forms/' . $blockId)) ?>#form-<?= (int) $blockId ?>" data-guard-submit novalidate>
      <?= csrf_field() ?>

      <!-- Spam trap: hidden from people, irresistible to bots. -->
      <div class="honeypot" aria-hidden="true">
        <label for="website_url<?= (int) $blockId ?>">Leave this blank</label>
        <input type="text" id="website_url<?= (int) $blockId ?>" name="website_url" tabindex="-1" autocomplete="off">
      </div>

      <div class="field-row">
        <?php $field('name', 'Your name', 'text', true); ?>
        <?php if ($showOrg) { $field('organisation', 'Organisation'); } ?>
      </div>

      <div class="field-row">
        <?php $field('email', 'Email address', 'email', true); ?>
        <?php if ($showPhone) { $field('phone', 'Phone number', 'tel'); } ?>
      </div>

      <?php if ($showService || $showSector): ?>
        <div class="field-row">
          <?php if ($showService): ?>
            <div class="field">
              <label for="fservice">What do you need?</label>
              <select class="select" id="fservice" name="service">
                <option value="">Not sure yet</option>
                <?php foreach ($services as $service): ?>
                  <option value="<?= e((string) $service['title']) ?>"<?= old('service') === $service['title'] ? ' selected' : '' ?>>
                    <?= e((string) $service['title']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <?php if ($showSector): ?>
            <div class="field">
              <label for="fsector">Your sector</label>
              <select class="select" id="fsector" name="sector">
                <option value="">Please choose</option>
                <?php foreach ($sectors as $sector): ?>
                  <option value="<?= e((string) $sector['name']) ?>"<?= old('sector') === $sector['name'] ? ' selected' : '' ?>>
                    <?= e((string) $sector['name']) ?>
                  </option>
                <?php endforeach; ?>
                <option value="Other"<?= old('sector') === 'Other' ? ' selected' : '' ?>>Other</option>
              </select>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($showDeadline) { $field('deadline', 'Submission deadline', 'date'); } ?>

      <div class="field<?= isset($errors['message']) ? ' has-error' : '' ?>">
        <label for="fmessage"><?= e($messageLabel) ?> <span class="req">*</span></label>
        <textarea class="textarea" id="fmessage" name="message" required><?= e((string) old('message')) ?></textarea>
        <?php if (isset($errors['message'])): ?><span class="field-error"><?= e($errors['message']) ?></span><?php endif; ?>
      </div>

      <div class="field<?= isset($errors['consent']) ? ' has-error' : '' ?>">
        <label class="checkline">
          <input type="checkbox" name="consent" value="1" required<?= old('consent') ? ' checked' : '' ?>>
          <span>I agree to ExcelBids storing these details in order to respond to my enquiry.</span>
        </label>
        <?php if (isset($errors['consent'])): ?><span class="field-error"><?= e($errors['consent']) ?></span><?php endif; ?>
      </div>

      <button type="submit" class="btn btn-red"><?= e($buttonLabel) ?></button>
    </form>
  <?php endif; ?>
</div>
