<?php

use App\Core\Content;

?>
<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <?php if ($tag = block('hero_case_tag')): ?>
        <div class="case-tag mono"><?= e($tag) ?></div>
      <?php endif; ?>

      <h1><?= Content::rich('hero_heading') ?></h1>

      <?php if ($lead = block('hero_lead')): ?>
        <p class="lead"><?= e($lead) ?></p>
      <?php endif; ?>

      <div class="hero-actions">
        <a href="<?= e(path('consultation')) ?>" class="btn btn-red"><?= e(block('hero_btn_primary', 'Submit a Consultation Request')) ?></a>
        <a href="#services" class="btn btn-ghost"><?= e(block('hero_btn_secondary', 'See Our Services')) ?></a>
      </div>

      <div class="hero-trust">
        <?php for ($i = 1; $i <= 3; $i++): ?>
          <?php $value = block("hero_trust_{$i}_value"); if ($value === '') { continue; } ?>
          <div><strong><?= e($value) ?></strong><?= e(block("hero_trust_{$i}_label")) ?></div>
        <?php endfor; ?>
      </div>
    </div>

    <div class="doc-wrap" aria-hidden="true">
      <div class="doc-page">
        <svg class="paperclip" viewBox="0 0 24 40" fill="none"><path d="M6 10V28a6 6 0 0012 0V8a4 4 0 00-8 0v18a2 2 0 004 0V10" stroke="#8A8A80" stroke-width="2" stroke-linecap="round"/></svg>

        <?php if ($topline = block('hero_doc_topline')): ?>
          <div class="doc-topline mono"><?= e($topline) ?></div>
        <?php endif; ?>

        <?php foreach (['hero_doc_para_1', 'hero_doc_para_2'] as $paraKey): ?>
          <?php if (block($paraKey) !== ''): ?>
            <p class="doc-text"><?= Content::rich($paraKey) ?></p>
          <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($note = block('hero_doc_note')): ?>
          <div class="doc-note doc-note-1 hand"><?= e($note) ?> ✓</div>
        <?php endif; ?>

        <?php if (block('hero_doc_stamp') !== ''): ?>
          <div class="stamp"><?= Content::lines('hero_doc_stamp') ?></div>
        <?php endif; ?>
      </div>

      <?php if (block('hero_sticky_label') !== ''): ?>
        <div class="sticky"><?= e(block('hero_sticky_label')) ?><span><?= e(block('hero_sticky_value')) ?></span></div>
      <?php endif; ?>
    </div>
  </div>
</section>
