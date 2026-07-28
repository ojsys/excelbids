<?php
/**
 * @var string $token
 * @var string $name
 * @var string $organisation
 */

use App\Core\Flash;

$errors = Flash::errors();
?>
<div class="auth-box">
  <h1>Set up your account</h1>
  <p class="lead">
    Welcome, <?= e($name) ?>. Choose a password to activate the
    <strong><?= e($organisation) ?></strong> portal account.
  </p>

  <form method="post" action="<?= e(path('portal/activate/' . $token)) ?>" data-guard-submit>
    <?= csrf_field() ?>

    <div class="field<?= isset($errors['password']) ? ' has-error' : '' ?>">
      <label for="password">Choose a password</label>
      <input class="input" type="password" id="password" name="password" required autocomplete="new-password" autofocus>
      <span class="help">At least 10 characters. A memorable phrase works well.</span>
      <?php if (isset($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
    </div>

    <div class="field">
      <label for="password_confirm">Confirm password</label>
      <input class="input" type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
    </div>

    <button type="submit" class="btn btn-red btn-block u-mt">Activate my account</button>
  </form>
</div>
