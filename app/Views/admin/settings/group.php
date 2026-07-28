<?php
/**
 * @var string                              $group
 * @var array<string,array<string,string>>  $groups
 * @var array<int,array<string,mixed>>      $settings
 * @var array<int,array<string,mixed>>|null $diagnostics
 */

use App\Core\Branding;
use App\Core\Flash;
use App\Core\Settings;
use App\Core\Uploader;

$errors = Flash::errors();
// Only a form that carries files needs the multipart encoding.
$hasImages = false;
foreach ($settings as $setting) {
    if (($setting['type'] ?? '') === 'image') {
        $hasImages = true;
        break;
    }
}
?>

<div class="tabs">
  <?php foreach ($groups as $key => $definition): ?>
    <a href="<?= e(path('admin/settings/' . $key)) ?>" class="<?= $key === $group ? 'active' : '' ?>">
      <?= e($definition['label']) ?>
    </a>
  <?php endforeach; ?>
  <a href="<?= e(path('admin/logs/email')) ?>">Email log</a>
</div>

<form method="post" action="<?= e(path('admin/settings/' . $group)) ?>" class="content-narrow"
      <?= $hasImages ? 'enctype="multipart/form-data"' : '' ?> data-guard-submit>
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

        <div class="field<?= isset($errors[$key]) ? ' has-error' : '' ?>">
          <?php if ($type === 'image'): ?>
            <?php $preview = Branding::url($key); ?>
            <label for="<?= e($key) ?>"><?= e((string) $setting['label']) ?></label>

            <div class="brand-field">
              <div class="brand-preview<?= $key === 'logo_image_dark' ? ' on-dark' : '' ?>">
                <?php if ($preview !== null): ?>
                  <img src="<?= e($preview) ?>" alt="Current <?= e(strtolower((string) $setting['label'])) ?>">
                <?php else: ?>
                  <span class="brand-empty">None set</span>
                <?php endif; ?>
              </div>

              <div class="brand-controls">
                <input class="input" type="file" id="<?= e($key) ?>" name="<?= e($key) ?>"
                       accept=".png,.jpg,.jpeg,.gif,.webp,.svg,.ico,image/*">
                <?php if ($preview !== null): ?>
                  <p class="u-small u-faint u-mb0" style="margin-top:7px;">
                    Choosing a new file replaces the current one.
                  </p>
                <?php endif; ?>
              </div>
            </div>

          <?php elseif ($type === 'bool'): ?>
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
          <?php if (isset($errors[$key])): ?>
            <span class="field-error"><?= e($errors[$key]) ?></span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <?php if ($group === 'branding'): ?>
        <p class="u-small u-faint u-mb0">
          Accepted: <?= e(implode(', ', Uploader::allowedImageExtensions())) ?>,
          up to <?= e(filesize_human(Uploader::maxImageBytes())) ?> each.
          Leave a field empty to keep the built-in default.
        </p>
      <?php endif; ?>
    </div>

    <div class="card-foot">
      <button type="submit" class="btn btn-red">Save settings</button>
    </div>
  </div>
</form>

<?php if ($group === 'branding'): ?>
  <?php
    // Removal posts to its own endpoint, so it cannot sit inside the form above.
    $uploaded = array_filter($settings, static fn ($s) => ($s['type'] ?? '') === 'image' && (string) $s['value'] !== '');
  ?>
  <?php if ($uploaded): ?>
    <section class="card content-narrow">
      <div class="card-head">
        <div><h2>Remove an image</h2><div class="sub">Reverts to the built-in default</div></div>
      </div>
      <div class="card-body">
        <?php foreach ($uploaded as $setting): ?>
          <div class="u-between" style="padding:9px 0;border-bottom:1px solid var(--line-soft);">
            <span class="u-small" style="font-weight:600;"><?= e((string) $setting['label']) ?></span>
            <form method="post" action="<?= e(path('admin/settings/branding/' . $setting['key'] . '/remove')) ?>"
                  data-confirm="Remove the <?= e(strtolower((string) $setting['label'])) ?>?">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-danger btn-sm">Remove</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <section class="card content-narrow">
    <div class="card-head">
      <div><h2>Where each image appears</h2></div>
    </div>
    <div class="table-wrap">
      <table class="data">
        <tbody>
          <tr>
            <td style="width:210px;"><strong class="u-small">Logo</strong></td>
            <td class="u-small u-muted">The website header, the sign-in screens, and the header of every email the system sends.</td>
          </tr>
          <tr>
            <td><strong class="u-small">Logo for dark backgrounds</strong></td>
            <td class="u-small u-muted">The admin panel and client portal sidebars, which are dark navy. If you leave this empty, those sidebars fall back to the typographic wordmark.</td>
          </tr>
          <tr>
            <td><strong class="u-small">Favicon</strong></td>
            <td class="u-small u-muted">The browser tab icon on every page, and the home-screen icon when someone saves the site on a phone.</td>
          </tr>
          <tr>
            <td><strong class="u-small">Social sharing image</strong></td>
            <td class="u-small u-muted">The preview card shown when a link to your site is posted on LinkedIn, X, WhatsApp or Slack.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>

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
