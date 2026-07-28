<?php
/**
 * @var array<int,array<string,mixed>> $entries
 * @var int                            $page
 * @var int                            $lastPage
 * @var int                            $total
 */

$entityLink = static function (array $entry): ?string {
    if (empty($entry['entity_id'])) {
        return null;
    }
    return match ((string) $entry['entity_type']) {
        'bid'      => path('admin/bids/' . $entry['entity_id']),
        'client'   => path('admin/clients/' . $entry['entity_id']),
        'enquiry'  => path('admin/enquiries/' . $entry['entity_id']),
        default    => null,
    };
};
?>

<div class="card">
  <div class="card-head">
    <div><h2>Activity log</h2><div class="sub"><?= number_format($total) ?> entries recorded</div></div>
  </div>

  <?php if (!$entries): ?>
    <div class="empty"><span class="mark">◷</span><h3>Nothing logged yet</h3></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th style="width:150px;">When</th><th>Who</th><th>What</th><th>Record</th><th style="width:120px;">From</th></tr>
        </thead>
        <tbody>
          <?php foreach ($entries as $entry): ?>
            <tr>
              <td class="u-small u-muted u-nowrap"><?= e(fdatetime((string) $entry['created_at'])) ?></td>
              <td class="u-small">
                <?= e((string) $entry['actor_name']) ?>
                <span class="sub-cell"><?= e(labelize((string) $entry['actor_type'])) ?></span>
              </td>
              <td class="u-small">
                <?= e((string) ($entry['description'] !== '' ? $entry['description'] : labelize((string) $entry['action']))) ?>
                <span class="sub-cell ref"><?= e((string) $entry['action']) ?></span>
              </td>
              <td class="u-small">
                <?php $link = $entityLink($entry); ?>
                <?php if ($link !== null): ?>
                  <a href="<?= e($link) ?>"><?= e(labelize((string) $entry['entity_type'])) ?> #<?= (int) $entry['entity_id'] ?></a>
                <?php else: ?>
                  <span class="u-faint">—</span>
                <?php endif; ?>
              </td>
              <td class="u-small u-faint u-mono"><?= e((string) $entry['ip_address']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php if ($lastPage > 1): ?>
  <nav class="pagination" aria-label="Pagination">
    <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>" rel="prev">‹</a><?php endif; ?>
    <span class="current">Page <?= (int) $page ?> of <?= (int) $lastPage ?></span>
    <?php if ($page < $lastPage): ?><a href="?page=<?= $page + 1 ?>" rel="next">›</a><?php endif; ?>
  </nav>
<?php endif; ?>
