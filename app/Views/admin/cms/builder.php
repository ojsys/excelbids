<?php
/**
 * The page builder: sections down the page, blocks inside their columns.
 *
 * @var array<string,mixed>            $page
 * @var array<int,array<string,mixed>> $sections
 * @var array<string,array<string,array<string,mixed>>> $blockTypes
 * @var array<int,array<string,mixed>> $media
 */

use App\Core\Blocks;
use App\Core\View;

$pageId = (int) $page['id'];
$addUrl = path('admin/cms/pages/' . $pageId . '/blocks');

/** The block picker, rendered wherever a block can be added. */
$picker = static function (int $parentId, int $columnIndex, array $blockTypes, string $addUrl): void {
    $key = $parentId . '-' . $columnIndex;
    ?>
    <div class="bb-add">
      <button type="button" class="bb-add-btn" data-bb-picker="<?= e($key) ?>">+ Add block</button>

      <div class="bb-picker" id="picker-<?= e($key) ?>" hidden>
        <?php foreach ($blockTypes as $group => $types): ?>
          <div class="bb-picker-group">
            <h6><?= e($group) ?></h6>
            <div class="bb-picker-grid">
              <?php foreach ($types as $type => $definition): ?>
                <form method="post" action="<?= e($addUrl) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="block_type" value="<?= e($type) ?>">
                  <input type="hidden" name="parent_id" value="<?= (int) $parentId ?>">
                  <input type="hidden" name="column_index" value="<?= (int) $columnIndex ?>">
                  <button type="submit" class="bb-picker-item" title="<?= e((string) $definition['description']) ?>">
                    <span class="bb-picker-icon" aria-hidden="true"><?= e((string) $definition['icon']) ?></span>
                    <span><?= e((string) $definition['label']) ?></span>
                  </button>
                </form>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
};
?>

<div class="bb-bar">
  <div>
    <span class="ref">/<?= e((string) $page['slug']) ?></span>
    <?php if ((int) $page['is_published'] !== 1): ?>
      <span class="badge badge-muted">Draft — not visible to the public</span>
    <?php else: ?>
      <span class="badge badge-success">Published</span>
    <?php endif; ?>
    <span class="u-small u-faint"><?= count($sections) ?> section<?= count($sections) === 1 ? '' : 's' ?></span>
  </div>
  <a href="<?= e(path('admin/cms/pages')) ?>" class="btn btn-subtle btn-sm">← All pages</a>
</div>

<?php if (!$sections): ?>
  <div class="card">
    <div class="empty">
      <span class="mark">▤</span>
      <h3>This page is empty</h3>
      <p>Start by adding a section. Sections are the horizontal bands that run down the page — set their
         background and column layout, then drop blocks inside.</p>
    </div>
  </div>
<?php endif; ?>

<div class="bb-canvas">
  <?php foreach ($sections as $section): ?>
    <?php
      $sectionId = (int) $section['id'];
      $settings = $section['settings'] ?? [];
      $columnCount = Blocks::columnCount((string) ($settings['columns'] ?? '1'));
      $background = (string) ($settings['background'] ?? 'paper');
      $sectionHidden = (int) $section['is_visible'] !== 1;
    ?>
    <section class="bb-section bb-bg-<?= e(str_replace('_', '-', $background)) ?><?= $sectionHidden ? ' is-hidden' : '' ?>">

      <div class="bb-section-label">
        <span class="bb-section-tag">Section</span>
        <?php if (!empty($settings['heading'])): ?>
          <span class="bb-section-title"><?= e(str_excerpt((string) $settings['heading'], 48)) ?></span>
        <?php endif; ?>
        <span class="bb-section-meta">
          <?= e(Blocks::BACKGROUNDS[$background] ?? $background) ?> ·
          <?= $columnCount ?> column<?= $columnCount === 1 ? '' : 's' ?>
        </span>
      </div>

      <?= View::partial('admin/cms/partials/block-editor', [
          'block'       => $section,
          'definition'  => Blocks::definition('section'),
          'pageId'      => $pageId,
          'media'       => $media,
          'columnCount' => 1,
      ]) ?>

      <div class="bb-columns bb-columns-<?= $columnCount ?>">
        <?php foreach ($section['columns'] as $index => $blocks): ?>
          <div class="bb-column">
            <?php if ($columnCount > 1): ?>
              <div class="bb-column-label">Column <?= (int) $index + 1 ?></div>
            <?php endif; ?>

            <?php foreach ($blocks as $child): ?>
              <?= View::partial('admin/cms/partials/block-editor', [
                  'block'       => $child,
                  'definition'  => Blocks::definition((string) $child['block_type']),
                  'pageId'      => $pageId,
                  'media'       => $media,
                  'columnCount' => $columnCount,
              ]) ?>
            <?php endforeach; ?>

            <?php $picker($sectionId, (int) $index, $blockTypes, $addUrl); ?>
          </div>
        <?php endforeach; ?>
      </div>

    </section>
  <?php endforeach; ?>
</div>

<!-- Add a new section -->
<form method="post" action="<?= e($addUrl) ?>" class="bb-add-section">
  <?= csrf_field() ?>
  <input type="hidden" name="block_type" value="section">
  <button type="submit" class="btn btn-red">+ Add section</button>
  <span class="u-small u-faint">A full-width band. Everything else goes inside one.</span>
</form>

<script src="<?= e(asset('js/builder.js')) ?>" defer></script>
