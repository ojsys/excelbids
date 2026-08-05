<?php
/**
 * The public Outcome Letters page.
 *
 * Each letter is a case-file card: the redacted scan on one side, the context
 * and the client's feedback on the other. Clicking a scan opens it full size.
 *
 * @var array<int,array<string,mixed>> $letters
 */

$intro = trim(block('outcome_intro'));
$note  = trim(block('outcome_consent_note'));
?>

<div class="page-head">
  <div class="wrap">
    <?php if ($eyebrow = trim(block('outcome_eyebrow'))): ?>
      <div class="eyebrow"><?= e($eyebrow) ?></div>
    <?php endif; ?>
    <h1><?= e(block('outcome_heading', 'Outcome Letters')) ?></h1>
    <?php if ($intro !== ''): ?><p><?= nl2br(e($intro)) ?></p><?php endif; ?>
  </div>
</div>

<section id="outcome-letters">
  <div class="wrap">

    <?php if (!$letters): ?>
      <div class="ol-empty">
        <span class="mark" aria-hidden="true">✉</span>
        <p><?= e(block('outcome_empty', 'Approved outcome letters will appear here shortly.')) ?></p>
      </div>
    <?php else: ?>

      <div class="ol-grid">
        <?php foreach ($letters as $letter): ?>
          <?php
            $outcome = trim((string) $letter['outcome']);
            $organisation = trim((string) $letter['organisation']);
            $sector = trim((string) $letter['sector']);
            $summary = trim((string) $letter['summary']);
            $quote = trim((string) $letter['quote']);
            $received = (string) ($letter['received_on'] ?? '');
            $imageUrl = $letter['image_url'];
          ?>
          <article class="ol-card">

            <?php if ($imageUrl !== null): ?>
              <a class="ol-scan" href="<?= e($imageUrl) ?>" target="_blank" rel="noopener"
                 aria-label="Open the full letter for <?= e((string) $letter['title']) ?>">
                <img src="<?= e($imageUrl) ?>" alt="Redacted outcome letter — <?= e((string) $letter['title']) ?>" loading="lazy">
                <?php if ($outcome !== ''): ?>
                  <span class="ol-stamp"><?= e($outcome) ?></span>
                <?php endif; ?>
              </a>
            <?php elseif ($outcome !== ''): ?>
              <div class="ol-scan ol-scan-empty">
                <span class="ol-stamp"><?= e($outcome) ?></span>
              </div>
            <?php endif; ?>

            <div class="ol-body">
              <?php if ($received !== '' && !str_starts_with($received, '0000')): ?>
                <div class="ol-meta mono"><?= e(fdate($received, 'F Y')) ?></div>
              <?php endif; ?>

              <h2 class="ol-title"><?= e((string) $letter['title']) ?></h2>

              <?php if ($organisation !== '' || $sector !== ''): ?>
                <div class="ol-tags">
                  <?php if ($organisation !== ''): ?><span class="ol-tag"><?= e($organisation) ?></span><?php endif; ?>
                  <?php if ($sector !== ''): ?><span class="ol-tag"><?= e($sector) ?></span><?php endif; ?>
                </div>
              <?php endif; ?>

              <?php if ($summary !== ''): ?>
                <p class="ol-summary"><?= nl2br(e($summary)) ?></p>
              <?php endif; ?>

              <?php if ($quote !== ''): ?>
                <blockquote class="ol-quote">
                  <p><?= nl2br(e($quote)) ?></p>
                  <?php if (trim((string) $letter['author_role']) !== '' || trim((string) $letter['author_org']) !== ''): ?>
                    <footer>
                      <strong><?= e((string) $letter['author_role']) ?></strong>
                      <span><?= e((string) $letter['author_org']) ?></span>
                    </footer>
                  <?php endif; ?>
                </blockquote>
              <?php endif; ?>
            </div>

          </article>
        <?php endforeach; ?>
      </div>

      <?php if ($note !== ''): ?>
        <p class="ol-note"><?= nl2br(e($note)) ?></p>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</section>
