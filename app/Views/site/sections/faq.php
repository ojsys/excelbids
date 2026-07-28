<?php
/**
 * @var array<int,array<string,mixed>> $faqs
 * @var string|null                    $ghostNum
 */
if (!$faqs) {
    return;
}

// FAQPage structured data — these questions are exactly what buyers search for.
$schema = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
foreach ($faqs as $faq) {
    $schema['mainEntity'][] = [
        '@type' => 'Question',
        'name' => $faq['question'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
    ];
}
?>
<section id="faq" class="tint">
  <?php if ($ghostNum): ?><div class="ghost-num" aria-hidden="true"><?= e($ghostNum) ?></div><?php endif; ?>
  <div class="wrap">
    <div class="section-head">
      <div class="file-num mono"><?= e(block('faq_file_num', 'FILE §08')) ?></div>
      <h2><?= e(block('faq_heading', 'Frequently Asked Questions')) ?></h2>
    </div>

    <div class="faq-wrap">
      <?php foreach ($faqs as $index => $faq): ?>
        <div class="faq-item">
          <button class="faq-q" type="button" aria-expanded="false">
            <span><span class="idx">Q<?= (int) $index + 1 ?></span><?= e($faq['question']) ?></span>
            <span class="plus" aria-hidden="true">+</span>
          </button>
          <div class="faq-a"><p><?= e($faq['answer']) ?></p></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
