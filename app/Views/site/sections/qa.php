<?php
/**
 * @var array<int,array<string,mixed>> $qaChecks
 * @var string|null                    $ghostNum
 */

use App\Core\Content;

if (!$qaChecks) {
    return;
}
?>
<section id="qa">
  <?php if ($ghostNum): ?><div class="ghost-num" aria-hidden="true"><?= e($ghostNum) ?></div><?php endif; ?>
  <div class="wrap">
    <div class="section-head" style="margin-bottom:24px;">
      <div class="file-num mono"><?= e(block('qa_file_num', 'FILE §05')) ?></div>
      <h2><?= e(block('qa_heading', 'Quality Assurance Sign-Off')) ?></h2>
    </div>

    <div class="signoff">
      <?php foreach ($qaChecks as $check): ?>
        <div class="so-row">
          <div class="box" aria-hidden="true"></div>
          <div>
            <h4><?= e($check['title']) ?></h4>
            <?php if (!empty($check['description'])): ?><p><?= e($check['description']) ?></p><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="so-sign">
        <div class="sig"><?= e(block('qa_signature', 'Cleared for submission')) ?></div>
        <div class="meta"><?= Content::lines('qa_signature_meta') ?></div>
      </div>
    </div>
  </div>
</section>
