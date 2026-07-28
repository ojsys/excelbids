<div class="auth-box">
  <h1>Client sign in</h1>
  <p class="lead">Track your bids, deadlines and documents in one place.</p>

  <form method="post" action="<?= e(path('portal/login')) ?>" data-guard-submit>
    <?= csrf_field() ?>

    <div class="field">
      <label for="email">Email address</label>
      <input class="input" type="email" id="email" name="email" value="<?= e((string) old('email')) ?>"
             required autocomplete="username" autofocus>
    </div>

    <div class="field">
      <label for="password">Password</label>
      <input class="input" type="password" id="password" name="password" required autocomplete="current-password">
    </div>

    <button type="submit" class="btn btn-red btn-block u-mt">Sign in</button>
  </form>

  <div class="auth-foot">
    <a href="<?= e(path('portal/forgot-password')) ?>">Forgotten your password?</a>
  </div>
</div>

<div class="auth-foot">
  Not a client yet? <a href="<?= e(path('consultation')) ?>">Request a consultation</a>
  <br><br>
  <a href="<?= e(path('/')) ?>">← Back to the website</a>
</div>
