<?php
/**
 * The edit panel for a single block.
 *
 * @var array<string,mixed>            $block
 * @var array<string,mixed>            $definition
 * @var int                            $pageId
 * @var array<int,array<string,mixed>> $media
 * @var int                            $columnCount   Columns in the parent section
 */

use App\Core\View;

$blockId = (int) $block['id'];
$settings = $block['settings'] ?? [];
$isSection = $block['parent_id'] === null;
$isHidden = (int) $block['is_visible'] !== 1;
$summary = '';

// A one-line summary keeps the collapsed list readable.
foreach (['heading', 'text', 'title', 'question', 'topline', 'url'] as $key) {
    if (!empty($settings[$key])) {
        $summary = str_excerpt((string) $settings[$key], 60);
        break;
    }
}
if ($summary === '' && !empty($settings['body'])) {
    $summary = str_excerpt(strip_tags((string) $settings['body']), 60);
}
foreach (['items'] as $key) {
    if ($summary === '' && !empty($settings[$key]) && is_array($settings[$key])) {
        $summary = count($settings[$key]) . ' item' . (count($settings[$key]) === 1 ? '' : 's');
    }
}

$actionBase = path('admin/cms/pages/' . $pageId . '/blocks/' . $blockId);
?>
<div class="bb-block<?= $isHidden ? ' is-hidden' : '' ?>" id="block-<?= $blockId ?>">

  <div class="bb-block-head">
    <button type="button" class="bb-toggle" data-bb-toggle aria-expanded="false">
      <span class="bb-icon" aria-hidden="true"><?= e((string) $definition['icon']) ?></span>
      <span class="bb-block-name">
        <?= e((string) $definition['label']) ?>
        <?php if ($summary !== ''): ?><span class="bb-summary"><?= e($summary) ?></span><?php endif; ?>
      </span>
      <?php if ($isHidden): ?><span class="badge badge-muted">Hidden</span><?php endif; ?>
      <span class="bb-chevron" aria-hidden="true">▾</span>
    </button>

    <div class="bb-block-actions">
      <form method="post" action="<?= e($actionBase . '/up') ?>"><?= csrf_field() ?>
        <button type="submit" class="bb-mini" title="Move up" aria-label="Move up">↑</button></form>
      <form method="post" action="<?= e($actionBase . '/down') ?>"><?= csrf_field() ?>
        <button type="submit" class="bb-mini" title="Move down" aria-label="Move down">↓</button></form>
      <form method="post" action="<?= e($actionBase . '/duplicate') ?>"><?= csrf_field() ?>
        <button type="submit" class="bb-mini" title="Duplicate" aria-label="Duplicate">⧉</button></form>
      <form method="post" action="<?= e($actionBase . '/toggle') ?>"><?= csrf_field() ?>
        <button type="submit" class="bb-mini" title="<?= $isHidden ? 'Show on the live page' : 'Hide from the live page' ?>"
                aria-label="Toggle visibility"><?= $isHidden ? '◌' : '◉' ?></button></form>
      <form method="post" action="<?= e($actionBase . '/delete') ?>"
            data-confirm="Delete this <?= e(strtolower((string) $definition['label'])) ?><?= $isSection ? ' and everything inside it' : '' ?>?">
        <?= csrf_field() ?>
        <button type="submit" class="bb-mini bb-mini-danger" title="Delete" aria-label="Delete">✕</button></form>
    </div>
  </div>

  <div class="bb-block-body" hidden>
    <form method="post" action="<?= e($actionBase . '/save') ?>" data-guard-submit>
      <?= csrf_field() ?>

      <?php foreach ($definition['fields'] as $name => $field): ?>
        <?php if ($field['type'] === 'repeater'): ?>
          <?= View::partial('admin/cms/partials/repeater', [
              'name'     => $name,
              'field'    => $field,
              'rows'     => is_array($settings[$name] ?? null) ? $settings[$name] : [],
              'blockId'  => $blockId,
          ]) ?>
        <?php else: ?>
          <?= View::partial('admin/cms/partials/field', [
              'name'      => $name,
              'field'     => $field,
              'value'     => $settings[$name] ?? ($field['default'] ?? ''),
              'inputName' => 'settings[' . $name . ']',
              'inputId'   => 'b' . $blockId . '-' . $name,
              'media'     => $media,
          ]) ?>
        <?php endif; ?>
      <?php endforeach; ?>

      <div class="bb-block-foot">
        <button type="submit" class="btn btn-red btn-sm">Save block</button>

        <?php if (!$isSection && $columnCount > 1): ?>
          <span class="bb-move-col">
            Move to column:
            <?php for ($i = 0; $i < $columnCount; $i++): ?>
              <?php if ($i === (int) $block['column_index']) { continue; } ?>
              <button type="submit" class="btn btn-ghost btn-sm"
                      formaction="<?= e($actionBase . '/column') ?>" formnovalidate
                      name="column_index" value="<?= $i ?>"><?= $i + 1 ?></button>
            <?php endfor; ?>
          </span>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
