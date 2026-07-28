<?php
/** @var array<int,array<string,mixed>> $conversations */
?>

<div class="card">
  <?php if (!$conversations): ?>
    <div class="empty">
      <span class="mark">❑</span>
      <h3>No conversations yet</h3>
      <p>Once a client with portal access sends a message — or you start a thread from their record — it will appear here.</p>
      <a href="<?= e(path('admin/clients')) ?>" class="btn btn-ghost btn-sm">Go to clients</a>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th>Client</th><th class="num">Messages</th><th>Last activity</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($conversations as $conversation): ?>
            <tr>
              <td>
                <span class="primary-cell">
                  <a href="<?= e(path('admin/messages/' . $conversation['client_id'])) ?>">
                    <?= e(str_excerpt((string) $conversation['organisation'], 40)) ?>
                  </a>
                </span>
                <span class="sub-cell ref"><?= e((string) $conversation['reference']) ?></span>
              </td>
              <td class="num"><?= (int) $conversation['total_messages'] ?></td>
              <td class="u-small u-muted">
                <?= e(fdatetime((string) $conversation['last_message_at'])) ?>
                <span class="sub-cell"><?= e(relative_days((string) $conversation['last_message_at'])) ?></span>
              </td>
              <td>
                <?php if ((int) $conversation['unread'] > 0): ?>
                  <span class="badge badge-danger"><?= (int) $conversation['unread'] ?> unread</span>
                <?php else: ?>
                  <span class="badge badge-muted">Read</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
