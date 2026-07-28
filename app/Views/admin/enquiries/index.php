<?php
/**
 * @var App\Core\Paginator  $paginator
 * @var array<string,mixed> $filters
 */

use App\Core\Auth;
use App\Core\View;
use App\Models\Enquiry;
?>

<form method="get" class="filters">
  <div class="field grow">
    <label for="q">Search</label>
    <input class="input" type="search" id="q" name="q" value="<?= e((string) $filters['q']) ?>"
           placeholder="Name, organisation, email or reference">
  </div>
  <div class="field">
    <label for="status">Status</label>
    <select class="select" id="status" name="status" data-auto-submit>
      <option value="">All statuses</option>
      <?php foreach (Enquiry::STATUSES as $key => $label): ?>
        <option value="<?= e($key) ?>"<?= $filters['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filter-actions">
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="<?= e(path('admin/enquiries')) ?>" class="btn btn-ghost btn-sm">Reset</a>
    <?php if (Auth::can('reports.view')): ?>
      <a href="<?= e(path('admin/enquiries/export')) ?>" class="btn btn-ghost btn-sm">Export CSV</a>
    <?php endif; ?>
  </div>
</form>

<div class="card">
  <?php if ($paginator->isEmpty()): ?>
    <div class="empty">
      <span class="mark">✉</span>
      <h3>No consultation requests</h3>
      <p>Requests submitted through the website's consultation form arrive here.</p>
      <a href="<?= e(path('consultation')) ?>" class="btn btn-ghost btn-sm" target="_blank" rel="noopener">View the form</a>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th>From</th><th>Needs</th><th>Their deadline</th><th>Status</th><th>Assigned</th><th>Received</th></tr>
        </thead>
        <tbody>
          <?php foreach ($paginator->items as $enquiry): ?>
            <tr>
              <td>
                <span class="primary-cell">
                  <a href="<?= e(path('admin/enquiries/' . $enquiry['id'])) ?>">
                    <?= e(str_excerpt((string) ($enquiry['organisation'] !== '' ? $enquiry['organisation'] : $enquiry['name']), 36)) ?>
                  </a>
                </span>
                <span class="sub-cell">
                  <?= e((string) $enquiry['name']) ?> · <span class="ref"><?= e((string) $enquiry['reference']) ?></span>
                </span>
              </td>
              <td class="u-small u-muted">
                <?= e((string) ($enquiry['service'] !== '' ? $enquiry['service'] : 'Not specified')) ?>
                <?php if ($enquiry['sector'] !== ''): ?><span class="sub-cell"><?= e((string) $enquiry['sector']) ?></span><?php endif; ?>
              </td>
              <td class="u-small">
                <?php if (!empty($enquiry['deadline'])): ?>
                  <?= e(fdate((string) $enquiry['deadline'])) ?>
                  <span class="sub-cell"><?= e(relative_days((string) $enquiry['deadline'])) ?></span>
                <?php else: ?>
                  <span class="u-faint">—</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge-<?= e(Enquiry::statusTone((string) $enquiry['status'])) ?>">
                  <?= e(Enquiry::STATUSES[$enquiry['status']] ?? '') ?>
                </span>
              </td>
              <td class="u-small u-muted"><?= e((string) ($enquiry['assigned_name'] ?? '—')) ?></td>
              <td class="u-small u-muted u-nowrap">
                <?= e(fdate((string) $enquiry['created_at'], 'j M Y')) ?>
                <span class="sub-cell"><?= e(fdatetime((string) $enquiry['created_at'], 'H:i')) ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?= View::partial('admin/partials/pagination', ['paginator' => $paginator, 'noun' => 'requests']) ?>
