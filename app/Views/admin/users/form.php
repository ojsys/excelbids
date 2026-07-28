<?php
/**
 * @var array<string,mixed>|null $user
 * @var array<string,string>     $roles
 */

use App\Core\Flash;

$errors = Flash::errors();
$isEdit = $user !== null;
$action = $isEdit ? path('admin/users/' . $user['id'] . '/edit') : path('admin/users/create');

$val = static function (string $key, $default = '') use ($user) {
    $old = old($key, null);
    if ($old !== null) {
        return $old;
    }
    return $user !== null ? ($user[$key] ?? $default) : $default;
};
?>

<form method="post" action="<?= e($action) ?>" class="content-narrow" style="max-width:620px;" data-guard-submit>
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-head">
      <div><h2><?= $isEdit ? 'Edit staff account' : 'New staff account' ?></h2></div>
    </div>

    <div class="card-body">
      <div class="field<?= isset($errors['name']) ? ' has-error' : '' ?>">
        <label for="name">Name <span class="req">*</span></label>
        <input class="input" type="text" id="name" name="name" required maxlength="120" value="<?= e((string) $val('name')) ?>">
        <?php if (isset($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>
      </div>

      <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>">
        <label for="email">Email address <span class="req">*</span></label>
        <input class="input" type="email" id="email" name="email" required maxlength="190" value="<?= e((string) $val('email')) ?>">
        <span class="help">This is their sign-in address.</span>
        <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="job_title">Job title</label>
          <input class="input" type="text" id="job_title" name="job_title" maxlength="120" value="<?= e((string) $val('job_title')) ?>">
        </div>
        <div class="field<?= isset($errors['phone']) ? ' has-error' : '' ?>">
          <label for="phone">Phone</label>
          <input class="input" type="tel" id="phone" name="phone" maxlength="40" value="<?= e((string) $val('phone')) ?>">
          <?php if (isset($errors['phone'])): ?><span class="field-error"><?= e($errors['phone']) ?></span><?php endif; ?>
        </div>
      </div>

      <div class="field<?= isset($errors['role']) ? ' has-error' : '' ?>">
        <label for="role">Role <span class="req">*</span></label>
        <select class="select" id="role" name="role" required>
          <?php foreach ($roles as $key => $description): ?>
            <option value="<?= e($key) ?>"<?= $val('role', 'writer') === $key ? ' selected' : '' ?>><?= e($description) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['role'])): ?><span class="field-error"><?= e($errors['role']) ?></span><?php endif; ?>
      </div>

      <div class="form-section">
        <h3><?= $isEdit ? 'Change password' : 'Password' ?></h3>
        <?php if ($isEdit): ?>
          <p class="sub">Leave both fields blank to keep their current password.</p>
        <?php else: ?>
          <p class="sub">At least 10 characters.</p>
        <?php endif; ?>

        <div class="field-row">
          <div class="field<?= isset($errors['password']) ? ' has-error' : '' ?>">
            <label for="password"><?= $isEdit ? 'New password' : 'Password' ?><?= $isEdit ? '' : ' <span class="req">*</span>' ?></label>
            <input class="input" type="password" id="password" name="password" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
            <?php if (isset($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
          </div>
          <div class="field">
            <label for="password_confirm">Confirm password</label>
            <input class="input" type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
          </div>
        </div>

        <?php if (!$isEdit): ?>
          <div class="u-stack">
            <label class="checkline">
              <input type="checkbox" name="must_change_pw" value="1" checked>
              <span>Ask them to set their own password when they first sign in</span>
            </label>
            <label class="checkline">
              <input type="checkbox" name="send_welcome" value="1" checked>
              <span>Email them a link to choose their own password</span>
            </label>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card-foot">
      <button type="submit" class="btn btn-red"><?= $isEdit ? 'Save changes' : 'Create account' ?></button>
      <a href="<?= e(path('admin/users')) ?>" class="btn btn-ghost">Cancel</a>
    </div>
  </div>
</form>
