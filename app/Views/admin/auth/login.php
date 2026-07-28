<?php

use App\Core\Flash;

$errors = Flash::errors();
?>
<div class="auth-box">
  <h1>Sign in</h1>
  <p class="lead">Access the ExcelBids bid management system.</p>

  <form method="post" action="<?= e(path('admin/login')) ?>" data-guard-submit>
    <?= csrf_field() ?>

    <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>">
      <label for="email">Email address</label>
      <input class="input" type="email" id="email" name="email" value="<?= e((string) old('email')) ?>"
             required autocomplete="username" autofocus>
      <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
    </div>

    <div class="field<?= isset($errors['password']) ? ' has-error' : '' ?>">
      <label for="password">Password</label>
      <input class="input" type="password" id="password" name="password" required autocomplete="current-password">
      <?php if (isset($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
    </div>

    <button type="submit" class="btn btn-red btn-block u-mt">Sign in</button>
  </form>

  <div class="auth-foot">
    <a href="<?= e(path('admin/forgot-password')) ?>">Forgotten your password?</a>
  </div>
</div>

<div class="auth-foot">
  <a href="<?= e(path('/')) ?>">← Back to the public site</a>
</div>
