<?php
/**
 * @var array<string,mixed>|null       $client
 * @var array<int,array<string,mixed>> $staff
 * @var array<int,string>              $sectors
 * @var string                         $reference
 */

use App\Core\Flash;
use App\Models\Client;

$errors = Flash::errors();
$isEdit = $client !== null;
$action = $isEdit ? path('admin/clients/' . $client['id'] . '/edit') : path('admin/clients/create');

$val = static function (string $key, $default = '') use ($client) {
    $old = old($key, null);
    if ($old !== null) {
        return $old;
    }
    if ($client !== null && array_key_exists($key, $client)) {
        return $client[$key] ?? $default;
    }
    return $default;
};

$hasError = static fn (string $key): string => isset($errors[$key]) ? ' has-error' : '';
$showError = static function (string $key) use ($errors): void {
    if (isset($errors[$key])) {
        echo '<span class="field-error">' . e($errors[$key]) . '</span>';
    }
};
?>

<form method="post" action="<?= e($action) ?>" class="content-narrow" data-guard-submit>
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-head">
      <div>
        <h2><?= $isEdit ? 'Edit client' : 'New client' ?></h2>
        <div class="sub">Reference <span class="ref"><?= e($reference) ?></span><?= $isEdit ? '' : ' — assigned on save' ?></div>
      </div>
    </div>

    <div class="card-body">

      <div class="form-section">
        <h3>Organisation</h3>

        <div class="field<?= $hasError('organisation') ?>">
          <label for="organisation">Organisation name <span class="req">*</span></label>
          <input class="input" type="text" id="organisation" name="organisation" required maxlength="190"
                 value="<?= e((string) $val('organisation')) ?>">
          <?php $showError('organisation'); ?>
        </div>

        <div class="field-row-3">
          <div class="field">
            <label for="sector">Sector</label>
            <select class="select" id="sector" name="sector">
              <option value="">Not specified</option>
              <?php foreach ($sectors as $sector): ?>
                <option value="<?= e($sector) ?>"<?= $val('sector') === $sector ? ' selected' : '' ?>><?= e($sector) ?></option>
              <?php endforeach; ?>
              <option value="Other"<?= $val('sector') === 'Other' ? ' selected' : '' ?>>Other</option>
            </select>
          </div>
          <div class="field<?= $hasError('company_no') ?>">
            <label for="company_no">Company number</label>
            <input class="input" type="text" id="company_no" name="company_no" maxlength="40" value="<?= e((string) $val('company_no')) ?>">
            <?php $showError('company_no'); ?>
          </div>
          <div class="field">
            <label for="website">Website</label>
            <input class="input" type="text" id="website" name="website" maxlength="190"
                   value="<?= e((string) $val('website')) ?>" placeholder="example.co.uk">
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3>Main contact</h3>

        <div class="field-row-3">
          <div class="field<?= $hasError('contact_name') ?>">
            <label for="contact_name">Name</label>
            <input class="input" type="text" id="contact_name" name="contact_name" maxlength="140" value="<?= e((string) $val('contact_name')) ?>">
            <?php $showError('contact_name'); ?>
          </div>
          <div class="field<?= $hasError('email') ?>">
            <label for="email">Email address</label>
            <input class="input" type="email" id="email" name="email" maxlength="190" value="<?= e((string) $val('email')) ?>">
            <?php $showError('email'); ?>
          </div>
          <div class="field<?= $hasError('phone') ?>">
            <label for="phone">Phone</label>
            <input class="input" type="tel" id="phone" name="phone" maxlength="40" value="<?= e((string) $val('phone')) ?>">
            <?php $showError('phone'); ?>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3>Address</h3>

        <div class="field">
          <label for="address_line1">Address line 1</label>
          <input class="input" type="text" id="address_line1" name="address_line1" maxlength="190" value="<?= e((string) $val('address_line1')) ?>">
        </div>
        <div class="field">
          <label for="address_line2">Address line 2</label>
          <input class="input" type="text" id="address_line2" name="address_line2" maxlength="190" value="<?= e((string) $val('address_line2')) ?>">
        </div>
        <div class="field-row-3">
          <div class="field">
            <label for="city">Town / city</label>
            <input class="input" type="text" id="city" name="city" maxlength="120" value="<?= e((string) $val('city')) ?>">
          </div>
          <div class="field">
            <label for="postcode">Postcode</label>
            <input class="input" type="text" id="postcode" name="postcode" maxlength="20" value="<?= e((string) $val('postcode')) ?>">
          </div>
          <div class="field">
            <label for="country">Country</label>
            <input class="input" type="text" id="country" name="country" maxlength="80" value="<?= e((string) $val('country', 'United Kingdom')) ?>">
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3>Relationship</h3>

        <div class="field-row-3">
          <div class="field">
            <label for="status">Status</label>
            <select class="select" id="status" name="status" required>
              <?php foreach (Client::STATUSES as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $val('status', 'prospect') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="owner_user_id">Account manager</label>
            <select class="select" id="owner_user_id" name="owner_user_id">
              <option value="">Unassigned</option>
              <?php foreach ($staff as $member): ?>
                <option value="<?= (int) $member['id'] ?>"<?= (string) $val('owner_user_id') === (string) $member['id'] ? ' selected' : '' ?>>
                  <?= e((string) $member['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field<?= $hasError('nda_signed_on') ?>">
            <label for="nda_signed_on">NDA signed on</label>
            <input class="input" type="date" id="nda_signed_on" name="nda_signed_on" value="<?= e((string) $val('nda_signed_on')) ?>">
            <?php $showError('nda_signed_on'); ?>
          </div>
        </div>

        <div class="field">
          <label for="notes">Internal notes</label>
          <textarea class="textarea" id="notes" name="notes" data-autogrow maxlength="10000"
                    placeholder="Background, capability, what they can and cannot bid for."><?= e((string) $val('notes')) ?></textarea>
          <span class="help">Only visible to staff. Clients never see these notes.</span>
        </div>
      </div>

    </div>

    <div class="card-foot">
      <button type="submit" class="btn btn-red"><?= $isEdit ? 'Save changes' : 'Create client' ?></button>
      <a href="<?= e(path($isEdit ? 'admin/clients/' . $client['id'] : 'admin/clients')) ?>" class="btn btn-ghost">Cancel</a>
    </div>
  </div>
</form>
