<?php
/**
 * @var array<string,array<int,array<string,mixed>>> $grouped  Week start => bids
 * @var int                                          $days
 */

use App\Models\Bid;
?>

<form method="get" class="filters">
  <div class="field">
    <label for="days">Look ahead</label>
    <select class="select" id="days" name="days" data-auto-submit>
      <?php foreach ([30 => '30 days', 60 => '60 days', 90 => '90 days', 180 => '6 months'] as $value => $label): ?>
        <option value="<?= (int) $value ?>"<?= $days === $value ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filter-actions">
    <a href="<?= e(path('admin/bids')) ?>" class="btn btn-ghost btn-sm">List view</a>
    <a href="<?= e(path('admin/bids/board')) ?>" class="btn btn-ghost btn-sm">Board view</a>
  </div>
</form>

<?php if (!$grouped): ?>
  <div class="card">
    <div class="empty">
      <span class="mark">◷</span>
      <h3>No deadlines in the next <?= (int) $days ?> days</h3>
      <p>Open bids with a submission deadline inside this window will be listed here, grouped by week.</p>
    </div>
  </div>
<?php else: ?>
  <?php foreach ($grouped as $weekStart => $bids): ?>
    <?php
      $weekEnd = date('j M', strtotime($weekStart . ' +6 days'));
      $isThisWeek = $weekStart === date('Y-m-d', strtotime('monday this week'));
    ?>
    <section class="card">
      <div class="card-head">
        <div>
          <h2 style="font-size:14.5px;">
            <?= e(date('j M', strtotime($weekStart))) ?> – <?= e($weekEnd) ?>
            <?php if ($isThisWeek): ?><span class="badge badge-warning" style="margin-left:8px;">This week</span><?php endif; ?>
          </h2>
          <div class="sub"><?= count($bids) ?> deadline<?= count($bids) === 1 ? '' : 's' ?></div>
        </div>
      </div>
      <div class="table-wrap">
        <table class="data">
          <thead>
            <tr><th style="width:120px;">Due</th><th>Bid</th><th>Client</th><th>Stage</th><th>Owner</th></tr>
          </thead>
          <tbody>
            <?php foreach ($bids as $bid): ?>
              <?php $deadline = Bid::deadlineState($bid); ?>
              <tr>
                <td>
                  <strong class="u-small"><?= e(fdatetime((string) $bid['submission_due'], 'D j M')) ?></strong><br>
                  <span class="u-small u-faint u-mono"><?= e(fdatetime((string) $bid['submission_due'], 'H:i')) ?></span><br>
                  <span class="deadline <?= e($deadline['level']) ?>" style="font-size:11px;"><?= e($deadline['label']) ?></span>
                </td>
                <td>
                  <span class="primary-cell"><a href="<?= e(path('admin/bids/' . $bid['id'])) ?>"><?= e(str_excerpt((string) $bid['title'], 60)) ?></a></span>
                  <span class="sub-cell ref"><?= e((string) $bid['reference']) ?></span>
                </td>
                <td class="u-small"><?= e(str_excerpt((string) $bid['organisation'], 30)) ?></td>
                <td><span class="badge badge-neutral"><?= e(Bid::STAGES[$bid['stage']] ?? '') ?></span></td>
                <td class="u-small u-muted"><?= e((string) ($bid['owner_name'] ?? '—')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endforeach; ?>
<?php endif; ?>
