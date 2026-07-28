<?php /** @var string $reference */ ?>
<section class="form-page">
  <div class="wrap">
    <div class="confirm-card">
      <div class="confirm-stamp" aria-hidden="true">REQUEST<br>RECEIVED</div>
      <h1>Thank you — we have your request.</h1>
      <p><?= e(block('quote_success')) ?></p>
      <div class="confirm-ref">YOUR REFERENCE — <?= e($reference) ?></div>
      <div style="margin-top:32px; display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
        <a href="<?= e(path('/')) ?>" class="btn btn-primary">Back to the homepage</a>
        <a href="<?= e(path('services')) ?>" class="btn btn-ghost">See what we do</a>
      </div>
    </div>
  </div>
</section>
