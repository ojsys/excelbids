<?php
/**
 * @var array<int,array<string,mixed>> $entries
 * @var int                            $page
 * @var int                            $lastPage
 * @var int                            $total
 */
?>

<div class="tabs">
  <a href="<?= e(path('admin/settings/general')) ?>">General</a>
  <a href="<?= e(path('admin/settings/mail')) ?>">Email</a>
  <a href="<?= e(path('admin/settings/portal')) ?>">Client portal</a>
  <a href="<?= e(path('admin/settings/seo')) ?>">SEO</a>
  <a href="<?= e(path('admin/logs/email')) ?>" class="active">Email log</a>
</div>

<div class="card">
  <div class="card-head">
    <div>
      <h2>Email log</h2>
      <div class="sub">Every message the system has tried to send — <?= number_format($total) ?> in total</div>
    </div>
  </div>

  <?php if (!$entries): ?>
    <div class="empty">
      <span class="mark">✉</span>
      <h3>No emails sent yet</h3>
      <p>Consultation requests, portal invitations and password resets are all recorded here so you can confirm they were sent.</p>
      <a href="<?= e(path('admin/settings/mail')) ?>" class="btn btn-ghost btn-sm">Send a test email</a>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th style="width:150px;">Sent</th><th>To</th><th>Subject</th><th>Route</th><th>Result</th></tr>
        </thead>
        <tbody>
          <?php foreach ($entries as $entry): ?>
            <tr>
              <td class="u-small u-muted u-nowrap"><?= e(fdatetime((string) $entry['created_at'])) ?></td>
              <td class="u-small"><?= e((string) $entry['to_email']) ?></td>
              <td class="u-small"><?= e(str_excerpt((string) $entry['subject'], 60)) ?></td>
              <td class="u-small u-faint u-mono"><?= e((string) $entry['transport']) ?></td>
              <td>
                <?php if ($entry['status'] === 'sent'): ?>
                  <span class="badge badge-success">Sent</span>
                <?php else: ?>
                  <span class="badge badge-danger">Failed</span>
                  <?php if (!empty($entry['error'])): ?>
                    <span class="sub-cell u-small" style="color:var(--red);"><?= e(str_excerpt((string) $entry['error'], 90)) ?></span>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
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
