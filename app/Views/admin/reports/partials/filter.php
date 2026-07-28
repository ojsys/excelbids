<?php
/**
 * Shared reporting tabs and date-window control.
 *
 * @var string      $tab
 * @var string|null $from
 * @var string|null $to
 * @var string      $preset
 */

$tabs = [
    'overview'    => ['label' => 'Overview',    'path' => 'admin/reports'],
    'pipeline'    => ['label' => 'Pipeline',    'path' => 'admin/reports/pipeline'],
    'clients'     => ['label' => 'Clients',     'path' => 'admin/reports/clients'],
    'performance' => ['label' => 'Performance', 'path' => 'admin/reports/performance'],
];

$presets = [
    'all'        => 'All time',
    'this_month' => 'This month',
    'last_month' => 'Last month',
    'quarter'    => 'Last 3 months',
    'last_12'    => 'Last 12 months',
    'year'       => 'This year',
];

// Carry the window across tab clicks.
$carry = array_filter(['preset' => $preset, 'from' => $from, 'to' => $to], static fn ($v) => $v !== null && $v !== '' && $v !== 'custom');
$query = $carry ? '?' . http_build_query($carry) : '';
?>

<div class="tabs">
  <?php foreach ($tabs as $key => $definition): ?>
    <a href="<?= e(path($definition['path']) . $query) ?>" class="<?= $tab === $key ? 'active' : '' ?>">
      <?= e($definition['label']) ?>
    </a>
  <?php endforeach; ?>
</div>

<form method="get" class="filters">
  <div class="field">
    <label for="preset">Period</label>
    <select class="select" id="preset" name="preset" data-auto-submit>
      <?php foreach ($presets as $key => $label): ?>
        <option value="<?= e($key) ?>"<?= $preset === $key ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
      <?php if ($preset === 'custom'): ?><option value="custom" selected>Custom range</option><?php endif; ?>
    </select>
  </div>

  <div class="field">
    <label for="from">From</label>
    <input class="input" type="date" id="from" name="from" value="<?= e((string) $from) ?>">
  </div>
  <div class="field">
    <label for="to">To</label>
    <input class="input" type="date" id="to" name="to" value="<?= e((string) $to) ?>">
  </div>

  <div class="filter-actions">
    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
    <a href="<?= e(path($tabs[$tab]['path'])) ?>" class="btn btn-ghost btn-sm">Reset</a>
  </div>
</form>

<p class="u-small u-muted u-mb">
  Showing
  <?php if ($from === null && $to === null): ?>
    <strong>all bids on record</strong>.
  <?php else: ?>
    bids created between <strong><?= e($from !== null ? fdate($from) : 'the beginning') ?></strong>
    and <strong><?= e($to !== null ? fdate($to) : 'today') ?></strong>.
  <?php endif; ?>
</p>
