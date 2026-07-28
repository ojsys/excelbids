<?php
/**
 * @var array<string,mixed>|null       $bid
 * @var array<int,array<string,mixed>> $clients
 * @var array<int,array<string,mixed>> $staff
 * @var array<int,string>              $sectors
 * @var string                         $reference
 */

use App\Core\Database;
use App\Core\Flash;
use App\Models\Bid;

$errors = Flash::errors();
$isEdit = $bid !== null;
$action = $isEdit ? path('admin/bids/' . $bid['id'] . '/edit') : path('admin/bids/create');

/** Current value: old input after a failed submit, else the record, else a default. */
$val = static function (string $key, $default = '') use ($bid) {
    $old = old($key, null);
    if ($old !== null) {
        return $old;
    }
    if ($bid !== null && array_key_exists($key, $bid)) {
        return $bid[$key] ?? $default;
    }
    return $default;
};

$hasError = static fn (string $key): string => isset($errors[$key]) ? ' has-error' : '';
$showError = static function (string $key) use ($errors): void {
    if (isset($errors[$key])) {
        echo '<span class="field-error">' . e($errors[$key]) . '</span>';
    }
};

// Portals and services come from the CMS so the two stay in step.
$portals = Database::all('SELECT name, line_two FROM portals WHERE is_active = 1 ORDER BY sort_order, id');
$services = Database::all('SELECT title FROM services WHERE is_active = 1 ORDER BY sort_order, id');

$dueValue = '';
if ($val('submission_due') !== '') {
    $timestamp = strtotime((string) $val('submission_due'));
    $dueValue = $timestamp ? date('Y-m-d\TH:i', $timestamp) : '';
}
?>

<form method="post" action="<?= e($action) ?>" class="content-narrow" data-guard-submit>
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-head">
      <div>
        <h2><?= $isEdit ? 'Edit bid' : 'New bid' ?></h2>
        <div class="sub">Reference <span class="ref"><?= e($reference) ?></span><?= $isEdit ? '' : ' — assigned on save' ?></div>
      </div>
    </div>

    <div class="card-body">

      <div class="form-section">
        <h3>The opportunity</h3>
        <p class="sub">What is being bid for, and for whom.</p>

        <div class="field<?= $hasError('client_id') ?>">
          <label for="client_id">Client <span class="req">*</span></label>
          <select class="select" id="client_id" name="client_id" required>
            <option value="">Choose a client…</option>
            <?php foreach ($clients as $client): ?>
              <option value="<?= (int) $client['id'] ?>"<?= (string) $val('client_id') === (string) $client['id'] ? ' selected' : '' ?>>
                <?= e((string) $client['organisation']) ?> (<?= e((string) $client['reference']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <?php $showError('client_id'); ?>
          <?php if (!$clients): ?>
            <span class="help">No clients yet — <a href="<?= e(path('admin/clients/create')) ?>">add one first</a>.</span>
          <?php endif; ?>
        </div>

        <div class="field<?= $hasError('title') ?>">
          <label for="title">Bid title <span class="req">*</span></label>
          <input class="input" type="text" id="title" name="title" required maxlength="255"
                 value="<?= e((string) $val('title')) ?>"
                 placeholder="e.g. Supported Living Framework — Lot 2">
          <?php $showError('title'); ?>
        </div>

        <div class="field-row">
          <div class="field<?= $hasError('buyer') ?>">
            <label for="buyer">Buyer / contracting authority</label>
            <input class="input" type="text" id="buyer" name="buyer" maxlength="190" value="<?= e((string) $val('buyer')) ?>">
            <?php $showError('buyer'); ?>
          </div>
          <div class="field<?= $hasError('service_type') ?>">
            <label for="service_type">Our service</label>
            <select class="select" id="service_type" name="service_type">
              <option value="">Not specified</option>
              <?php foreach ($services as $service): ?>
                <option value="<?= e((string) $service['title']) ?>"<?= $val('service_type') === $service['title'] ? ' selected' : '' ?>>
                  <?= e((string) $service['title']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="field-row-3">
          <div class="field">
            <label for="portal">Portal</label>
            <select class="select" id="portal" name="portal">
              <option value="">Not specified</option>
              <?php foreach ($portals as $portal): ?>
                <?php $name = trim($portal['name'] . ' ' . $portal['line_two']); ?>
                <option value="<?= e($name) ?>"<?= $val('portal') === $name ? ' selected' : '' ?>><?= e($name) ?></option>
              <?php endforeach; ?>
              <option value="Other"<?= $val('portal') === 'Other' ? ' selected' : '' ?>>Other</option>
            </select>
          </div>
          <div class="field">
            <label for="portal_ref">Portal reference</label>
            <input class="input" type="text" id="portal_ref" name="portal_ref" maxlength="120" value="<?= e((string) $val('portal_ref')) ?>">
          </div>
          <div class="field">
            <label for="sector">Sector</label>
            <select class="select" id="sector" name="sector">
              <option value="">Not specified</option>
              <?php foreach ($sectors as $sector): ?>
                <option value="<?= e($sector) ?>"<?= $val('sector') === $sector ? ' selected' : '' ?>><?= e($sector) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="field">
          <label for="summary">Summary</label>
          <textarea class="textarea sm" id="summary" name="summary" data-autogrow maxlength="5000"
                    placeholder="Scope, lots, win themes, anything the team needs at a glance."><?= e((string) $val('summary')) ?></textarea>
        </div>
      </div>

      <div class="form-section">
        <h3>Dates</h3>
        <p class="sub">The submission deadline drives every reminder in the system.</p>

        <div class="field-row-3">
          <div class="field<?= $hasError('clarification_due') ?>">
            <label for="clarification_due">Clarifications close</label>
            <input class="input" type="date" id="clarification_due" name="clarification_due" value="<?= e((string) $val('clarification_due')) ?>">
            <?php $showError('clarification_due'); ?>
          </div>
          <div class="field<?= $hasError('submission_due') ?>">
            <label for="submission_due">Submission deadline</label>
            <input class="input" type="datetime-local" id="submission_due" name="submission_due" value="<?= e($dueValue) ?>">
            <?php $showError('submission_due'); ?>
          </div>
          <div class="field">
            <label for="decision_expected_on">Decision expected</label>
            <input class="input" type="date" id="decision_expected_on" name="decision_expected_on" value="<?= e((string) $val('decision_expected_on')) ?>">
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3>Progress</h3>

        <div class="field-row-3">
          <div class="field">
            <label for="stage">Stage</label>
            <select class="select" id="stage" name="stage" required>
              <?php foreach (Bid::STAGES as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $val('stage', 'consultation') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="status">Status</label>
            <select class="select" id="status" name="status" required data-outcome-toggle>
              <?php foreach (Bid::STATUSES as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $val('status', 'draft') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="owner_user_id">Bid owner</label>
            <select class="select" id="owner_user_id" name="owner_user_id">
              <option value="">Unassigned</option>
              <?php foreach ($staff as $member): ?>
                <option value="<?= (int) $member['id'] ?>"<?= (string) $val('owner_user_id') === (string) $member['id'] ? ' selected' : '' ?>>
                  <?= e((string) $member['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="field<?= $hasError('win_probability') ?>">
          <label for="win_probability">Win probability (%)</label>
          <input class="input" type="number" id="win_probability" name="win_probability" min="0" max="100" step="5"
                 value="<?= e((string) $val('win_probability', '50')) ?>" style="max-width:140px;">
          <span class="help">Your honest read. Used to weight the pipeline in reports.</span>
          <?php $showError('win_probability'); ?>
        </div>
      </div>

      <div class="form-section">
        <h3>Commercials</h3>

        <div class="field-row">
          <div class="field<?= $hasError('contract_value') ?>">
            <label for="contract_value">Contract value (<?= e((string) setting('currency_symbol', '£')) ?>)</label>
            <input class="input" type="number" id="contract_value" name="contract_value" min="0" step="0.01"
                   value="<?= e((string) $val('contract_value', '0')) ?>">
            <?php $showError('contract_value'); ?>
          </div>
          <div class="field">
            <label for="contract_length">Contract length</label>
            <input class="input" type="text" id="contract_length" name="contract_length" maxlength="60"
                   value="<?= e((string) $val('contract_length')) ?>" placeholder="e.g. 3 years + 2 optional">
          </div>
        </div>

        <div class="field-row">
          <div class="field">
            <label for="fee_type">Our fee basis</label>
            <select class="select" id="fee_type" name="fee_type" required>
              <?php foreach (Bid::FEE_TYPES as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $val('fee_type', 'fixed') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field<?= $hasError('fee_amount') ?>">
            <label for="fee_amount">Our fee (<?= e((string) setting('currency_symbol', '£')) ?>)</label>
            <input class="input" type="number" id="fee_amount" name="fee_amount" min="0" step="0.01"
                   value="<?= e((string) $val('fee_amount', '0')) ?>">
            <?php $showError('fee_amount'); ?>
          </div>
        </div>
      </div>

      <div class="form-section" id="outcome-fields">
        <h3>Outcome</h3>
        <p class="sub">Complete once the buyer has confirmed the result.</p>

        <div class="field-row">
          <div class="field<?= $hasError('evaluation_score') ?>">
            <label for="evaluation_score">Evaluation score</label>
            <input class="input" type="number" id="evaluation_score" name="evaluation_score" min="0" step="0.01"
                   value="<?= e((string) $val('evaluation_score')) ?>">
            <?php $showError('evaluation_score'); ?>
          </div>
          <div class="field">
            <label for="evaluation_max">Out of</label>
            <input class="input" type="number" id="evaluation_max" name="evaluation_max" min="1" step="0.01"
                   value="<?= e((string) $val('evaluation_max', '100')) ?>">
          </div>
        </div>

        <div class="field">
          <label for="outcome_notes">Buyer feedback</label>
          <textarea class="textarea sm" id="outcome_notes" name="outcome_notes" data-autogrow maxlength="5000"
                    placeholder="Scoring feedback, what to do differently next time."><?= e((string) $val('outcome_notes')) ?></textarea>
        </div>
      </div>

    </div>

    <div class="card-foot">
      <button type="submit" class="btn btn-red"><?= $isEdit ? 'Save changes' : 'Create bid' ?></button>
      <a href="<?= e(path($isEdit ? 'admin/bids/' . $bid['id'] : 'admin/bids')) ?>" class="btn btn-ghost">Cancel</a>
    </div>
  </div>
</form>
