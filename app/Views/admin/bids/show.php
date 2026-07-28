<?php
/**
 * Bid detail — the working screen for a single bid.
 *
 * @var array<string,mixed>            $bid
 * @var array<int,array<string,mixed>> $qaChecks
 * @var int                            $qaProgress
 * @var array<int,array<string,mixed>> $tasks
 * @var array<int,array<string,mixed>> $events
 * @var array<int,array<string,mixed>> $documents
 * @var array<int,array<string,mixed>> $staff
 */

use App\Core\Auth;
use App\Core\Settings;
use App\Core\Uploader;
use App\Models\Bid;
use App\Models\Document;

$id = (int) $bid['id'];
$deadline = Bid::deadlineState($bid);
$currentStageIndex = Bid::stageIndex((string) $bid['stage']);
$canManage = Auth::can('bids.manage');
$qaTone = $qaProgress === 100 ? '' : ($qaProgress >= 50 ? ' is-mid' : ' is-low');
?>

<div class="u-between u-mb">
  <div class="u-flex">
    <span class="ref"><?= e((string) $bid['reference']) ?></span>
    <span class="badge badge-<?= e(Bid::statusTone((string) $bid['status'])) ?>">
      <?= e(Bid::STATUSES[$bid['status']] ?? '') ?>
    </span>
    <span class="deadline <?= e($deadline['level']) ?>"><?= e($deadline['label']) ?></span>
  </div>
  <a href="<?= e(path('admin/bids')) ?>" class="btn btn-subtle btn-sm">← All bids</a>
</div>

<!-- Pipeline -->
<section class="card">
  <div class="card-body">
    <div class="stage-rail">
      <?php foreach (Bid::STAGES as $key => $label): ?>
        <?php
          $index = Bid::stageIndex($key);
          $class = $index < $currentStageIndex ? 'done' : ($index === $currentStageIndex ? 'current' : '');
        ?>
        <div class="stage-node <?= e($class) ?>">
          <span class="pip"><?= $index < $currentStageIndex ? '✓' : sprintf('%02d', $index + 1) ?></span>
          <span class="name"><?= e($label) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($canManage): ?>
    <div class="card-foot">
      <form method="post" action="<?= e(path('admin/bids/' . $id . '/stage')) ?>" class="inline-form">
        <?= csrf_field() ?>
        <label for="stage" class="u-small u-muted u-nowrap" style="align-self:center;">Move to stage</label>
        <select class="select" id="stage" name="stage" style="flex:0 1 190px;">
          <?php foreach (Bid::STAGES as $key => $label): ?>
            <option value="<?= e($key) ?>"<?= $bid['stage'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-navy btn-sm">Update stage</button>
      </form>

      <form method="post" action="<?= e(path('admin/bids/' . $id . '/status')) ?>" class="inline-form" style="margin-left:auto;">
        <?= csrf_field() ?>
        <label for="status" class="u-small u-muted u-nowrap" style="align-self:center;">Status</label>
        <select class="select" id="status" name="status" style="flex:0 1 160px;">
          <?php foreach (Bid::STATUSES as $key => $label): ?>
            <option value="<?= e($key) ?>"<?= $bid['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-red btn-sm">Set status</button>
      </form>
    </div>
  <?php endif; ?>
</section>

<div class="grid grid-main">

  <div>
    <!-- QA sign-off -->
    <section class="card" id="qa">
      <div class="card-head">
        <div>
          <h2>Quality assurance sign-off</h2>
          <div class="sub">Every check must pass before submission</div>
        </div>
        <div class="head-actions u-flex">
          <span class="meter<?= e($qaTone) ?>" style="width:90px;"><span style="width:<?= (int) $qaProgress ?>%"></span></span>
          <strong class="u-small"><?= (int) $qaProgress ?>%</strong>
        </div>
      </div>
      <div class="card-body qa-sheet">
        <?php if (!$qaChecks): ?>
          <p class="u-muted u-small u-mb0">No QA checklist is configured. Add checks under Website Content → QA checklist.</p>
        <?php endif; ?>

        <?php foreach ($qaChecks as $check): ?>
          <div class="qa-row">
            <?php if ($canManage): ?>
              <form method="post" action="<?= e(path('admin/bids/' . $id . '/qa/' . $check['id'])) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="qa-check<?= (int) $check['is_passed'] === 1 ? ' passed' : '' ?>"
                        aria-label="<?= (int) $check['is_passed'] === 1 ? 'Re-open' : 'Sign off' ?> <?= e((string) $check['title']) ?>"></button>
              </form>
            <?php else: ?>
              <span class="qa-check<?= (int) $check['is_passed'] === 1 ? ' passed' : '' ?>" aria-hidden="true"></span>
            <?php endif; ?>

            <div class="qa-body">
              <h4><?= e((string) $check['title']) ?></h4>
              <?php if ((int) $check['is_passed'] === 1): ?>
                <div class="qa-meta">
                  Signed off by <?= e((string) ($check['checked_by_name'] ?? 'a team member')) ?>
                  · <?= e(fdatetime((string) $check['checked_at'])) ?>
                </div>
              <?php else: ?>
                <div class="qa-meta">Outstanding</div>
              <?php endif; ?>
              <?php if (!empty($check['notes'])): ?>
                <p class="qa-note"><?= e((string) $check['notes']) ?></p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if ($qaProgress === 100 && $qaChecks): ?>
          <div class="u-between" style="margin-top:18px;padding-top:18px;border-top:1px solid var(--line);">
            <span style="font-family:'Caveat',cursive;font-size:26px;">Cleared for submission</span>
            <span class="u-mono u-small u-faint u-right">QA COMPLETE<br><?= e(date('d / m / Y')) ?></span>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Tasks -->
    <section class="card" id="tasks">
      <div class="card-head">
        <div><h2>Tasks</h2><div class="sub"><?= count(array_filter($tasks, static fn ($t) => (int) $t['is_done'] === 0)) ?> outstanding</div></div>
      </div>

      <?php if ($tasks): ?>
        <div class="card-body tight">
          <?php foreach ($tasks as $task): ?>
            <div class="u-between" style="padding:9px 0;border-bottom:1px solid var(--line-soft);">
              <div class="u-flex" style="min-width:0;gap:11px;">
                <?php if ($canManage): ?>
                  <form method="post" action="<?= e(path('admin/bids/' . $id . '/tasks/' . $task['id'] . '/toggle')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="qa-check<?= (int) $task['is_done'] === 1 ? ' passed' : '' ?>"
                            aria-label="Toggle task"></button>
                  </form>
                <?php endif; ?>
                <div style="min-width:0;">
                  <div class="u-small" style="<?= (int) $task['is_done'] === 1 ? 'text-decoration:line-through;color:var(--ink-faint);' : 'font-weight:600;' ?>">
                    <?= e((string) $task['title']) ?>
                  </div>
                  <span class="u-small u-faint">
                    <?= e((string) ($task['assignee_name'] ?? 'Unassigned')) ?>
                    <?php if (!empty($task['due_date'])): ?> · due <?= e(fdate((string) $task['due_date'], 'j M')) ?><?php endif; ?>
                  </span>
                </div>
              </div>
              <?php if ($canManage): ?>
                <form method="post" action="<?= e(path('admin/bids/' . $id . '/tasks/' . $task['id'] . '/delete')) ?>"
                      data-confirm="Remove this task?">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-subtle btn-sm" aria-label="Remove task">✕</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($canManage): ?>
        <div class="card-foot">
          <form method="post" action="<?= e(path('admin/bids/' . $id . '/tasks')) ?>" class="inline-form" style="width:100%;">
            <?= csrf_field() ?>
            <input class="input" type="text" name="title" placeholder="Add a task…" required maxlength="255" style="flex:2 1 220px;">
            <select class="select" name="assignee_id" style="flex:1 1 150px;">
              <option value="">Unassigned</option>
              <?php foreach ($staff as $member): ?>
                <option value="<?= (int) $member['id'] ?>"><?= e((string) $member['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <input class="input" type="date" name="due_date" style="flex:0 1 150px;" aria-label="Due date">
            <button type="submit" class="btn btn-primary btn-sm">Add</button>
          </form>
        </div>
      <?php endif; ?>
    </section>

    <!-- Documents -->
    <section class="card" id="documents">
      <div class="card-head">
        <div><h2>Documents</h2><div class="sub"><?= count($documents) ?> attached</div></div>
      </div>

      <div class="card-body">
        <?php if (!$documents): ?>
          <p class="u-muted u-small u-mb0">Nothing attached yet. Upload the ITT, drafts and the final submission here.</p>
        <?php endif; ?>

        <?php foreach ($documents as $document): ?>
          <div class="doc-row">
            <span class="doc-ext" aria-hidden="true"><?= e(Document::extension((string) $document['original_name'])) ?></span>
            <div class="doc-info">
              <strong><?= e((string) $document['original_name']) ?></strong>
              <span>
                <?= e(Document::CATEGORIES[$document['category']] ?? 'General') ?>
                · <?= e(filesize_human((int) $document['size_bytes'])) ?>
                · <?= e(fdate((string) $document['created_at'], 'j M Y')) ?>
                <?= $document['uploader_type'] === 'client' ? ' · uploaded by the client' : '' ?>
              </span>
            </div>
            <div class="doc-actions">
              <?php if ((int) $document['visible_to_client'] === 1): ?>
                <span class="badge badge-info" title="The client can see this in their portal">Shared</span>
              <?php endif; ?>
              <a class="btn btn-ghost btn-sm" href="<?= e(path('admin/documents/' . $document['id'] . '/download')) ?>">Download</a>
              <?php if ($canManage): ?>
                <form method="post" action="<?= e(path('admin/documents/' . $document['id'] . '/visibility')) ?>">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-subtle btn-sm">
                    <?= (int) $document['visible_to_client'] === 1 ? 'Unshare' : 'Share' ?>
                  </button>
                </form>
                <form method="post" action="<?= e(path('admin/documents/' . $document['id'] . '/delete')) ?>"
                      data-confirm="Delete &quot;<?= e((string) $document['original_name']) ?>&quot;? This cannot be undone.">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-subtle btn-sm" aria-label="Delete document">✕</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($canManage): ?>
        <div class="card-foot">
          <form method="post" action="<?= e(path('admin/bids/' . $id . '/documents')) ?>"
                enctype="multipart/form-data" class="inline-form" style="width:100%;" data-guard-submit>
            <?= csrf_field() ?>
            <input class="input" type="file" name="document" required style="flex:2 1 220px;padding:6px;">
            <select class="select" name="category" style="flex:1 1 160px;">
              <?php foreach (Document::CATEGORIES as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $key === 'tender_pack' ? ' selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
            <label class="checkline" style="flex:0 0 auto;align-self:center;">
              <input type="checkbox" name="visible_to_client" value="1"><span>Share with client</span>
            </label>
            <button type="submit" class="btn btn-primary btn-sm">Upload</button>
          </form>
          <p class="u-small u-faint u-mb0" style="width:100%;">
            Up to <?= e(filesize_human(Uploader::maxBytes())) ?>. Accepted: <?= e(implode(', ', Uploader::allowedExtensions())) ?>.
          </p>
        </div>
      <?php endif; ?>
    </section>

    <!-- Timeline -->
    <section class="card" id="timeline">
      <div class="card-head"><h2>Timeline</h2></div>

      <?php if ($canManage): ?>
        <div class="card-body" style="border-bottom:1px solid var(--line-soft);">
          <form method="post" action="<?= e(path('admin/bids/' . $id . '/notes')) ?>" data-guard-submit>
            <?= csrf_field() ?>
            <div class="field u-mb0">
              <label for="note-body">Add a note</label>
              <textarea class="textarea sm" id="note-body" name="body" data-autogrow
                        placeholder="What happened, what was agreed, what happens next…" required></textarea>
            </div>
            <div class="u-between u-mt">
              <label class="checkline">
                <input type="checkbox" name="visible_to_client" value="1">
                <span>Share with the client and email their portal users</span>
              </label>
              <button type="submit" class="btn btn-primary btn-sm">Save note</button>
            </div>
          </form>
        </div>
      <?php endif; ?>

      <div class="card-body">
        <?php if (!$events): ?>
          <p class="u-muted u-small u-mb0">Nothing recorded yet.</p>
        <?php else: ?>
          <div class="timeline">
            <?php foreach ($events as $event): ?>
              <div class="tl-item <?= $event['event_type'] === 'status' ? 'is-status' : ($event['actor_type'] === 'client' ? 'is-client' : '') ?>">
                <div class="tl-meta">
                  <?= e(fdatetime((string) $event['created_at'])) ?> — <?= e((string) $event['actor_name']) ?>
                  <?php if ((int) $event['visible_to_client'] === 1): ?>
                    <span class="badge badge-info" style="margin-left:6px;font-size:10px;padding:1px 6px;">Client can see</span>
                  <?php endif; ?>
                </div>
                <div class="tl-body"><?= e((string) $event['body']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <!-- Sidebar: the facts -->
  <div>
    <section class="card">
      <div class="card-head"><h3>Bid details</h3></div>
      <div class="card-body tight">
        <dl class="dl">
          <dt>Client</dt>
          <dd><a href="<?= e(path('admin/clients/' . $bid['client_id'])) ?>"><?= e((string) $bid['organisation']) ?></a></dd>

          <dt>Buyer</dt><dd><?= e((string) ($bid['buyer'] !== '' ? $bid['buyer'] : '—')) ?></dd>
          <dt>Portal</dt>
          <dd>
            <?= e((string) ($bid['portal'] !== '' ? $bid['portal'] : '—')) ?>
            <?php if (!empty($bid['portal_ref'])): ?><br><span class="ref"><?= e((string) $bid['portal_ref']) ?></span><?php endif; ?>
          </dd>
          <dt>Service</dt><dd><?= e((string) ($bid['service_type'] !== '' ? $bid['service_type'] : '—')) ?></dd>
          <dt>Sector</dt><dd><?= e((string) ($bid['sector'] !== '' ? $bid['sector'] : '—')) ?></dd>
          <dt>Owner</dt><dd><?= e((string) ($bid['owner_name'] ?? 'Unassigned')) ?></dd>
        </dl>
      </div>
    </section>

    <section class="card">
      <div class="card-head"><h3>Key dates</h3></div>
      <div class="card-body tight">
        <dl class="dl">
          <dt>Clarifications close</dt><dd><?= e(fdate((string) ($bid['clarification_due'] ?? ''))) ?></dd>
          <dt>Submission due</dt>
          <dd>
            <strong><?= e(fdatetime((string) ($bid['submission_due'] ?? ''))) ?></strong>
            <?php if (!empty($bid['submission_due'])): ?>
              <br><span class="deadline <?= e($deadline['level']) ?>"><?= e($deadline['label']) ?></span>
            <?php endif; ?>
          </dd>
          <dt>Submitted</dt><dd><?= e(fdatetime((string) ($bid['submitted_at'] ?? ''))) ?></dd>
          <dt>Decision expected</dt><dd><?= e(fdate((string) ($bid['decision_expected_on'] ?? ''))) ?></dd>
          <dt>Outcome dated</dt><dd><?= e(fdate((string) ($bid['outcome_on'] ?? ''))) ?></dd>
        </dl>
      </div>
    </section>

    <section class="card">
      <div class="card-head"><h3>Commercials</h3></div>
      <div class="card-body tight">
        <dl class="dl">
          <dt>Contract value</dt>
          <dd><strong><?= (float) $bid['contract_value'] > 0 ? e(money($bid['contract_value'])) : '—' ?></strong>
            <?php if (!empty($bid['contract_length'])): ?><br><span class="u-small u-faint">over <?= e((string) $bid['contract_length']) ?></span><?php endif; ?>
          </dd>
          <dt>Our fee</dt>
          <dd><?= (float) $bid['fee_amount'] > 0 ? e(money($bid['fee_amount'], true)) : '—' ?>
            <span class="u-small u-faint">(<?= e(Bid::FEE_TYPES[$bid['fee_type']] ?? '') ?>)</span></dd>
          <dt>Win probability</dt>
          <dd>
            <span class="u-flex" style="gap:8px;">
              <span class="meter" style="width:70px;"><span style="width:<?= (int) $bid['win_probability'] ?>%"></span></span>
              <?= (int) $bid['win_probability'] ?>%
            </span>
          </dd>
          <?php if ($bid['evaluation_score'] !== null): ?>
            <dt>Evaluation score</dt>
            <dd><strong><?= e(rtrim(rtrim(number_format((float) $bid['evaluation_score'], 2), '0'), '.')) ?></strong>
              / <?= e(rtrim(rtrim(number_format((float) $bid['evaluation_max'], 2), '0'), '.')) ?></dd>
          <?php endif; ?>
        </dl>
      </div>
    </section>

    <?php if (!empty($bid['summary'])): ?>
      <section class="card">
        <div class="card-head"><h3>Summary</h3></div>
        <div class="card-body"><p class="u-small u-mb0" style="white-space:pre-wrap;"><?= e((string) $bid['summary']) ?></p></div>
      </section>
    <?php endif; ?>

    <?php if (!empty($bid['outcome_notes'])): ?>
      <section class="card">
        <div class="card-head"><h3>Outcome &amp; feedback</h3></div>
        <div class="card-body"><p class="u-small u-mb0" style="white-space:pre-wrap;"><?= e((string) $bid['outcome_notes']) ?></p></div>
      </section>
    <?php endif; ?>

    <?php if ($canManage): ?>
      <section class="card">
        <div class="card-body">
          <a href="<?= e(path('admin/bids/' . $id . '/edit')) ?>" class="btn btn-ghost btn-block u-mb">Edit this bid</a>
          <form method="post" action="<?= e(path('admin/bids/' . $id . '/delete')) ?>"
                data-confirm="Delete bid <?= e((string) $bid['reference']) ?> and all its documents? This cannot be undone.">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger btn-block">Delete bid</button>
          </form>
        </div>
      </section>
    <?php endif; ?>
  </div>

</div>
