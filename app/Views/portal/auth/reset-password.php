<?php
/**
 * @var string $token
 * @var string $name
 */

use App\Core\Flash;

$errors = Flash::errors();
?>
<div class="auth-box">
  <h1>Choose a new password</h1>
  <p class="lead">Hello <?= e($name) ?> — pick a password of at least 10 characters.</p>

  <form method="post" action="<?= e(path('portal/reset-password/' . $token)) ?>" data-guard-submit>
    <?= csrf_field() ?>

    <div class="field<?= isset($errors['password']) ? ' has-error' : '' ?>">
      <label for="password">New password</label>
      <input class="input" type="password" id="password" name="password" required autocomplete="new-password" autofocus>
      <?php if (isset($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
    </div>

    <div class="field">
      <label for="password_confirm">Confirm new password</label>
      <input class="input" type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
    </div>

    <button type="submit" class="btn btn-red btn-block u-mt">Save new password</button>
  </form>
</div>
