<?php
/** @var array<string,mixed> $user */

use App\Core\Auth;
use App\Core\Flash;

$errors = Flash::errors();
?>

<div class="grid grid-side content-narrow" style="max-width:900px;">

  <section class="card">
    <div class="card-head"><h3>Your account</h3></div>
    <div class="card-body tight">
      <div class="u-flex u-mb">
        <span class="avatar" style="width:44px;height:44px;font-size:15px;"><?= e(initials((string) $user['name'])) ?></span>
        <div>
          <strong><?= e((string) $user['name']) ?></strong>
          <div class="u-small u-faint"><?= e(labelize((string) $user['role'])) ?></div>
        </div>
      </div>
      <dl class="dl">
        <dt>Last signed in</dt><dd><?= e(fdatetime((string) ($user['last_login_at'] ?? ''))) ?></dd>
        <dt>From</dt><dd class="u-mono u-small"><?= e((string) ($user['last_login_ip'] ?? '—')) ?></dd>
        <dt>Account created</dt><dd><?= e(fdate((string) $user['created_at'])) ?></dd>
      </dl>
      <p class="u-small u-faint u-mt">
        Your role controls what you can see and change. Only an administrator can change roles.
      </p>
    </div>
  </section>

  <div>
    <section class="card">
      <div class="card-head"><h2>Your details</h2></div>
      <form method="post" action="<?= e(path('admin/account')) ?>" data-guard-submit>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="profile">
        <div class="card-body">
          <div class="field-row">
            <div class="field<?= isset($errors['name']) ? ' has-error' : '' ?>">
              <label for="name">Name <span class="req">*</span></label>
              <input class="input" type="text" id="name" name="name" required maxlength="120"
                     value="<?= e((string) old('name', $user['name'])) ?>">
              <?php if (isset($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>
            </div>
            <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>">
              <label for="email">Email address <span class="req">*</span></label>
              <input class="input" type="email" id="email" name="email" required maxlength="190"
                     value="<?= e((string) old('email', $user['email'])) ?>">
              <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
              <span class="help">This is also your sign-in address.</span>
            </div>
          </div>
          <div class="field-row">
            <div class="field">
              <label for="job_title">Job title</label>
              <input class="input" type="text" id="job_title" name="job_title" maxlength="120"
                     value="<?= e((string) old('job_title', $user['job_title'])) ?>">
            </div>
            <div class="field<?= isset($errors['phone']) ? ' has-error' : '' ?>">
              <label for="phone">Phone</label>
              <input class="input" type="tel" id="phone" name="phone" maxlength="40"
                     value="<?= e((string) old('phone', $user['phone'])) ?>">
              <?php if (isset($errors['phone'])): ?><span class="field-error"><?= e($errors['phone']) ?></span><?php endif; ?>
            </div>
          </div>
        </div>
        <div class="card-foot"><button type="submit" class="btn btn-primary">Save details</button></div>
      </form>
    </section>

    <section class="card">
      <div class="card-head">
        <div><h2>Change password</h2><div class="sub">At least 10 characters</div></div>
      </div>
      <form method="post" action="<?= e(path('admin/account')) ?>" data-guard-submit>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="password">
        <div class="card-body">
          <div class="field<?= isset($errors['current_password']) ? ' has-error' : '' ?>">
            <label for="current_password">Current password</label>
            <input class="input" type="password" id="current_password" name="current_password" required autocomplete="current-password">
            <?php if (isset($errors['current_password'])): ?><span class="field-error"><?= e($errors['current_password']) ?></span><?php endif; ?>
          </div>
          <div class="field-row">
            <div class="field<?= isset($errors['password']) ? ' has-error' : '' ?>">
              <label for="password">New password</label>
              <input class="input" type="password" id="password" name="password" required autocomplete="new-password">
              <?php if (isset($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
            </div>
            <div class="field">
              <label for="password_confirm">Confirm new password</label>
              <input class="input" type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
            </div>
          </div>
        </div>
        <div class="card-foot"><button type="submit" class="btn btn-red">Change password</button></div>
      </form>
    </section>
  </div>

</div>
