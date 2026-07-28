<?php /** @var string|null $ghostNum */ ?>
<section id="about">
  <?php if ($ghostNum): ?><div class="ghost-num" aria-hidden="true"><?= e($ghostNum) ?></div><?php endif; ?>
  <div class="wrap">
    <div class="section-head" style="margin-bottom:20px;">
      <div class="file-num mono"><?= e(block('about_file_num', 'FILE §01')) ?></div>
      <h2><?= e(block('about_heading', 'About ExcelBids')) ?></h2>
    </div>

    <?php if ($body = block('about_body')): ?>
      <p class="about-body"><?= e($body) ?></p>
    <?php endif; ?>

    <div class="fact-row">
      <?php for ($i = 1; $i <= 3; $i++): ?>
        <?php $value = block("about_fact_{$i}_value"); if ($value === '') { continue; } ?>
        <div><strong><?= e($value) ?></strong><span><?= e(block("about_fact_{$i}_label")) ?></span></div>
      <?php endfor; ?>
    </div>
  </div>
</section>
