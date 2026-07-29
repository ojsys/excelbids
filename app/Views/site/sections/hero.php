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

    <div class="hero-graphic-wrap" aria-hidden="true">
      <div class="hero-backdrop-shape"></div>
      <div class="hero-illustration-card">
        <div class="clipboard-top">
          <div class="clipboard-clip"></div>
        </div>
        <div class="clipboard-body">
          <div class="winning-bid-badge">WINNING BID</div>
          <div class="checklist-items">
            <div class="check-row"><span class="check-icon">✓</span> <span class="check-bar"></span></div>
            <div class="check-row"><span class="check-icon">✓</span> <span class="check-bar"></span></div>
            <div class="check-row"><span class="check-icon">✓</span> <span class="check-bar"></span></div>
            <div class="check-row"><span class="check-icon">✓</span> <span class="check-bar"></span></div>
          </div>
          <div class="signature-line hand">ExcelBids</div>
        </div>
        <!-- Pie Chart Element -->
        <div class="hero-pie-chart">
          <svg width="68" height="68" viewBox="0 0 40 40">
            <circle r="15.9" cx="20" cy="20" fill="transparent" stroke="#1E3A5F" stroke-width="8" stroke-dasharray="40 60" stroke-dashoffset="0"/>
            <circle r="15.9" cx="20" cy="20" fill="transparent" stroke="#F97316" stroke-width="8" stroke-dasharray="30 70" stroke-dashoffset="-40"/>
            <circle r="15.9" cx="20" cy="20" fill="transparent" stroke="#FBBF24" stroke-width="8" stroke-dasharray="18 82" stroke-dashoffset="-70"/>
            <circle r="15.9" cx="20" cy="20" fill="transparent" stroke="#10B981" stroke-width="8" stroke-dasharray="12 88" stroke-dashoffset="-88"/>
          </svg>
        </div>
        <!-- Sticky Tag -->
        <?php if (block('hero_sticky_label') !== ''): ?>
          <div class="sticky"><?= e(block('hero_sticky_label')) ?><span><?= e(block('hero_sticky_value')) ?></span></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
