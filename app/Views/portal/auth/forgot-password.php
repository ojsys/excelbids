<div class="auth-box">
  <h1>Forgotten password</h1>
  <p class="lead">Enter the email address you use to sign in and we will send you a reset link.</p>

  <form method="post" action="<?= e(path('portal/forgot-password')) ?>" data-guard-submit>
    <?= csrf_field() ?>

    <div class="field">
      <label for="email">Email address</label>
      <input class="input" type="email" id="email" name="email" required autocomplete="username" autofocus>
    </div>

    <button type="submit" class="btn btn-red btn-block u-mt">Send reset link</button>
  </form>

  <div class="auth-foot">
    <a href="<?= e(path('portal/login')) ?>">← Back to sign in</a>
  </div>
</div>
