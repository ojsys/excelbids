<?php
/**
 * @var array<int,array<string,mixed>> $documents
 * @var array<int,array<string,mixed>> $bids
 */

use App\Models\Document;

// Group by bid so the list reads the way a client thinks about their work.
$grouped = ['' => []];
foreach ($documents as $document) {
    $key = (string) ($document['bid_reference'] ?? '');
    $grouped[$key][] = $document;
}
?>

<?php if (!$documents): ?>
  <div class="card">
    <div class="empty">
      <span class="mark">▭</span>
      <h3>No documents yet</h3>
      <p>Anything your bid team shares with you — tender packs, drafts, final submissions — will be collected here.</p>
      <a href="<?= e(path('portal/bids')) ?>" class="btn btn-ghost btn-sm">View my bids</a>
    </div>
  </div>
<?php else: ?>
  <?php foreach ($grouped as $reference => $items): ?>
    <?php if (!$items) { continue; } ?>
    <section class="card">
      <div class="card-head">
        <div>
          <h2><?= $reference !== '' ? e($reference) : 'General documents' ?></h2>
          <?php if ($reference !== '' && !empty($items[0]['bid_title'])): ?>
            <div class="sub"><?= e(str_excerpt((string) $items[0]['bid_title'], 70)) ?></div>
          <?php endif; ?>
        </div>
        <?php if ($reference !== '' && !empty($items[0]['bid_id'])): ?>
          <div class="head-actions">
            <a href="<?= e(path('portal/bids/' . $items[0]['bid_id'])) ?>" class="btn btn-ghost btn-sm">Open bid</a>
          </div>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php foreach ($items as $document): ?>
          <div class="doc-row">
            <span class="doc-ext" aria-hidden="true"><?= e(Document::extension((string) $document['original_name'])) ?></span>
            <div class="doc-info">
              <strong><?= e((string) $document['original_name']) ?></strong>
              <span>
                <?= e(filesize_human((int) $document['size_bytes'])) ?>
                · <?= e(fdate((string) $document['created_at'], 'j M Y')) ?>
                <?= $document['uploader_type'] === 'client' ? ' · sent by you' : ' · from ExcelBids' ?>
              </span>
            </div>
            <div class="doc-actions">
              <a class="btn btn-ghost btn-sm" href="<?= e(path('portal/documents/' . $document['id'] . '/download')) ?>">Download</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
<?php endif; ?>
