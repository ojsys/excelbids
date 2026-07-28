<?php
/**
 * @var string                              $group
 * @var array<string,array<string,string>>  $groups
 * @var array<int,array<string,mixed>>      $settings
 * @var array<int,array<string,mixed>>|null $diagnostics
 */

use App\Core\Settings;
?>

<div class="tabs">
  <?php foreach ($groups as $key => $definition): ?>
    <a href="<?= e(path('admin/settings/' . $key)) ?>" class="<?= $key === $group ? 'active' : '' ?>">
      <?= e($definition['label']) ?>
    </a>
  <?php endforeach; ?>
  <a href="<?= e(path('admin/logs/email')) ?>">Email log</a>
</div>

<form method="post" action="<?= e(path('admin/settings/' . $group)) ?>" class="content-narrow" data-guard-submit>
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-head">
      <div>
        <h2><?= e($groups[$group]['label']) ?></h2>
        <div class="sub"><?= e($groups[$group]['intro']) ?></div>
      </div>
    </div>

    <div class="card-body">
      <?php foreach ($settings as $setting): ?>
        <?php $key = (string) $setting['key']; $type = (string) $setting['type']; $value = (string) $setting['value']; ?>

        <div class="field">
          <?php if ($type === 'bool'): ?>
            <label class="checkline">
              <input type="checkbox" name="<?= e($key) ?>" value="1" <?= Settings::bool($key) ? 'checked' : '' ?>>
              <span><strong><?= e((string) $setting['label']) ?></strong></span>
            </label>

          <?php elseif ($type === 'select' && $key === 'mail_transport'): ?>
            <label for="<?= e($key) ?>"><?= e((string) $setting['label']) ?></label>
            <select class="select" id="<?= e($key) ?>" name="<?= e($key) ?>">
              <option value="mail"<?= $value === 'mail' ? ' selected' : '' ?>>PHP mail() — simplest, works on most cPanel hosts</option>
              <option value="smtp"<?= $value === 'smtp' ? ' selected' : '' ?>>SMTP — better deliverability, needs credentials</option>
            </select>

          <?php elseif ($type === 'select' && $key === 'smtp_secure'): ?>
            <label for="<?= e($key) ?>"><?= e((string) $setting['label']) ?></label>
            <select class="select" id="<?= e($key) ?>" name="<?= e($key) ?>">
              <option value="tls"<?= $value === 'tls' ? ' selected' : '' ?>>TLS (port 587)</option>
              <option value="ssl"<?= $value === 'ssl' ? ' selected' : '' ?>>SSL (port 465)</option>
              <option value="none"<?= $value === 'none' ? ' selected' : '' ?>>None (not recommended)</option>
            </select>

          <?php elseif ($type === 'password'): ?>
            <label for="<?= e($key) ?>"><?= e((string) $setting['label']) ?></label>
            <input class="input" type="password" id="<?= e($key) ?>" name="<?= e($key) ?>" autocomplete="new-password"
                   placeholder="<?= $value !== '' ? '••••••••  (leave blank to keep)' : 'Not set' ?>">

          <?php elseif ($type === 'textarea'): ?>
            <label for="<?= e($key) ?>"><?= e((string) $setting['label']) ?></label>
            <textarea class="textarea sm" id="<?= e($key) ?>" name="<?= e($key) ?>" data-autogrow><?= e($value) ?></textarea>

          <?php elseif ($type === 'number'): ?>
            <label for="<?= e($key) ?>"><?= e((string) $setting['label']) ?></label>
            <input class="input" type="number" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($value) ?>" style="max-width:200px;">

          <?php else: ?>
            <label for="<?= e($key) ?>"><?= e((string) $setting['label']) ?></label>
            <input class="input" type="text" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($value) ?>">
          <?php endif; ?>

          <?php if (!empty($setting['hint'])): ?>
            <span class="help"><?= e((string) $setting['hint']) ?></span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="card-foot">
      <button type="submit" class="btn btn-red">Save settings</button>
    </div>
  </div>
</form>

<?php if ($group === 'mail'): ?>
  <section class="card content-narrow">
    <div class="card-head">
      <div><h2>Test your email settings</h2><div class="sub">Sends a message to your own address</div></div>
    </div>
    <div class="card-body">
      <p class="u-small u-muted">
        Email is the most common thing to break on shared hosting. Send a test before you rely on
        consultation-request notifications reaching you.
      </p>
      <form method="post" action="<?= e(path('admin/settings/mail/test')) ?>" data-guard-submit>
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-primary btn-sm">Send test email</button>
      </form>
    </div>
  </section>
<?php endif; ?>

<?php if ($diagnostics !== null): ?>
  <section class="card content-narrow">
    <div class="card-head">
      <div><h2>System check</h2><div class="sub">What your hosting is doing right now</div></div>
    </div>
    <div class="table-wrap">
      <table class="data">
        <tbody>
          <?php foreach ($diagnostics as $check): ?>
            <tr>
              <td style="width:190px;"><strong class="u-small"><?= e($check['label']) ?></strong></td>
              <td style="width:150px;">
                <span class="badge badge-<?= $check['ok'] ? 'success' : 'warning' ?>"><?= e($check['value']) ?></span>
              </td>
              <td class="u-small u-muted"><?= e($check['note']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>
