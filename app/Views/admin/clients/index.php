<?php
/**
 * @var App\Core\Paginator             $paginator
 * @var array<string,mixed>            $filters
 * @var array<int,array<string,mixed>> $owners
 */

use App\Core\Auth;
use App\Core\View;
use App\Models\Client;

$statusTone = static fn (string $status): string => match ($status) {
    'active'   => 'success',
    'prospect' => 'info',
    'on_hold'  => 'warning',
    default    => 'muted',
};
?>

<form method="get" class="filters">
  <div class="field grow">
    <label for="q">Search</label>
    <input class="input" type="search" id="q" name="q" value="<?= e((string) $filters['q']) ?>"
           placeholder="Organisation, contact, email or reference">
  </div>

  <div class="field">
    <label for="status">Status</label>
    <select class="select" id="status" name="status" data-auto-submit>
      <option value="">All statuses</option>
      <?php foreach (Client::STATUSES as $key => $label): ?>
        <option value="<?= e($key) ?>"<?= $filters['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="field">
    <label for="owner">Account manager</label>
    <select class="select" id="owner" name="owner" data-auto-submit>
      <option value="">Anyone</option>
      <?php foreach ($owners as $owner): ?>
        <option value="<?= (int) $owner['id'] ?>"<?= $filters['owner'] === (string) $owner['id'] ? ' selected' : '' ?>>
          <?= e((string) $owner['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="filter-actions">
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="<?= e(path('admin/clients')) ?>" class="btn btn-ghost btn-sm">Reset</a>
    <?php if (Auth::can('reports.view')): ?>
      <a href="<?= e(path('admin/clients/export')) ?>" class="btn btn-ghost btn-sm">Export CSV</a>
    <?php endif; ?>
  </div>
</form>

<div class="card">
  <?php if ($paginator->isEmpty()): ?>
    <div class="empty">
      <span class="mark">◫</span>
      <h3>No clients match those filters</h3>
      <p>Clients are created here, or automatically when you convert a consultation request.</p>
      <?php if (Auth::can('clients.manage')): ?>
        <a href="<?= e(path('admin/clients/create')) ?>" class="btn btn-red btn-sm">Add a client</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr>
            <th>Organisation</th>
            <th>Main contact</th>
            <th>Sector</th>
            <th>Status</th>
            <th class="num">Bids</th>
            <th class="num">Value won</th>
            <th>Account manager</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($paginator->items as $client): ?>
            <tr>
              <td>
                <span class="primary-cell">
                  <a href="<?= e(path('admin/clients/' . $client['id'])) ?>"><?= e(str_excerpt((string) $client['organisation'], 40)) ?></a>
                </span>
                <span class="sub-cell ref"><?= e((string) $client['reference']) ?></span>
              </td>
              <td>
                <?php if ($client['contact_name'] !== ''): ?>
                  <span class="u-small"><?= e((string) $client['contact_name']) ?></span>
                  <?php if ($client['email'] !== ''): ?>
                    <span class="sub-cell"><a href="mailto:<?= e((string) $client['email']) ?>"><?= e((string) $client['email']) ?></a></span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="u-faint">—</span>
                <?php endif; ?>
              </td>
              <td class="u-small u-muted"><?= e((string) ($client['sector'] !== '' ? $client['sector'] : '—')) ?></td>
              <td>
                <span class="badge badge-<?= e($statusTone((string) $client['status'])) ?>">
                  <?= e(Client::STATUSES[$client['status']] ?? '') ?>
                </span>
              </td>
              <td class="num">
                <strong><?= (int) $client['bid_count'] ?></strong>
                <?php if ((int) $client['open_bids'] > 0): ?>
                  <span class="sub-cell"><?= (int) $client['open_bids'] ?> open</span>
                <?php endif; ?>
              </td>
              <td class="num u-nowrap">
                <?= (float) $client['value_won'] > 0 ? e(money($client['value_won'])) : '<span class="u-faint">—</span>' ?>
              </td>
              <td class="u-small u-muted"><?= e((string) ($client['owner_name'] ?? '—')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?= View::partial('admin/partials/pagination', ['paginator' => $paginator, 'noun' => 'clients']) ?>
