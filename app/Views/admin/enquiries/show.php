<?php
/**
 * @var array<string,mixed>            $enquiry
 * @var array<int,array<string,mixed>> $staff
 */

use App\Core\Auth;
use App\Models\Enquiry;

$id = (int) $enquiry['id'];
$canManage = Auth::can('enquiries.manage');
?>

<div class="u-between u-mb">
  <div class="u-flex">
    <span class="ref"><?= e((string) $enquiry['reference']) ?></span>
    <span class="badge badge-<?= e(Enquiry::statusTone((string) $enquiry['status'])) ?>">
      <?= e(Enquiry::STATUSES[$enquiry['status']] ?? '') ?>
    </span>
    <span class="u-small u-faint">Received <?= e(fdatetime((string) $enquiry['created_at'])) ?></span>
  </div>
  <a href="<?= e(path('admin/enquiries')) ?>" class="btn btn-subtle btn-sm">← All requests</a>
</div>

<div class="grid grid-main">

  <div>
    <section class="card">
      <div class="card-head"><h2>What they told us</h2></div>
      <div class="card-body">
        <div style="background:var(--paper);border:1px solid var(--line);border-radius:3px;padding:18px;white-space:pre-wrap;font-size:14px;line-height:1.7;">
          <?= e((string) $enquiry['message']) ?>
        </div>
      </div>
    </section>

    <?php if ($canManage): ?>
      <section class="card">
        <div class="card-head"><h2>Internal notes</h2></div>
        <form method="post" action="<?= e(path('admin/enquiries/' . $id . '/notes')) ?>" data-guard-submit>
          <?= csrf_field() ?>
          <div class="card-body">
            <div class="field u-mb0">
              <label for="admin_notes">Notes</label>
              <textarea class="textarea" id="admin_notes" name="admin_notes" data-autogrow maxlength="10000"
                        placeholder="What was discussed, what was quoted, next steps."><?= e((string) $enquiry['admin_notes']) ?></textarea>
              <span class="help">Only visible to staff.</span>
            </div>
          </div>
          <div class="card-foot"><button type="submit" class="btn btn-primary btn-sm">Save notes</button></div>
        </form>
      </section>
    <?php endif; ?>
  </div>

  <div>
    <section class="card">
      <div class="card-head"><h3>Contact details</h3></div>
      <div class="card-body tight">
        <dl class="dl">
          <dt>Name</dt><dd><?= e((string) $enquiry['name']) ?></dd>
          <dt>Organisation</dt><dd><?= e((string) ($enquiry['organisation'] !== '' ? $enquiry['organisation'] : '—')) ?></dd>
          <dt>Email</dt><dd><a href="mailto:<?= e((string) $enquiry['email']) ?>"><?= e((string) $enquiry['email']) ?></a></dd>
          <dt>Phone</dt>
          <dd><?= $enquiry['phone'] !== '' ? '<a href="tel:' . e(preg_replace('/\s+/', '', (string) $enquiry['phone'])) . '">' . e((string) $enquiry['phone']) . '</a>' : '—' ?></dd>
          <dt>Service wanted</dt><dd><?= e((string) ($enquiry['service'] !== '' ? $enquiry['service'] : '—')) ?></dd>
          <dt>Sector</dt><dd><?= e((string) ($enquiry['sector'] !== '' ? $enquiry['sector'] : '—')) ?></dd>
          <dt>Their deadline</dt>
          <dd>
            <?php if (!empty($enquiry['deadline'])): ?>
              <?= e(fdate((string) $enquiry['deadline'])) ?>
              <span class="u-small u-faint">(<?= e(relative_days((string) $enquiry['deadline'])) ?>)</span>
            <?php else: ?>—<?php endif; ?>
          </dd>
          <dt>Source</dt><dd><?= e(labelize((string) $enquiry['source'])) ?></dd>
        </dl>
      </div>
      <div class="card-foot">
        <a href="mailto:<?= e((string) $enquiry['email']) ?>?subject=<?= e(rawurlencode('Your consultation request ' . $enquiry['reference'])) ?>"
           class="btn btn-primary btn-sm btn-block">Reply by email</a>
      </div>
    </section>

    <?php if ($canManage): ?>
      <section class="card">
        <div class="card-head"><h3>Progress</h3></div>
        <form method="post" action="<?= e(path('admin/enquiries/' . $id . '/status')) ?>">
          <?= csrf_field() ?>
          <div class="card-body tight">
            <div class="field">
              <label for="status">Status</label>
              <select class="select" id="status" name="status">
                <?php foreach (Enquiry::STATUSES as $key => $label): ?>
                  <option value="<?= e($key) ?>"<?= $enquiry['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field u-mb0">
              <label for="assigned_to">Assigned to</label>
              <select class="select" id="assigned_to" name="assigned_to">
                <option value="">Nobody</option>
                <?php foreach ($staff as $member): ?>
                  <option value="<?= (int) $member['id'] ?>"<?= (string) $enquiry['assigned_to'] === (string) $member['id'] ? ' selected' : '' ?>>
                    <?= e((string) $member['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="card-foot"><button type="submit" class="btn btn-primary btn-sm btn-block">Update</button></div>
        </form>
      </section>
    <?php endif; ?>

    <section class="card">
      <div class="card-head"><h3>Convert to client</h3></div>
      <div class="card-body tight">
        <?php if (!empty($enquiry['client_id'])): ?>
          <p class="u-small u-muted">Already converted.</p>
          <a href="<?= e(path('admin/clients/' . $enquiry['client_id'])) ?>" class="btn btn-ghost btn-sm btn-block">
            Open <?= e(str_excerpt((string) $enquiry['client_organisation'], 26)) ?>
          </a>
        <?php elseif (Auth::can('clients.manage')): ?>
          <p class="u-small u-muted">Creates a client record with these contact details and links it back to this request.</p>
          <form method="post" action="<?= e(path('admin/enquiries/' . $id . '/convert')) ?>"
                data-confirm="Create a client record from this request?">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-red btn-sm btn-block">Create client record</button>
          </form>
        <?php else: ?>
          <p class="u-small u-muted u-mb0">You do not have permission to create clients.</p>
        <?php endif; ?>
      </div>
    </section>

    <?php if ($canManage): ?>
      <section class="card">
        <div class="card-body">
          <form method="post" action="<?= e(path('admin/enquiries/' . $id . '/delete')) ?>"
                data-confirm="Delete this consultation request permanently?">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger btn-block btn-sm">Delete request</button>
          </form>
        </div>
      </section>
    <?php endif; ?>
  </div>

</div>
