<?php
/** @var array<int,array<string,mixed>> $sections */
?>

<form method="post" action="<?= e(path('admin/cms/sections')) ?>" class="content-narrow">
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-head">
      <div>
        <h2>Home page sections</h2>
        <div class="sub">Untick to hide a whole section. Lower numbers appear higher up the page.</div>
      </div>
      <div class="head-actions">
        <a href="<?= e(path('/')) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">View site ↗</a>
      </div>
    </div>

    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th style="width:80px;">Order</th><th style="width:70px;">Visible</th><th>Section</th><th>Edit</th></tr>
        </thead>
        <tbody>
          <?php foreach ($sections as $section): ?>
            <tr>
              <td>
                <input class="input" type="number" name="order[<?= (int) $section['id'] ?>]"
                       value="<?= (int) $section['sort_order'] ?>" min="0" style="width:66px;padding:5px 7px;"
                       aria-label="Sort order for <?= e((string) $section['title']) ?>">
              </td>
              <td>
                <input type="checkbox" name="visible[]" value="<?= (int) $section['id'] ?>"
                       <?= (int) $section['is_visible'] === 1 ? 'checked' : '' ?>
                       style="accent-color:var(--red);width:16px;height:16px;"
                       aria-label="Show <?= e((string) $section['title']) ?>">
              </td>
              <td>
                <span class="primary-cell"><?= e((string) $section['title']) ?></span>
                <span class="sub-cell ref"><?= e((string) $section['section_key']) ?></span>
              </td>
              <td class="u-small">
                <a href="<?= e(path('admin/cms/content/' . $section['section_key'])) ?>">Edit copy</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="card-foot">
      <button type="submit" class="btn btn-red">Save layout</button>
      <a href="<?= e(path('admin/cms')) ?>" class="btn btn-ghost">Back to content</a>
    </div>
  </div>
</form>
