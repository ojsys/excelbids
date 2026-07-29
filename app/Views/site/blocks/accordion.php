<?php
/**
 * Reuses the home page FAQ accordion markup, so site.js drives it unchanged.
 *
 * @var array<string,mixed> $settings
 * @var array<string,mixed> $block
 */

use App\Core\BlockRenderer as R;

$items = R::rows($settings, 'items');
if (!$items) {
    return;
}

// Structured data helps these questions surface in search results.
$schema = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
?>
<div class="pb-block faq-wrap">
  <?php foreach ($items as $index => $item): ?>
    <?php
      $question = trim((string) ($item['question'] ?? ''));
      $answer = trim((string) ($item['answer'] ?? ''));
      if ($question === '') { continue; }
      $schema['mainEntity'][] = [
          '@type' => 'Question',
          'name' => $question,
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
      ];
    ?>
    <div class="faq-item">
      <button class="faq-q" type="button" aria-expanded="false">
        <span><span class="idx">Q<?= (int) $index + 1 ?></span><?= e($question) ?></span>
        <span class="plus" aria-hidden="true">+</span>
      </button>
      <div class="faq-a"><p><?= nl2br(e($answer)) ?></p></div>
    </div>
  <?php endforeach; ?>
</div>
<?php if ($schema['mainEntity']): ?>
  <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php endif; ?>
