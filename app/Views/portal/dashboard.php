<?php
/**
 * @var array<string,mixed>            $stats
 * @var array<int,array<string,mixed>> $upcoming
 * @var array<int,array<string,mixed>> $bids
 * @var int                            $unread
 * @var array<int,array<string,mixed>> $documents
 * @var array<int,array<string,mixed>> $activity
 */

use App\Core\Settings;
use App\Models\Bid;
use App\Models\Document;
?>

<div class="kpi-grid">
  <div class="kpi is-red">
    <div class="label">Live bids</div>
    <div class="value"><?= (int) $stats['open_bids'] ?></div>
    <div class="meta"><?= (int) $stats['total'] ?> in total with us</div>
  </div>
  <div class="kpi is-green">
    <div class="label">Bids won</div>
    <div class="value"><?= (int) $stats['won'] ?></div>
    <div class="meta"><?= e((string) $stats['win_rate']) ?>% of decided bids</div>
  </div>
  <div class="kpi is-navy">
    <div class="label">Contract value won</div>
    <div class="value"><?= e(money($stats['value_won'])) ?></div>
    <div class="meta">Awarded to date</div>
  </div>
  <div class="kpi">
    <div class="label">In pipeline</div>
    <div class="value"><?= e(money($stats['pipeline_value'])) ?></div>
    <div class="meta">Value of bids in progress</div>
  </div>
</div>

<?php if ($unread > 0 && Settings::bool('portal_messaging', true)): ?>
  <div class="alert alert-info">
    <strong>You have <?= (int) $unread ?> unread <?= $unread === 1 ? 'message' : 'messages' ?> from your bid team.</strong>
    <a href="<?= e(path('portal/messages')) ?>">Read <?= $unread === 1 ? 'it' : 'them' ?> →</a>
  </div>
<?php endif; ?>

<div class="grid grid-main">

  <div>
    <section class="card">
      <div class="card-head">
        <div><h2>What is coming up</h2><div class="sub">Your live bids, by deadline</div></div>
        <div class="head-actions"><a href="<?= e(path('portal/bids')) ?>" class="btn btn-ghost btn-sm">All bids</a></div>
      </div>

      <?php if (!$upcoming): ?>
        <div class="empty">
          <span class="mark">▤</span>
          <h3>No live bids right now</h3>
          <p>When we start work on an opportunity for you, it will appear here with its deadline and progress.</p>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Bid</th><th>Stage</th><th>Status</th><th>Deadline</th></tr></thead>
            <tbody>
              <?php foreach ($upcoming as $bid): ?>
                <?php $deadline = Bid::deadlineState($bid); ?>
                <tr>
                  <td>
                    <span class="primary-cell"><a href="<?= e(path('portal/bids/' . $bid['id'])) ?>"><?= e(str_excerpt((string) $bid['title'], 56)) ?></a></span>
                    <span class="sub-cell ref"><?= e((string) $bid['reference']) ?><?php if (!empty($bid['buyer'])): ?> · <?= e(str_excerpt((string) $bid['buyer'], 32)) ?><?php endif; ?></span>
                  </td>
                  <td><span class="badge badge-neutral"><?= e(Bid::STAGES[$bid['stage']] ?? '') ?></span></td>
                  <td><span class="badge badge-<?= e(Bid::statusTone((string) $bid['status'])) ?>"><?= e(Bid::STATUSES[$bid['status']] ?? '') ?></span></td>
                  <td>
                    <span class="u-small u-nowrap"><?= e(fdatetime((string) $bid['submission_due'], 'j M Y')) ?></span><br>
                    <span class="deadline <?= e($deadline['level']) ?>"><?= e($deadline['label']) ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <?php if ($activity): ?>
      <section class="card">
        <div class="card-head"><div><h2>Latest updates</h2><div class="sub">From your bid team</div></div></div>
        <div class="card-body">
          <div class="timeline">
            <?php foreach ($activity as $event): ?>
              <div class="tl-item <?= $event['event_type'] === 'status' ? 'is-status' : '' ?>">
                <div class="tl-meta">
                  <?= e(fdatetime((string) $event['created_at'])) ?> ·
                  <a href="<?= e(path('portal/bids/' . $event['bid_id'])) ?>"><?= e((string) $event['bid_reference']) ?></a>
                </div>
                <div class="tl-body"><?= e((string) $event['body']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>
  </div>

  <div>
    <?php if ($documents): ?>
      <section class="card">
        <div class="card-head">
          <div><h3>Recent documents</h3></div>
          <div class="head-actions"><a href="<?= e(path('portal/documents')) ?>" class="btn btn-subtle btn-sm">All</a></div>
        </div>
        <div class="card-body tight">
          <?php foreach ($documents as $document): ?>
            <div class="doc-row">
              <span class="doc-ext" aria-hidden="true"><?= e(Document::extension((string) $document['original_name'])) ?></span>
              <div class="doc-info">
                <strong><?= e(str_excerpt((string) $document['original_name'], 34)) ?></strong>
                <span><?= e(filesize_human((int) $document['size_bytes'])) ?> · <?= e(fdate((string) $document['created_at'], 'j M')) ?></span>
              </div>
              <div class="doc-actions">
                <a class="btn btn-ghost btn-sm" href="<?= e(path('portal/documents/' . $document['id'] . '/download')) ?>">Get</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <section class="card">
      <div class="card-head"><h3>Your bid history</h3></div>
      <div class="card-body tight">
        <?php if (!$bids): ?>
          <p class="u-small u-muted u-mb0">Nothing on record yet.</p>
        <?php else: ?>
          <?php foreach (array_slice($bids, 0, 8) as $bid): ?>
            <a href="<?= e(path('portal/bids/' . $bid['id'])) ?>" class="u-between" style="padding:9px 0;border-bottom:1px solid var(--line-soft);">
              <span style="min-width:0;">
                <span class="u-small" style="font-weight:600;display:block;"><?= e(str_excerpt((string) $bid['title'], 32)) ?></span>
                <span class="ref"><?= e((string) $bid['reference']) ?></span>
              </span>
              <span class="badge badge-<?= e(Bid::statusTone((string) $bid['status'])) ?>"><?= e(Bid::STATUSES[$bid['status']] ?? '') ?></span>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <section class="card">
      <div class="card-head"><h3>Need something?</h3></div>
      <div class="card-body">
        <p class="u-small u-muted">
          Spotted an opportunity, or need a document from us? Message your bid team and we will pick it up.
        </p>
        <?php if (Settings::bool('portal_messaging', true)): ?>
          <a href="<?= e(path('portal/messages')) ?>" class="btn btn-red btn-sm btn-block">Message the team</a>
        <?php else: ?>
          <a href="mailto:<?= e((string) Settings::get('contact_email', '')) ?>" class="btn btn-red btn-sm btn-block">Email us</a>
        <?php endif; ?>
      </div>
    </section>
  </div>

</div>
