<?php
/**
 * @var array<int,array<string,mixed>> $sectors
 * @var string|null                    $ghostNum
 */
$keywords = array_filter(array_map('trim', explode(',', block('sectors_card_keywords'))));
?>
<section id="sectors">
  <?php if ($ghostNum): ?><div class="ghost-num" aria-hidden="true"><?= e($ghostNum) ?></div><?php endif; ?>
  <div class="wrap sector-row">
    <div>
      <div class="file-num mono" style="font-family:'IBM Plex Mono',monospace;font-size:11.5px;color:var(--ink-soft);"><?= e(block('sectors_file_num', 'FILE §03')) ?></div>
      <h2 style="font-size:30px; margin-top:6px; line-height:1.25;"><?= e(block('sectors_heading')) ?></h2>
      <?php if ($body = block('sectors_body')): ?>
        <p style="margin-top:12px; color:var(--ink-soft); font-size:15.5px;"><?= e($body) ?></p>
      <?php endif; ?>

      <?php if ($sectors): ?>
        <div class="sector-tags">
          <?php foreach ($sectors as $sector): ?>
            <span<?= (int) $sector['is_core'] === 1 ? ' class="core"' : '' ?>><?= e($sector['name']) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="folder" data-flag="<?= e(block('sectors_card_flag', 'SECTOR FOCUS')) ?>">
      <h4><?= e(block('sectors_card_title')) ?></h4>
      <?php if ($cardBody = block('sectors_card_body')): ?>
        <p><?= e($cardBody) ?></p>
      <?php endif; ?>
      <?php if ($keywords): ?>
        <div class="kw">
          <?php foreach ($keywords as $keyword): ?><span><?= e($keyword) ?></span><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
