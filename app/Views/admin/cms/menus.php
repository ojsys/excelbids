<?php
/**
 * @var array<string,string>                        $locations
 * @var array<string,array<int,array<string,mixed>>> $items
 */
?>

<p class="u-small u-muted u-mb">
  Links starting with <span class="u-mono">/</span> point inside this site
  (<span class="u-mono">/#services</span> jumps to a home page section).
  Full addresses beginning <span class="u-mono">https://</span> open external sites.
</p>

<form method="post" action="<?= e(path('admin/cms/menus')) ?>" class="content-narrow">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">

  <?php foreach ($locations as $location => $label): ?>
    <section class="card">
      <div class="card-head">
        <div><h2><?= e($label) ?></h2><div class="sub"><?= count($items[$location] ?? []) ?> links</div></div>
      </div>

      <?php if (empty($items[$location])): ?>
        <div class="card-body"><p class="u-small u-muted u-mb0">No links in this menu yet.</p></div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead>
              <tr><th style="width:75px;">Order</th><th style="width:60px;">Live</th><th>Label</th><th>Link</th><th class="actions"></th></tr>
            </thead>
            <tbody>
              <?php foreach ($items[$location] as $item): ?>
                <tr>
                  <td>
                    <input class="input" type="number" name="order[<?= (int) $item['id'] ?>]"
                           value="<?= (int) $item['sort_order'] ?>" min="0" style="width:62px;padding:5px 7px;" aria-label="Order">
                  </td>
                  <td>
                    <input type="checkbox" name="active[]" value="<?= (int) $item['id'] ?>"
                           <?= (int) $item['is_active'] === 1 ? 'checked' : '' ?>
                           style="accent-color:var(--red);width:16px;height:16px;" aria-label="Show this link">
                  </td>
                  <td>
                    <input class="input" type="text" name="label[<?= (int) $item['id'] ?>]"
                           value="<?= e((string) $item['label']) ?>" maxlength="120" style="padding:6px 9px;" aria-label="Label">
                  </td>
                  <td>
                    <input class="input" type="text" name="url[<?= (int) $item['id'] ?>]"
                           value="<?= e((string) $item['url']) ?>" maxlength="255" style="padding:6px 9px;font-family:'IBM Plex Mono',monospace;font-size:12.5px;" aria-label="Link">
                  </td>
                  <td class="actions">
                    <button type="submit" class="btn btn-subtle btn-sm" name="action" value="delete"
                            formnovalidate onclick="this.form.querySelector('#delete-id').value='<?= (int) $item['id'] ?>'">✕</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>

  <input type="hidden" name="id" id="delete-id" value="">

  <div class="card">
    <div class="card-foot">
      <button type="submit" class="btn btn-red">Save all menus</button>
      <a href="<?= e(path('admin/cms')) ?>" class="btn btn-ghost">Back to content</a>
    </div>
  </div>
</form>

<section class="card content-narrow">
  <div class="card-head"><h2>Add a link</h2></div>
  <form method="post" action="<?= e(path('admin/cms/menus')) ?>" data-guard-submit>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <div class="card-body">
      <div class="field-row-3">
        <div class="field">
          <label for="new-location">Menu</label>
          <select class="select" id="new-location" name="location">
            <?php foreach ($locations as $location => $label): ?>
              <option value="<?= e($location) ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="new-label">Label</label>
          <input class="input" type="text" id="new-label" name="label" required maxlength="120">
        </div>
        <div class="field">
          <label for="new-url">Link</label>
          <input class="input" type="text" id="new-url" name="url" required maxlength="255" placeholder="/#services">
        </div>
      </div>
    </div>
    <div class="card-foot"><button type="submit" class="btn btn-primary btn-sm">Add link</button></div>
  </form>
</section>
