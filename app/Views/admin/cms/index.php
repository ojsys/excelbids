<?php
/**
 * @var array<string,string> $sections
 * @var array<string,array<string,mixed>> $collections
 * @var array<string,int>   $counts
 * @var int                 $pageCount
 */
?>

<div class="alert alert-info">
  <strong>This is where the public website is edited.</strong>
  Nothing here needs a developer — change the wording, add or remove list items, reorder sections,
  and the site updates immediately.
</div>

<div class="grid grid-2">

  <section class="card">
    <div class="card-head">
      <div><h2>Page copy</h2><div class="sub">Headings, paragraphs and button labels</div></div>
    </div>
    <div class="card-body tight">
      <?php foreach ($sections as $key => $label): ?>
        <a href="<?= e(path('admin/cms/content/' . $key)) ?>" class="u-between"
           style="padding:10px 0;border-bottom:1px solid var(--line-soft);">
          <span class="u-small" style="font-weight:600;"><?= e($label) ?></span>
          <span class="u-faint">→</span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <div>
    <section class="card">
      <div class="card-head">
        <div><h2>Content lists</h2><div class="sub">Repeating items: services, sectors, FAQs and more</div></div>
      </div>
      <div class="card-body tight">
        <?php foreach ($collections as $key => $collection): ?>
          <a href="<?= e(path('admin/cms/list/' . $key)) ?>" class="u-between"
             style="padding:10px 0;border-bottom:1px solid var(--line-soft);">
            <span style="min-width:0;">
              <span class="u-small" style="font-weight:600;display:block;"><?= e((string) $collection['label']) ?></span>
              <span class="u-small u-faint"><?= e(str_excerpt((string) $collection['intro'], 60)) ?></span>
            </span>
            <span class="badge badge-neutral"><?= (int) ($counts[$key] ?? 0) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="card">
      <div class="card-head"><h2>Structure</h2></div>
      <div class="card-body tight">
        <a href="<?= e(path('admin/cms/sections')) ?>" class="u-between" style="padding:10px 0;border-bottom:1px solid var(--line-soft);">
          <span style="min-width:0;">
            <span class="u-small" style="font-weight:600;display:block;">Home page sections</span>
            <span class="u-small u-faint">Show, hide and reorder whole sections</span>
          </span>
          <span class="u-faint">→</span>
        </a>
        <a href="<?= e(path('admin/cms/pages')) ?>" class="u-between" style="padding:10px 0;border-bottom:1px solid var(--line-soft);">
          <span style="min-width:0;">
            <span class="u-small" style="font-weight:600;display:block;">Pages</span>
            <span class="u-small u-faint">Privacy policy, terms and any other standalone page</span>
          </span>
          <span class="badge badge-neutral"><?= (int) $pageCount ?></span>
        </a>
        <a href="<?= e(path('admin/cms/menus')) ?>" class="u-between" style="padding:10px 0;">
          <span style="min-width:0;">
            <span class="u-small" style="font-weight:600;display:block;">Navigation menus</span>
            <span class="u-small u-faint">Header and footer links</span>
          </span>
          <span class="u-faint">→</span>
        </a>
      </div>
    </section>
  </div>

</div>
