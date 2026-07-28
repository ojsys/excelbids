<?php
/**
 * @var array<int,array<string,mixed>> $services
 * @var array<int,array<string,mixed>> $sectors
 */

use App\Core\Flash;
use App\Core\Settings;

$errors = Flash::errors();
$email = Settings::get('contact_email', '');
$phone = Settings::get('contact_phone', '');

/** Render one form field with its error state. */
$field = static function (string $name, string $label, string $type = 'text', bool $required = false, string $help = '') use ($errors): void {
    $error = $errors[$name] ?? null;
    ?>
    <div class="field<?= $error ? ' has-error' : '' ?>">
      <label for="f-<?= e($name) ?>"><?= e($label) ?><?= $required ? ' <span class="req">*</span>' : '' ?></label>
      <input class="input" type="<?= e($type) ?>" id="f-<?= e($name) ?>" name="<?= e($name) ?>"
             value="<?= e((string) old($name)) ?>"
             <?= $required ? 'required' : '' ?>
             <?= $type === 'date' ? 'data-min-today' : '' ?>
             <?= $error ? 'aria-invalid="true"' : '' ?>>
      <?php if ($help !== ''): ?><span class="help"><?= e($help) ?></span><?php endif; ?>
      <?php if ($error): ?><span class="field-error"><?= e($error) ?></span><?php endif; ?>
    </div>
    <?php
};
?>

<div class="page-head">
  <div class="wrap">
    <div class="eyebrow">Consultation Request</div>
    <h1 style="margin-top:8px;"><?= e(block('quote_heading', 'Submit a Consultation Request')) ?></h1>
    <p><?= e(block('quote_intro')) ?></p>
  </div>
</div>

<section class="form-page" style="padding-top:24px;">
  <div class="wrap form-shell">

    <div class="form-card">
      <form method="post" action="<?= e(path('consultation')) ?>" data-guard-submit novalidate>
        <?= csrf_field() ?>

        <!-- Spam trap. Left in the DOM, hidden from people. -->
        <div class="honeypot" aria-hidden="true">
          <label for="website_url">Leave this blank</label>
          <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off">
        </div>

        <div class="field-row">
          <?php $field('name', 'Your name', 'text', true); ?>
          <?php $field('organisation', 'Organisation'); ?>
        </div>

        <div class="field-row">
          <?php $field('email', 'Email address', 'email', true); ?>
          <?php $field('phone', 'Phone number', 'tel'); ?>
        </div>

        <div class="field-row">
          <div class="field<?= isset($errors['service']) ? ' has-error' : '' ?>">
            <label for="f-service">What do you need?</label>
            <select class="select" id="f-service" name="service">
              <option value="">Not sure yet</option>
              <?php foreach ($services as $service): ?>
                <option value="<?= e($service['title']) ?>"<?= old('service') === $service['title'] ? ' selected' : '' ?>>
                  <?= e($service['title']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['service'])): ?><span class="field-error"><?= e($errors['service']) ?></span><?php endif; ?>
          </div>

          <div class="field<?= isset($errors['sector']) ? ' has-error' : '' ?>">
            <label for="f-sector">Your sector</label>
            <select class="select" id="f-sector" name="sector">
              <option value="">Please choose</option>
              <?php foreach ($sectors as $sector): ?>
                <option value="<?= e($sector['name']) ?>"<?= old('sector') === $sector['name'] ? ' selected' : '' ?>>
                  <?= e($sector['name']) ?>
                </option>
              <?php endforeach; ?>
              <option value="Other"<?= old('sector') === 'Other' ? ' selected' : '' ?>>Other</option>
            </select>
            <?php if (isset($errors['sector'])): ?><span class="field-error"><?= e($errors['sector']) ?></span><?php endif; ?>
          </div>
        </div>

        <?php $field('deadline', 'Submission deadline', 'date', false, 'If you already know it. Short turnarounds are fine — tell us anyway.'); ?>

        <div class="field<?= isset($errors['message']) ? ' has-error' : '' ?>">
          <label for="f-message">About the opportunity <span class="req">*</span></label>
          <textarea class="textarea" id="f-message" name="message" required
                    placeholder="What is the contract, who is the buyer, and where are you up to?"><?= e((string) old('message')) ?></textarea>
          <?php if (isset($errors['message'])): ?><span class="field-error"><?= e($errors['message']) ?></span><?php endif; ?>
        </div>

        <div class="field<?= isset($errors['consent']) ? ' has-error' : '' ?>">
          <label class="checkline">
            <input type="checkbox" name="consent" value="1" required<?= old('consent') ? ' checked' : '' ?>>
            <span>I agree to ExcelBids storing these details in order to respond to my request. Every engagement is covered by an NDA.</span>
          </label>
          <?php if (isset($errors['consent'])): ?><span class="field-error"><?= e($errors['consent']) ?></span><?php endif; ?>
        </div>

        <button type="submit" class="btn btn-red btn-block" style="margin-top:6px;">Send Consultation Request</button>
      </form>
    </div>

    <aside class="aside-panel">
      <div class="eyebrow" style="color:var(--gold);">What happens next</div>
      <h3 style="margin-top:8px;">A reply within one working day.</h3>
      <p>We read the opportunity, tell you honestly whether it is worth pursuing, and quote a fixed fee before any work starts.</p>

      <ul class="aside-list">
        <li><span class="tick">✓</span><span>We review the ITT and the scoring criteria</span></li>
        <li><span class="tick">✓</span><span>You get a scope, a fee and a delivery plan</span></li>
        <li><span class="tick">✓</span><span>An NDA is in place before anything is shared</span></li>
        <li><span class="tick">✓</span><span>No obligation if the timing is not right</span></li>
      </ul>

      <div class="aside-contact">
        <?php if ($email): ?><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><br><?php endif; ?>
        <?php if ($phone): ?><a href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a><?php endif; ?>
      </div>
    </aside>

  </div>
</section>
