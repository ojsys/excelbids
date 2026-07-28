<?php
/** @var array<int,array<string,mixed>> $stats */
if (!$stats) {
    return;
}
$note = block('proof_strip_note');
?>
<div class="proof-strip">
  <div class="wrap proof-inner">
    <?php foreach ($stats as $stat): ?>
      <div class="proof-stat"><strong><?= e($stat['value']) ?></strong><span><?= e($stat['label']) ?></span></div>
    <?php endforeach; ?>
  </div>
  <?php if ($note !== ''): ?>
    <div class="wrap"><p class="proof-note"><?= e($note) ?></p></div>
  <?php endif; ?>
</div>
