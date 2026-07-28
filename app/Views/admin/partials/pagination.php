<?php
/**
 * Shared pagination control.
 *
 * @var App\Core\Paginator $paginator
 * @var string             $noun  e.g. "bids"
 */

if (!$paginator->hasPages() && $paginator->total === 0) {
    return;
}
?>
<?php if ($paginator->hasPages()): ?>
  <nav class="pagination" aria-label="Pagination">
    <?php if ($paginator->currentPage > 1): ?>
      <a href="<?= e($paginator->urlForPage($paginator->currentPage - 1)) ?>" rel="prev" aria-label="Previous page">‹</a>
    <?php else: ?>
      <span class="gap" aria-hidden="true">‹</span>
    <?php endif; ?>

    <?php foreach ($paginator->window() as $page): ?>
      <?php if ($page === 0): ?>
        <span class="gap">…</span>
      <?php elseif ($page === $paginator->currentPage): ?>
        <span class="current" aria-current="page"><?= (int) $page ?></span>
      <?php else: ?>
        <a href="<?= e($paginator->urlForPage($page)) ?>"><?= (int) $page ?></a>
      <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($paginator->currentPage < $paginator->lastPage): ?>
      <a href="<?= e($paginator->urlForPage($paginator->currentPage + 1)) ?>" rel="next" aria-label="Next page">›</a>
    <?php else: ?>
      <span class="gap" aria-hidden="true">›</span>
    <?php endif; ?>
  </nav>
<?php endif; ?>

<p class="result-count">
  Showing <?= (int) $paginator->from() ?>–<?= (int) $paginator->to() ?>
  of <?= number_format($paginator->total) ?> <?= e($noun ?? 'results') ?>
</p>
