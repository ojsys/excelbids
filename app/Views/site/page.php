<?php
/**
 * A CMS page: either a stack of builder blocks or hand-written HTML.
 *
 * @var array<string,mixed> $page
 * @var string              $blocksHtml
 */

$showHeader = (int) ($page['show_page_header'] ?? 1) === 1;
$eyebrow = trim((string) ($page['hero_eyebrow'] ?? ''));
$intro = trim((string) ($page['hero_intro'] ?? ''));
$isBlocks = ($page['layout_mode'] ?? 'html') === 'blocks';
?>

<?php if ($showHeader): ?>
  <div class="<?= $isBlocks ? 'pb-page-head' : 'page-head' ?>">
    <div class="wrap">
      <?php if ($eyebrow !== ''): ?><div class="eyebrow"><?= e($eyebrow) ?></div><?php endif; ?>
      <h1><?= e((string) $page['title']) ?></h1>
      <?php if ($intro !== ''): ?><p><?= nl2br(e($intro)) ?></p><?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php if ($isBlocks): ?>
  <?= $blocksHtml ?>
<?php else: ?>
  <section style="padding-top:24px;">
    <div class="wrap">
      <article class="prose"><?= $page['body'] ?></article>
    </div>
  </section>
<?php endif; ?>
