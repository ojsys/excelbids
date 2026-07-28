<?php
/**
 * @var array<string,mixed>            $client
 * @var array<int,array<string,mixed>> $messages
 * @var array<int,array<string,mixed>> $bids
 */

$clientId = (int) $client['id'];
?>

<div class="u-between u-mb">
  <div class="u-flex">
    <span class="ref"><?= e((string) $client['reference']) ?></span>
    <a href="<?= e(path('admin/clients/' . $clientId)) ?>" class="u-small">Open client record →</a>
  </div>
  <a href="<?= e(path('admin/messages')) ?>" class="btn btn-subtle btn-sm">← All conversations</a>
</div>

<section class="card content-narrow">
  <div class="card-head">
    <div><h2>Conversation</h2><div class="sub">Visible to this client's portal users</div></div>
  </div>

  <div class="card-body">
    <?php if (!$messages): ?>
      <p class="u-small u-muted u-center" style="padding:24px 0;">
        No messages yet. Anything you send here appears in their portal and is emailed to them.
      </p>
    <?php else: ?>
      <div class="thread" data-scroll-bottom>
        <?php foreach ($messages as $message): ?>
          <div class="msg msg-<?= e((string) $message['sender_type']) ?><?= $message['sender_type'] === 'client' && $message['read_at'] === null ? ' msg-unread' : '' ?>">
            <div class="msg-meta">
              <?= e((string) $message['sender_name']) ?> · <?= e(fdatetime((string) $message['created_at'])) ?>
              <?php if (!empty($message['bid_reference'])): ?> · <?= e((string) $message['bid_reference']) ?><?php endif; ?>
            </div>
            <div class="msg-body"><?= e((string) $message['body']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card-foot" style="display:block;">
    <form method="post" action="<?= e(path('admin/messages/' . $clientId)) ?>" data-guard-submit>
      <?= csrf_field() ?>
      <div class="field">
        <label for="body">Reply</label>
        <textarea class="textarea sm" id="body" name="body" data-autogrow required maxlength="10000"
                  placeholder="Write your reply…"></textarea>
      </div>
      <div class="u-between">
        <div class="field u-mb0" style="min-width:220px;">
          <label for="bid_id" class="u-small">Relates to</label>
          <select class="select" id="bid_id" name="bid_id">
            <option value="">General — not a specific bid</option>
            <?php foreach ($bids as $bid): ?>
              <option value="<?= (int) $bid['id'] ?>"><?= e((string) $bid['reference']) ?> — <?= e(str_excerpt((string) $bid['title'], 40)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-red">Send message</button>
      </div>
    </form>
  </div>
</section>
