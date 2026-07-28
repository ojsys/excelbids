<?php
/**
 * @var array<string,mixed>            $bid
 * @var array<int,array<string,mixed>> $events
 * @var array<int,array<string,mixed>> $documents
 * @var int                            $qaProgress
 * @var array<int,array<string,mixed>> $qaChecks
 */

use App\Core\Settings;
use App\Core\Uploader;
use App\Models\Bid;
use App\Models\Document;

$id = (int) $bid['id'];
$deadline = Bid::deadlineState($bid);
$currentStageIndex = Bid::stageIndex((string) $bid['stage']);
$uploadsOn = Settings::bool('portal_uploads', true);
$qaTone = $qaProgress === 100 ? '' : ($qaProgress >= 50 ? ' is-mid' : ' is-low');
?>

<div class="u-between u-mb">
  <div class="u-flex">
    <span class="ref"><?= e((string) $bid['reference']) ?></span>
    <span class="badge badge-<?= e(Bid::statusTone((string) $bid['status'])) ?>"><?= e(Bid::STATUSES[$bid['status']] ?? '') ?></span>
    <span class="deadline <?= e($deadline['level']) ?>"><?= e($deadline['label']) ?></span>
  </div>
  <a href="<?= e(path('portal/bids')) ?>" class="btn btn-subtle btn-sm">← All my bids</a>
</div>

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
</section>

<div class="grid grid-main">

  <div>
    <!-- QA progress: the same four checks promised on the website -->
    <?php if ($qaChecks): ?>
      <section class="card">
        <div class="card-head">
          <div><h2>Quality assurance</h2><div class="sub">Four independent checks before anything is submitted</div></div>
          <div class="head-actions u-flex">
            <span class="meter<?= e($qaTone) ?>" style="width:90px;"><span style="width:<?= (int) $qaProgress ?>%"></span></span>
            <strong class="u-small"><?= (int) $qaProgress ?>%</strong>
          </div>
        </div>
        <div class="card-body qa-sheet">
          <?php foreach ($qaChecks as $check): ?>
            <div class="qa-row">
              <span class="qa-check<?= (int) $check['is_passed'] === 1 ? ' passed' : '' ?>" aria-hidden="true"></span>
              <div class="qa-body">
                <h4><?= e((string) $check['title']) ?></h4>
                <div class="qa-meta">
                  <?= (int) $check['is_passed'] === 1
                        ? 'Passed ' . e(fdate((string) $check['checked_at'], 'j M Y'))
                        : 'Not yet complete' ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

          <?php if ($qaProgress === 100): ?>
            <div class="u-between" style="margin-top:18px;padding-top:18px;border-top:1px solid var(--line);">
              <span style="font-family:'Caveat',cursive;font-size:26px;">Cleared for submission</span>
              <span class="u-mono u-small u-faint">EXCELBIDS QA</span>
            </div>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>

    <!-- Documents -->
    <section class="card">
      <div class="card-head">
        <div><h2>Documents</h2><div class="sub">Files shared with you, and anything you send us</div></div>
      </div>

      <div class="card-body">
        <?php if (!$documents): ?>
          <p class="u-small u-muted u-mb0">No documents shared on this bid yet.</p>
        <?php endif; ?>

        <?php foreach ($documents as $document): ?>
          <div class="doc-row">
            <span class="doc-ext" aria-hidden="true"><?= e(Document::extension((string) $document['original_name'])) ?></span>
            <div class="doc-info">
              <strong><?= e((string) $document['original_name']) ?></strong>
              <span>
                <?= e(filesize_human((int) $document['size_bytes'])) ?>
                · <?= e(fdate((string) $document['created_at'], 'j M Y')) ?>
                <?= $document['uploader_type'] === 'client' ? ' · sent by you' : ' · from ExcelBids' ?>
              </span>
            </div>
            <div class="doc-actions">
              <a class="btn btn-ghost btn-sm" href="<?= e(path('portal/documents/' . $document['id'] . '/download')) ?>">Download</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($uploadsOn): ?>
        <div class="card-foot" style="display:block;">
          <form method="post" action="<?= e(path('portal/bids/' . $id . '/documents')) ?>"
                enctype="multipart/form-data" data-guard-submit>
            <?= csrf_field() ?>
            <p class="u-small" style="font-weight:600;margin:0 0 9px;">Send us a document</p>
            <div class="inline-form">
              <input class="input" type="file" name="document" required style="flex:2 1 220px;padding:6px;">
              <input class="input" type="text" name="notes" maxlength="255" placeholder="What is it? (optional)" style="flex:1 1 180px;">
              <button type="submit" class="btn btn-primary btn-sm">Upload</button>
            </div>
            <p class="u-small u-faint u-mb0" style="margin-top:8px;">
              Up to <?= e(filesize_human(Uploader::maxBytes())) ?>.
              Accepted: <?= e(implode(', ', Uploader::allowedExtensions())) ?>.
            </p>
          </form>
        </div>
      <?php endif; ?>
    </section>

    <!-- Progress updates -->
    <section class="card">
      <div class="card-head"><div><h2>Progress updates</h2><div class="sub">Everything your bid team has shared</div></div></div>
      <div class="card-body">
        <?php if (!$events): ?>
          <p class="u-small u-muted u-mb0">No updates shared yet. We will post here as the bid progresses.</p>
        <?php else: ?>
          <div class="timeline">
            <?php foreach ($events as $event): ?>
              <div class="tl-item <?= $event['event_type'] === 'status' ? 'is-status' : ($event['actor_type'] === 'client' ? 'is-client' : '') ?>">
                <div class="tl-meta">
                  <?= e(fdatetime((string) $event['created_at'])) ?> — <?= e((string) $event['actor_name']) ?>
                </div>
                <div class="tl-body"><?= e((string) $event['body']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <!-- Sidebar -->
  <div>
    <section class="card">
      <div class="card-head"><h3>Bid details</h3></div>
      <div class="card-body tight">
        <dl class="dl">
          <dt>Buyer</dt><dd><?= e((string) ($bid['buyer'] !== '' ? $bid['buyer'] : '—')) ?></dd>
          <dt>Portal</dt><dd><?= e((string) ($bid['portal'] !== '' ? $bid['portal'] : '—')) ?></dd>
          <dt>Contract value</dt>
          <dd>
            <?= (float) $bid['contract_value'] > 0 ? '<strong>' . e(money($bid['contract_value'])) . '</strong>' : '—' ?>
            <?php if (!empty($bid['contract_length'])): ?><br><span class="u-small u-faint">over <?= e((string) $bid['contract_length']) ?></span><?php endif; ?>
          </dd>
          <dt>Current stage</dt><dd><?= e(Bid::STAGES[$bid['stage']] ?? '') ?></dd>
        </dl>
      </div>
    </section>

    <section class="card">
      <div class="card-head"><h3>Key dates</h3></div>
      <div class="card-body tight">
        <dl class="dl">
          <dt>Clarifications close</dt><dd><?= e(fdate((string) ($bid['clarification_due'] ?? ''))) ?></dd>
          <dt>Submission deadline</dt>
          <dd>
            <strong><?= e(fdatetime((string) ($bid['submission_due'] ?? ''))) ?></strong>
            <?php if (!empty($bid['submission_due'])): ?>
              <br><span class="deadline <?= e($deadline['level']) ?>"><?= e($deadline['label']) ?></span>
            <?php endif; ?>
          </dd>
          <dt>Submitted</dt><dd><?= e(fdatetime((string) ($bid['submitted_at'] ?? ''))) ?></dd>
          <dt>Decision expected</dt><dd><?= e(fdate((string) ($bid['decision_expected_on'] ?? ''))) ?></dd>
        </dl>
      </div>
    </section>

    <?php if ($bid['evaluation_score'] !== null): ?>
      <section class="card">
        <div class="card-head"><h3>Result</h3></div>
        <div class="card-body tight">
          <div class="u-center" style="padding:8px 0 14px;">
            <div style="font-family:'Fraunces',serif;font-size:34px;font-weight:600;line-height:1;">
              <?= e(rtrim(rtrim(number_format((float) $bid['evaluation_score'], 2), '0'), '.')) ?>
              <span class="u-faint" style="font-size:18px;">/ <?= e(rtrim(rtrim(number_format((float) $bid['evaluation_max'], 2), '0'), '.')) ?></span>
            </div>
            <div class="u-small u-faint" style="margin-top:4px;">Evaluation score</div>
          </div>
          <?php if (!empty($bid['outcome_notes'])): ?>
            <p class="u-small u-mb0" style="white-space:pre-wrap;border-top:1px solid var(--line-soft);padding-top:12px;">
              <?= e((string) $bid['outcome_notes']) ?>
            </p>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>

    <?php if (Settings::bool('portal_messaging', true)): ?>
      <section class="card">
        <div class="card-body">
          <p class="u-small u-muted">Question about this bid?</p>
          <a href="<?= e(path('portal/messages')) ?>" class="btn btn-red btn-sm btn-block">Message the team</a>
        </div>
      </section>
    <?php endif; ?>
  </div>

</div>
