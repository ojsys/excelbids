<?php
/** @var array<int,array<string,mixed>> $whyCards */
if (!$whyCards) {
    return;
}
?>
<section id="why" class="why-section">
  <div class="wrap">
    <div class="section-head" style="margin-bottom:28px;">
      <div class="file-num mono" style="color:var(--gold);"><?= e(block('why_file_num', 'FILE §06')) ?></div>
      <h2 style="color:#fff;"><?= e(block('why_heading', 'Why Choose Excel Bids')) ?></h2>
    </div>

    <div class="why-grid">
      <?php foreach ($whyCards as $card): ?>
        <div class="why-card">
          <div class="seal" aria-hidden="true"><?= e($card['seal']) ?></div>
          <h4><?= e($card['title']) ?></h4>
          <?php if (!empty($card['description'])): ?><p><?= e($card['description']) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
