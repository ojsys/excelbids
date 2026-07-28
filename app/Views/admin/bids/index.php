<?php
/**
 * @var App\Core\Paginator            $paginator
 * @var array<string,mixed>           $filters
 * @var string                        $sort
 * @var string                        $dir
 * @var array<int,array<string,mixed>> $clients
 * @var array<int,array<string,mixed>> $owners
 */

use App\Core\Auth;
use App\Core\View;
use App\Models\Bid;

/** Build a sortable column header that keeps the current filters. */
$sortLink = static function (string $key, string $label) use ($filters, $sort, $dir): string {
    $nextDir = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
    $query = array_filter($filters, static fn ($v) => $v !== '' && $v !== null);
    $query['sort'] = $key;
    $query['dir'] = $nextDir;
    $arrow = $sort === $key ? ($dir === 'asc' ? '↑' : '↓') : '';
    return '<a href="?' . e(http_build_query($query)) . '">' . e($label) . ' ' . $arrow . '</a>';
};
?>

<form method="get" class="filters">
  <div class="field grow">
    <label for="q">Search</label>
    <input class="input" type="search" id="q" name="q" value="<?= e((string) $filters['q']) ?>"
           placeholder="Title, reference, buyer or client">
  </div>

  <div class="field">
    <label for="status">Status</label>
    <select class="select" id="status" name="status" data-auto-submit>
      <option value="">All statuses</option>
      <option value="open"<?= $filters['status'] === 'open' ? ' selected' : '' ?>>Open only</option>
      <?php foreach (Bid::STATUSES as $key => $label): ?>
        <option value="<?= e($key) ?>"<?= $filters['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="field">
    <label for="stage">Stage</label>
    <select class="select" id="stage" name="stage" data-auto-submit>
      <option value="">All stages</option>
      <?php foreach (Bid::STAGES as $key => $label): ?>
        <option value="<?= e($key) ?>"<?= $filters['stage'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="field">
    <label for="client_id">Client</label>
    <select class="select" id="client_id" name="client_id" data-auto-submit>
      <option value="">All clients</option>
      <?php foreach ($clients as $client): ?>
        <option value="<?= (int) $client['id'] ?>"<?= $filters['client_id'] === (string) $client['id'] ? ' selected' : '' ?>>
          <?= e(str_excerpt((string) $client['organisation'], 32)) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="field">
    <label for="due">Deadline</label>
    <select class="select" id="due" name="due" data-auto-submit>
      <option value="">Any time</option>
      <option value="overdue"<?= $filters['due'] === 'overdue' ? ' selected' : '' ?>>Overdue</option>
      <option value="week"<?= $filters['due'] === 'week' ? ' selected' : '' ?>>Next 7 days</option>
      <option value="month"<?= $filters['due'] === 'month' ? ' selected' : '' ?>>Next 30 days</option>
    </select>
  </div>

  <div class="filter-actions">
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="<?= e(path('admin/bids')) ?>" class="btn btn-ghost btn-sm">Reset</a>
  </div>
</form>

<div class="u-between u-mb">
  <div class="u-flex">
    <a href="<?= e(path('admin/bids/board')) ?>" class="btn btn-ghost btn-sm">Board view</a>
    <a href="<?= e(path('admin/bids/calendar')) ?>" class="btn btn-ghost btn-sm">Calendar</a>
  </div>
  <?php if (Auth::can('reports.view')): ?>
    <a href="<?= e(path('admin/bids/export') . '?' . http_build_query(array_filter($filters))) ?>" class="btn btn-ghost btn-sm">Export CSV</a>
  <?php endif; ?>
</div>

<div class="card">
  <?php if ($paginator->isEmpty()): ?>
    <div class="empty">
      <span class="mark">▤</span>
      <h3>No bids match those filters</h3>
      <p>Try widening the search, or add the first bid for a client.</p>
      <?php if (Auth::can('bids.manage')): ?>
        <a href="<?= e(path('admin/bids/create')) ?>" class="btn btn-red btn-sm">Add a bid</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr>
            <th><?= $sortLink('title', 'Bid') ?></th>
            <th><?= $sortLink('client', 'Client') ?></th>
            <th>Stage</th>
            <th><?= $sortLink('status', 'Status') ?></th>
            <th><?= $sortLink('deadline', 'Deadline') ?></th>
            <th class="num"><?= $sortLink('value', 'Value') ?></th>
            <th>Owner</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($paginator->items as $bid): ?>
            <?= View::partial('admin/partials/bid-row', ['bid' => $bid, 'showClient' => true]) ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?= View::partial('admin/partials/pagination', ['paginator' => $paginator, 'noun' => 'bids']) ?>
