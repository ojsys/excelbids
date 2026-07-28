<?php
/**
 * @var array<int,array<string,mixed>> $messages
 * @var array<int,array<string,mixed>> $bids
 */
?>

<section class="card content-narrow">
  <div class="card-head">
    <div><h2>Your bid team</h2><div class="sub">Messages here reach the writers working on your bids</div></div>
  </div>

  <div class="card-body">
    <?php if (!$messages): ?>
      <p class="u-small u-muted u-center" style="padding:24px 0;">
        No messages yet. Ask us anything — about a live bid, a deadline, or an opportunity you have spotted.
      </p>
    <?php else: ?>
      <div class="thread" data-scroll-bottom>
        <?php foreach ($messages as $message): ?>
          <?php
            // From the client's point of view, their own messages sit on the right.
            $isMine = $message['sender_type'] === 'client';
          ?>
          <div class="msg msg-<?= $isMine ? 'client' : 'staff' ?>">
            <div class="msg-meta">
              <?= $isMine ? 'You' : e((string) $message['sender_name']) ?>
              · <?= e(fdatetime((string) $message['created_at'])) ?>
              <?php if (!empty($message['bid_reference'])): ?> · <?= e((string) $message['bid_reference']) ?><?php endif; ?>
            </div>
            <div class="msg-body"><?= e((string) $message['body']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card-foot" style="display:block;">
    <form method="post" action="<?= e(path('portal/messages')) ?>" data-guard-submit>
      <?= csrf_field() ?>
      <div class="field">
        <label for="body">Your message</label>
        <textarea class="textarea sm" id="body" name="body" data-autogrow required maxlength="10000"
                  placeholder="Write your message…"></textarea>
      </div>
      <div class="u-between">
        <div class="field u-mb0" style="min-width:220px;">
          <label for="bid_id" class="u-small">About</label>
          <select class="select" id="bid_id" name="bid_id">
            <option value="">Something else</option>
            <?php foreach ($bids as $bid): ?>
              <option value="<?= (int) $bid['id'] ?>"><?= e((string) $bid['reference']) ?> — <?= e(str_excerpt((string) $bid['title'], 38)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-red">Send message</button>
      </div>
    </form>
  </div>
</section>
