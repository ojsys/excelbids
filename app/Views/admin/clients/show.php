<?php
/**
 * @var array<string,mixed>            $client
 * @var array<string,mixed>            $stats
 * @var array<int,array<string,mixed>> $bids
 * @var array<int,array<string,mixed>> $portalUsers
 * @var array<int,array<string,mixed>> $documents
 * @var int                            $unread
 * @var array<int,array<string,mixed>> $activity
 */

use App\Core\Auth;
use App\Core\Settings;
use App\Core\View;
use App\Models\Client;
use App\Models\Document;

$id = (int) $client['id'];
$canManage = Auth::can('clients.manage');
$portalOn = Settings::bool('portal_enabled', true);

$statusTone = match ((string) $client['status']) {
    'active'   => 'success',
    'prospect' => 'info',
    'on_hold'  => 'warning',
    default    => 'muted',
};
?>

<div class="u-between u-mb">
  <div class="u-flex">
    <span class="ref"><?= e((string) $client['reference']) ?></span>
    <span class="badge badge-<?= e($statusTone) ?>"><?= e(Client::STATUSES[$client['status']] ?? '') ?></span>
    <?php if (!empty($client['nda_signed_on'])): ?>
      <span class="badge badge-neutral">NDA signed <?= e(fdate((string) $client['nda_signed_on'], 'M Y')) ?></span>
    <?php endif; ?>
  </div>
  <a href="<?= e(path('admin/clients')) ?>" class="btn btn-subtle btn-sm">← All clients</a>
</div>

<div class="kpi-grid">
  <div class="kpi is-red">
    <div class="label">Open bids</div>
    <div class="value"><?= (int) $stats['open_bids'] ?></div>
    <div class="meta"><?= (int) $stats['total'] ?> in total</div>
  </div>
  <div class="kpi is-green">
    <div class="label">Win rate</div>
    <div class="value"><?= e((string) $stats['win_rate']) ?><small>%</small></div>
    <div class="meta"><?= (int) $stats['won'] ?> won, <?= (int) $stats['lost'] ?> lost</div>
  </div>
  <div class="kpi is-navy">
    <div class="label">Value won</div>
    <div class="value"><?= e(money($stats['value_won'])) ?></div>
    <div class="meta">Awarded contract value</div>
  </div>
  <div class="kpi">
    <div class="label">Pipeline</div>
    <div class="value"><?= e(money($stats['pipeline_value'])) ?></div>
    <div class="meta">Value of live bids</div>
  </div>
</div>

<div class="grid grid-main">

  <div>
    <!-- Bids -->
    <section class="card">
      <div class="card-head">
        <div><h2>Bids</h2><div class="sub"><?= count($bids) ?> on record</div></div>
        <?php if (Auth::can('bids.manage')): ?>
          <div class="head-actions"><a href="<?= e(path('admin/bids/create')) ?>" class="btn btn-red btn-sm">+ New bid</a></div>
        <?php endif; ?>
      </div>

      <?php if (!$bids): ?>
        <div class="empty">
          <span class="mark">▤</span>
          <h3>No bids yet</h3>
          <p>When you start work for <?= e(str_excerpt((string) $client['organisation'], 30)) ?>, the bids will be listed here.</p>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead>
              <tr><th>Bid</th><th>Stage</th><th>Status</th><th>Deadline</th><th class="num">Value</th><th>Owner</th></tr>
            </thead>
            <tbody>
              <?php foreach ($bids as $bid): ?>
                <?= View::partial('admin/partials/bid-row', ['bid' => $bid, 'showClient' => false]) ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <!-- Portal access -->
    <?php if ($portalOn): ?>
      <section class="card" id="portal">
        <div class="card-head">
          <div><h2>Client portal access</h2><div class="sub">People at this organisation who can sign in</div></div>
        </div>

        <div class="card-body">
          <?php if (!$portalUsers): ?>
            <p class="u-small u-muted">No portal logins yet. Invite their main contact so they can track bids, download documents and message the team.</p>
          <?php endif; ?>

          <?php foreach ($portalUsers as $portalUser): ?>
            <div class="u-between" style="padding:11px 0;border-bottom:1px solid var(--line-soft);">
              <div class="u-flex" style="min-width:0;">
                <span class="avatar" style="background:var(--navy);color:#fff;"><?= e(initials((string) $portalUser['name'])) ?></span>
                <div style="min-width:0;">
                  <div class="u-small" style="font-weight:600;">
                    <?= e((string) $portalUser['name']) ?>
                    <?php if ((int) $portalUser['is_primary'] === 1): ?>
                      <span class="badge badge-neutral" style="font-size:10px;padding:1px 6px;">Primary</span>
                    <?php endif; ?>
                  </div>
                  <span class="u-small u-faint"><?= e((string) $portalUser['email']) ?></span>
                  <div class="u-small u-faint">
                    <?php if ((int) $portalUser['is_active'] !== 1): ?>
                      <span class="badge badge-danger" style="font-size:10px;padding:1px 6px;">Suspended</span>
                    <?php elseif (empty($portalUser['password_hash'])): ?>
                      <span class="badge badge-warning" style="font-size:10px;padding:1px 6px;">Invitation pending</span>
                    <?php elseif (!empty($portalUser['last_login_at'])): ?>
                      Last signed in <?= e(fdatetime((string) $portalUser['last_login_at'], 'j M Y')) ?>
                    <?php else: ?>
                      Activated, not yet signed in
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <?php if ($canManage): ?>
                <div class="u-flex" style="gap:5px;">
                  <?php if (empty($portalUser['password_hash'])): ?>
                    <form method="post" action="<?= e(path('admin/clients/' . $id . '/portal-users/' . $portalUser['id'] . '/resend')) ?>">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-ghost btn-sm">Resend invite</button>
                    </form>
                  <?php endif; ?>
                  <form method="post" action="<?= e(path('admin/clients/' . $id . '/portal-users/' . $portalUser['id'] . '/toggle')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-subtle btn-sm">
                      <?= (int) $portalUser['is_active'] === 1 ? 'Suspend' : 'Restore' ?>
                    </button>
                  </form>
                  <form method="post" action="<?= e(path('admin/clients/' . $id . '/portal-users/' . $portalUser['id'] . '/delete')) ?>"
                        data-confirm="Remove portal access for <?= e((string) $portalUser['email']) ?>?">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-subtle btn-sm" aria-label="Remove">✕</button>
                  </form>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if ($canManage): ?>
          <div class="card-foot" style="display:block;">
            <form method="post" action="<?= e(path('admin/clients/' . $id . '/portal-users')) ?>" data-guard-submit>
              <?= csrf_field() ?>
              <p class="u-small" style="font-weight:600;margin:0 0 10px;">Invite someone to the portal</p>
              <div class="field-row">
                <div class="field"><label for="pu-name">Name</label>
                  <input class="input" type="text" id="pu-name" name="name" required maxlength="140"></div>
                <div class="field"><label for="pu-email">Email address</label>
                  <input class="input" type="email" id="pu-email" name="email" required maxlength="190"></div>
              </div>
              <div class="field-row">
                <div class="field"><label for="pu-job">Job title</label>
                  <input class="input" type="text" id="pu-job" name="job_title" maxlength="120"></div>
                <div class="field"><label for="pu-phone">Phone</label>
                  <input class="input" type="tel" id="pu-phone" name="phone" maxlength="40"></div>
              </div>
              <div class="u-between">
                <label class="checkline"><input type="checkbox" name="is_primary" value="1"><span>Main contact for this client</span></label>
                <button type="submit" class="btn btn-primary btn-sm">Send invitation</button>
              </div>
            </form>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <!-- Documents -->
    <section class="card">
      <div class="card-head"><div><h2>Documents</h2><div class="sub">Everything filed against this client</div></div></div>
      <div class="card-body">
        <?php if (!$documents): ?>
          <p class="u-small u-muted u-mb0">No documents yet. Files uploaded to a bid appear here too.</p>
        <?php endif; ?>

        <?php foreach ($documents as $document): ?>
          <div class="doc-row">
            <span class="doc-ext" aria-hidden="true"><?= e(Document::extension((string) $document['original_name'])) ?></span>
            <div class="doc-info">
              <strong><?= e((string) $document['original_name']) ?></strong>
              <span>
                <?php if (!empty($document['bid_reference'])): ?><?= e((string) $document['bid_reference']) ?> · <?php endif; ?>
                <?= e(filesize_human((int) $document['size_bytes'])) ?> · <?= e(fdate((string) $document['created_at'], 'j M Y')) ?>
              </span>
            </div>
            <div class="doc-actions">
              <?php if ((int) $document['visible_to_client'] === 1): ?><span class="badge badge-info">Shared</span><?php endif; ?>
              <a class="btn btn-ghost btn-sm" href="<?= e(path('admin/documents/' . $document['id'] . '/download')) ?>">Download</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>

  <!-- Sidebar -->
  <div>
    <section class="card">
      <div class="card-head"><h3>Contact details</h3></div>
      <div class="card-body tight">
        <dl class="dl">
          <dt>Main contact</dt><dd><?= e((string) ($client['contact_name'] !== '' ? $client['contact_name'] : '—')) ?></dd>
          <dt>Email</dt>
          <dd><?= $client['email'] !== '' ? '<a href="mailto:' . e((string) $client['email']) . '">' . e((string) $client['email']) . '</a>' : '—' ?></dd>
          <dt>Phone</dt>
          <dd><?= $client['phone'] !== '' ? '<a href="tel:' . e(preg_replace('/\s+/', '', (string) $client['phone'])) . '">' . e((string) $client['phone']) . '</a>' : '—' ?></dd>
          <dt>Website</dt>
          <dd><?= $client['website'] !== '' ? '<a href="' . e((string) $client['website']) . '" target="_blank" rel="noopener">' . e(str_excerpt((string) $client['website'], 28)) . '</a>' : '—' ?></dd>
          <dt>Address</dt>
          <dd>
            <?php
              $address = array_filter([
                  $client['address_line1'], $client['address_line2'],
                  $client['city'], $client['postcode'], $client['country'],
              ], static fn ($part) => (string) $part !== '');
              echo $address ? nl2br(e(implode("\n", $address))) : '—';
            ?>
          </dd>
          <dt>Company no.</dt><dd><?= e((string) ($client['company_no'] !== '' ? $client['company_no'] : '—')) ?></dd>
          <dt>Sector</dt><dd><?= e((string) ($client['sector'] !== '' ? $client['sector'] : '—')) ?></dd>
          <dt>Account manager</dt><dd><?= e((string) ($client['owner_name'] ?? 'Unassigned')) ?></dd>
          <dt>Client since</dt><dd><?= e(fdate((string) $client['created_at'])) ?></dd>
        </dl>
      </div>
      <?php if (Auth::can('messages.manage')): ?>
        <div class="card-foot">
          <a href="<?= e(path('admin/messages/' . $id)) ?>" class="btn btn-ghost btn-sm btn-block">
            Messages<?= $unread > 0 ? ' (' . (int) $unread . ' unread)' : '' ?>
          </a>
        </div>
      <?php endif; ?>
    </section>

    <?php if (!empty($client['notes'])): ?>
      <section class="card">
        <div class="card-head"><h3>Notes</h3></div>
        <div class="card-body"><p class="u-small u-mb0" style="white-space:pre-wrap;"><?= e((string) $client['notes']) ?></p></div>
      </section>
    <?php endif; ?>

    <?php if ($activity): ?>
      <section class="card">
        <div class="card-head"><h3>Recent activity</h3></div>
        <div class="card-body tight">
          <div class="timeline">
            <?php foreach ($activity as $entry): ?>
              <div class="tl-item">
                <div class="tl-meta"><?= e(fdatetime((string) $entry['created_at'], 'j M · H:i')) ?> — <?= e((string) $entry['actor_name']) ?></div>
                <div class="tl-body"><?= e((string) $entry['description']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($canManage): ?>
      <section class="card">
        <div class="card-body">
          <a href="<?= e(path('admin/clients/' . $id . '/edit')) ?>" class="btn btn-ghost btn-block u-mb">Edit client</a>
          <form method="post" action="<?= e(path('admin/clients/' . $id . '/delete')) ?>"
                data-confirm="Delete <?= e((string) $client['organisation']) ?>? Clients with bids cannot be deleted — archive them instead.">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger btn-block">Delete client</button>
          </form>
        </div>
      </section>
    <?php endif; ?>
  </div>

</div>
