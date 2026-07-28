<?php
/**
 * @var string                         $section
 * @var string                         $sectionLabel
 * @var array<int,array<string,mixed>> $blocks
 * @var array<string,string>           $allSections
 */
?>

<div class="tabs" style="overflow-x:auto;">
  <?php foreach ($allSections as $key => $label): ?>
    <a href="<?= e(path('admin/cms/content/' . $key)) ?>" class="<?= $key === $section ? 'active' : '' ?>">
      <?= e($label) ?>
    </a>
  <?php endforeach; ?>
</div>

<form method="post" action="<?= e(path('admin/cms/content/' . $section)) ?>" class="content-narrow" data-guard-submit>
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-head">
      <div>
        <h2><?= e($sectionLabel) ?></h2>
        <div class="sub">Changes go live on the public site as soon as you save</div>
      </div>
      <div class="head-actions">
        <a href="<?= e(path('/')) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">View site ↗</a>
      </div>
    </div>

    <div class="card-body">
      <?php if (!$blocks): ?>
        <p class="u-muted u-small u-mb0">There is no editable copy in this section.</p>
      <?php endif; ?>

      <?php foreach ($blocks as $block): ?>
        <?php $key = (string) $block['key']; $type = (string) $block['type']; ?>
        <div class="field">
          <label for="<?= e($key) ?>"><?= e((string) $block['label']) ?></label>

          <?php if ($type === 'textarea' || $type === 'html'): ?>
            <textarea class="textarea" id="<?= e($key) ?>" name="<?= e($key) ?>" data-autogrow><?= e((string) $block['value']) ?></textarea>
          <?php elseif ($type === 'number'): ?>
            <input class="input" type="number" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e((string) $block['value']) ?>">
          <?php else: ?>
            <input class="input" type="text" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e((string) $block['value']) ?>">
          <?php endif; ?>

          <?php if (!empty($block['hint'])): ?>
            <span class="help"><?= e((string) $block['hint']) ?></span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <?php if ($section === 'hero'): ?>
        <div class="alert alert-info u-mb0" style="margin-top:8px;">
          <strong>Formatting shortcuts.</strong>
          Wrap a word in <code>[c]like this[/c]</code> to draw the hand-drawn red circle around it,
          or <code>[m]like this[/m]</code> to add the highlighter mark used in the document mock-up.
        </div>
      <?php endif; ?>
    </div>

    <div class="card-foot">
      <button type="submit" class="btn btn-red">Save changes</button>
      <a href="<?= e(path('admin/cms')) ?>" class="btn btn-ghost">Back to content</a>
    </div>
  </div>
</form>
