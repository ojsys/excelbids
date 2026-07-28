<?php
/**
 * @var array<int,array<string,mixed>> $users
 * @var array<string,string>           $roles
 * @var int|null                       $currentId
 */
?>

<div class="alert alert-info">
  <strong>Roles control what each person can reach.</strong>
  <?php foreach ($roles as $key => $description): ?>
    <br><span class="badge badge-neutral" style="margin:4px 6px 0 0;"><?= e(labelize($key)) ?></span><span class="u-small"><?= e($description) ?></span>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr><th>Name</th><th>Role</th><th>Status</th><th>Last signed in</th><th class="actions"></th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <?php $isSelf = (int) $user['id'] === (int) $currentId; ?>
          <tr>
            <td>
              <div class="u-flex" style="gap:11px;">
                <span class="avatar" style="background:var(--navy);color:#fff;"><?= e(initials((string) $user['name'])) ?></span>
                <div style="min-width:0;">
                  <span class="primary-cell">
                    <?= e((string) $user['name']) ?>
                    <?php if ($isSelf): ?><span class="u-faint u-small">(you)</span><?php endif; ?>
                  </span>
                  <span class="sub-cell"><?= e((string) $user['email']) ?></span>
                  <?php if (!empty($user['job_title'])): ?>
                    <span class="sub-cell u-faint"><?= e((string) $user['job_title']) ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td><span class="badge badge-<?= $user['role'] === 'admin' ? 'info' : 'neutral' ?>"><?= e(labelize((string) $user['role'])) ?></span></td>
            <td>
              <?php if ((int) $user['is_active'] === 1): ?>
                <span class="badge badge-success">Active</span>
              <?php else: ?>
                <span class="badge badge-muted">Suspended</span>
              <?php endif; ?>
            </td>
            <td class="u-small u-muted">
              <?php if (!empty($user['last_login_at'])): ?>
                <?= e(fdatetime((string) $user['last_login_at'])) ?>
                <span class="sub-cell u-mono"><?= e((string) $user['last_login_ip']) ?></span>
              <?php else: ?>
                <span class="u-faint">Never</span>
              <?php endif; ?>
            </td>
            <td class="actions">
              <a href="<?= e(path('admin/users/' . $user['id'] . '/edit')) ?>" class="btn btn-ghost btn-sm">Edit</a>
              <?php if (!$isSelf): ?>
                <form method="post" action="<?= e(path('admin/users/' . $user['id'] . '/toggle')) ?>" style="display:inline;">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-subtle btn-sm">
                    <?= (int) $user['is_active'] === 1 ? 'Suspend' : 'Restore' ?>
                  </button>
                </form>
                <form method="post" action="<?= e(path('admin/users/' . $user['id'] . '/delete')) ?>" style="display:inline;"
                      data-confirm="Delete <?= e((string) $user['name']) ?>'s account? Their bids and clients will become unassigned.">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-subtle btn-sm" aria-label="Delete account">✕</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
