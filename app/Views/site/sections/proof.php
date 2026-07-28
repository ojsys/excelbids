<?php
/**
 * @var array<string,mixed>|null       $caseStudy
 * @var array<int,array<string,mixed>> $testimonials
 * @var string|null                    $ghostNum
 */
if ($caseStudy === null && !$testimonials) {
    return;
}
$note = block('proof_testimonial_note');
?>
<section id="proof">
  <?php if ($ghostNum): ?><div class="ghost-num" aria-hidden="true"><?= e($ghostNum) ?></div><?php endif; ?>
  <div class="wrap">
    <div class="section-head" style="margin-bottom:24px;">
      <div class="file-num mono"><?= e(block('proof_file_num', 'FILE §07')) ?></div>
      <h2><?= e(block('proof_heading', 'Proof of Work')) ?></h2>
    </div>

    <?php if ($caseStudy !== null): ?>
      <div class="case-study">
        <div>
          <div class="eyebrow cs-label"><?= e($caseStudy['eyebrow']) ?></div>
          <h3><?= e($caseStudy['title']) ?></h3>
          <?php if ($caseStudy['intro'] !== ''): ?><p class="cs-before"><?= e($caseStudy['intro']) ?></p><?php endif; ?>
        </div>
        <div class="cs-results">
          <?php for ($i = 1; $i <= 3; $i++): ?>
            <?php $value = (string) $caseStudy["result_{$i}_value"]; if ($value === '') { continue; } ?>
            <div class="cs-result">
              <strong><?= e($value) ?></strong>
              <span><?= e((string) $caseStudy["result_{$i}_label"]) ?></span>
            </div>
          <?php endfor; ?>
        </div>
      </div>
      <?php if ($caseStudy['footnote'] !== ''): ?>
        <p class="proof-note" style="margin-top:10px; color:var(--ink-soft);"><?= e($caseStudy['footnote']) ?></p>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($testimonials): ?>
      <?php if ($note !== ''): ?><p style="font-size:12px;color:var(--ink-soft);margin-top:24px;"><?= e($note) ?></p><?php endif; ?>
      <div class="t-grid" style="margin-top:36px;">
        <?php foreach ($testimonials as $testimonial): ?>
          <div class="t-card">
            <div class="qmark" aria-hidden="true">&ldquo;</div>
            <p class="quote"><?= e($testimonial['quote']) ?></p>
            <div class="who">
              <strong><?= e($testimonial['author_role']) ?></strong>
              <span><?= e($testimonial['author_org']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
