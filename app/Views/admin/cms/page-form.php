<?php
/** @var array<string,mixed>|null $page */

use App\Core\Flash;

$errors = Flash::errors();
$isEdit = $page !== null;
$action = $isEdit ? path('admin/cms/pages/' . $page['id'] . '/edit') : path('admin/cms/pages/create');

$val = static function (string $key, $default = '') use ($page) {
    $old = old($key, null);
    if ($old !== null) {
        return $old;
    }
    return $page !== null ? ($page[$key] ?? $default) : $default;
};
?>

<form method="post" action="<?= e($action) ?>" class="content-narrow" data-guard-submit>
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-head">
      <div><h2><?= $isEdit ? 'Edit page' : 'New page' ?></h2></div>
    </div>

    <div class="card-body">
      <div class="field<?= isset($errors['title']) ? ' has-error' : '' ?>">
        <label for="title">Page title <span class="req">*</span></label>
        <input class="input" type="text" id="title" name="title" required maxlength="190" value="<?= e((string) $val('title')) ?>">
        <?php if (isset($errors['title'])): ?><span class="field-error"><?= e($errors['title']) ?></span><?php endif; ?>
      </div>

      <div class="field<?= isset($errors['slug']) ? ' has-error' : '' ?>">
        <label for="slug">URL slug</label>
        <input class="input" type="text" id="slug" name="slug" maxlength="120" value="<?= e((string) $val('slug')) ?>"
               placeholder="leave blank to generate from the title">
        <span class="help">The page will live at <span class="u-mono"><?= e(url('')) ?><span id="slug-preview"><?= e((string) $val('slug', 'your-page')) ?></span></span></span>
        <?php if (isset($errors['slug'])): ?><span class="field-error"><?= e($errors['slug']) ?></span><?php endif; ?>
      </div>

      <div class="field">
        <label for="layout_mode">Editor mode</label>
        <select class="input" id="layout_mode" name="layout_mode">
          <option value="blocks"<?= (string) $val('layout_mode', 'blocks') === 'blocks' ? ' selected' : '' ?>>Page Builder (blocks)</option>
          <option value="html"<?= (string) $val('layout_mode', 'blocks') === 'html' ? ' selected' : '' ?>>Custom HTML</option>
        </select>
        <span class="help">Use the visual block builder or hand-written HTML for this page.</span>
      </div>

      <div class="field">
        <label for="body">Page content (Custom HTML mode only)</label>
        <textarea class="textarea" id="body" name="body" style="min-height:200px;font-family:'IBM Plex Mono',monospace;font-size:13px;"><?= e((string) $val('body')) ?></textarea>
        <span class="help">
          Basic HTML is allowed: <code>&lt;p&gt; &lt;h2&gt; &lt;h3&gt; &lt;strong&gt; &lt;em&gt; &lt;ul&gt; &lt;ol&gt; &lt;li&gt; &lt;a&gt; &lt;table&gt;</code>.
          Anything else is stripped when you save.
        </span>
      </div>

      <div class="form-section">
        <h3>Page Header</h3>
        <div class="u-stack" style="margin-bottom:14px;">
          <label class="checkline">
            <input type="checkbox" name="show_page_header" value="1" <?= (string) $val('show_page_header', '1') === '1' ? 'checked' : '' ?>>
            <span>Show page header banner</span>
          </label>
        </div>
        <div class="field">
          <label for="hero_eyebrow">Eyebrow label</label>
          <input class="input" type="text" id="hero_eyebrow" name="hero_eyebrow" maxlength="120" value="<?= e((string) $val('hero_eyebrow')) ?>" placeholder="e.g. ABOUT EXCELBIDS">
        </div>
        <div class="field">
          <label for="hero_intro">Intro text</label>
          <textarea class="textarea sm" id="hero_intro" name="hero_intro" maxlength="500" data-autogrow><?= e((string) $val('hero_intro')) ?></textarea>
        </div>
      </div>

      <div class="form-section">
        <h3>Search engines</h3>
        <div class="field<?= isset($errors['meta_title']) ? ' has-error' : '' ?>">
          <label for="meta_title">Meta title</label>
          <input class="input" type="text" id="meta_title" name="meta_title" maxlength="190" value="<?= e((string) $val('meta_title')) ?>">
          <span class="help">Leave blank to use the page title.</span>
        </div>
        <div class="field<?= isset($errors['meta_description']) ? ' has-error' : '' ?>">
          <label for="meta_description">Meta description</label>
          <textarea class="textarea sm" id="meta_description" name="meta_description" maxlength="255" data-autogrow><?= e((string) $val('meta_description')) ?></textarea>
          <?php if (isset($errors['meta_description'])): ?><span class="field-error"><?= e($errors['meta_description']) ?></span><?php endif; ?>
        </div>
      </div>

      <div class="form-section">
        <h3>Publishing</h3>
        <div class="u-stack">
          <label class="checkline">
            <input type="checkbox" name="is_published" value="1" <?= (string) $val('is_published', '1') === '1' ? 'checked' : '' ?>>
            <span>Published — visible to the public</span>
          </label>
          <label class="checkline">
            <input type="checkbox" name="show_in_footer" value="1" <?= (string) $val('show_in_footer', '0') === '1' ? 'checked' : '' ?>>
            <span>Link to this page from the footer</span>
          </label>
        </div>
        <div class="field" style="max-width:160px;margin-top:14px;">
          <label for="sort_order">Footer order</label>
          <input class="input" type="number" id="sort_order" name="sort_order" min="0" value="<?= e((string) $val('sort_order', '0')) ?>">
        </div>
      </div>
    </div>

    <div class="card-foot">
      <button type="submit" class="btn btn-red"><?= $isEdit ? 'Save page' : 'Create page' ?></button>
      <a href="<?= e(path('admin/cms/pages')) ?>" class="btn btn-ghost">Cancel</a>
    </div>
  </div>
</form>

<script>
  // Live-preview the generated slug so the URL is never a surprise.
  (function () {
    var title = document.getElementById('title');
    var slug = document.getElementById('slug');
    var preview = document.getElementById('slug-preview');

    function slugify(value) {
      return value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    }
    function sync() {
      preview.textContent = slug.value ? slugify(slug.value) : (slugify(title.value) || 'your-page');
    }
    title.addEventListener('input', sync);
    slug.addEventListener('input', sync);
  })();
</script>
