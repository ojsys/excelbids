<?php
/**
 * Generic list editor: reorder and toggle in one form, add or edit a single
 * row in the panel below.
 *
 * @var string                            $type
 * @var array<string,mixed>               $collection
 * @var array<int,array<string,mixed>>    $items
 * @var array<string,array<string,mixed>> $collections
 */

use App\Core\Flash;
use App\Models\Media;

$errors = Flash::errors();
$columns = $collection['columns'];
$firstColumn = array_key_first($columns);

$mediaColumns = array_keys(array_filter($columns, static fn (array $d): bool => ($d['type'] ?? '') === 'media'));
$hasUploads = $mediaColumns !== [];

// Some lists need a second tick before they reach the website. Ticking "Live"
// alone is not enough, so the status is spelled out rather than left to the
// truncated detail column.
$gate = $collection['gate'] ?? null;
$gateLabel = $gate !== null ? (string) ($columns[$gate]['label'] ?? 'Approved') : '';

// Thumbnails for the edit panel, keyed by row id then column.
$thumbnails = [];
foreach ($items as $item) {
    foreach ($mediaColumns as $column) {
        $thumbnails[(int) $item['id']][$column] = Media::url(isset($item[$column]) ? (int) $item[$column] : null);
    }
}
?>

<div class="tabs" style="overflow-x:auto;">
  <?php foreach ($collections as $key => $definition): ?>
    <a href="<?= e(path('admin/cms/list/' . $key)) ?>" class="<?= $key === $type ? 'active' : '' ?>">
      <?= e((string) $definition['label']) ?>
    </a>
  <?php endforeach; ?>
</div>

<p class="u-small u-muted u-mb"><?= e((string) $collection['intro']) ?></p>

<div class="grid grid-main">

  <!-- Existing items -->
  <form method="post" action="<?= e(path('admin/cms/list/' . $type)) ?>">
    <?= csrf_field() ?>
    <div class="card">
      <div class="card-head">
        <div><h2>Current <?= e(strtolower((string) $collection['label'])) ?></h2>
          <div class="sub">Lowest order number appears first. Unticking hides an item without deleting it.</div>
        </div>
      </div>

      <?php if (!$items): ?>
        <div class="empty">
          <span class="mark">◈</span>
          <h3>Nothing here yet</h3>
          <p>Add your first <?= e((string) $collection['singular']) ?> using the form alongside.</p>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data">
            <thead>
              <tr>
                <th style="width:70px;">Order</th>
                <th style="width:60px;">Live</th>
                <?php if ($gate !== null): ?><th style="width:150px;">On the website</th><?php endif; ?>
                <th><?= e((string) $columns[$firstColumn]['label']) ?></th>
                <th>Detail</th>
                <th class="actions"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
                <tr>
                  <td>
                    <input class="input" type="number" name="order[<?= (int) $item['id'] ?>]"
                           value="<?= (int) $item['sort_order'] ?>" min="0" style="width:62px;padding:5px 7px;"
                           aria-label="Sort order">
                  </td>
                  <td>
                    <input type="checkbox" name="active[]" value="<?= (int) $item['id'] ?>"
                           <?= (int) $item['is_active'] === 1 ? 'checked' : '' ?>
                           style="accent-color:var(--red);width:16px;height:16px;" aria-label="Visible on the site">
                  </td>
                  <?php if ($gate !== null): ?>
                    <?php
                      $isLive = (int) $item['is_active'] === 1;
                      $isCleared = (int) ($item[$gate] ?? 0) === 1;
                    ?>
                    <td>
                      <?php if ($isLive && $isCleared): ?>
                        <span class="badge badge-success">Published</span>
                      <?php elseif (!$isCleared): ?>
                        <span class="badge badge-warning" title="<?= e($gateLabel) ?> is not ticked">Not approved</span>
                      <?php else: ?>
                        <span class="badge badge-neutral" title="Untick of the Live box hides this">Hidden</span>
                      <?php endif; ?>
                    </td>
                  <?php endif; ?>
                  <td class="primary-cell"><?= e(str_excerpt((string) $item[$firstColumn], 46)) ?></td>
                  <td class="u-small u-muted">
                    <?php
                      $detail = [];
                      foreach (array_slice(array_keys($columns), 1) as $column) {
                          if ($column === $gate) {
                              continue; // Already shown as its own status.
                          }
                          $value = (string) ($item[$column] ?? '');
                          $columnType = $columns[$column]['type'] ?? '';
                          if ($columnType === 'bool') {
                              if ($value === '1') { $detail[] = $columns[$column]['label']; }
                          } elseif ($columnType === 'media') {
                              if ($value !== '' && $value !== '0') { $detail[] = 'Image attached'; }
                          } elseif ($columnType === 'date') {
                              if ($value !== '') { $detail[] = fdate($value); }
                          } elseif ($value !== '') {
                              $detail[] = str_excerpt($value, 40);
                          }
                      }
                      echo $detail ? e(implode(' · ', array_slice($detail, 0, 2))) : '<span class="u-faint">—</span>';
                    ?>
                  </td>
                  <td class="actions">
                    <button type="button" class="btn btn-subtle btn-sm"
                            onclick="loadItem(<?= e(json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>)">Edit</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-foot">
          <button type="submit" class="btn btn-primary btn-sm">Save order &amp; visibility</button>
        </div>
      <?php endif; ?>
    </div>
  </form>

  <!-- Add / edit panel -->
  <section class="card" id="item-form">
    <div class="card-head">
      <h3 id="form-title">Add a <?= e((string) $collection['singular']) ?></h3>
    </div>

    <form method="post" action="<?= e(path('admin/cms/list/' . $type . '/save')) ?>"
          <?= $hasUploads ? 'enctype="multipart/form-data"' : '' ?> data-guard-submit>
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="item-id" value="0">

      <div class="card-body">
        <?php foreach ($columns as $column => $definition): ?>
          <?php $inputType = $definition['type'] ?? 'text'; ?>
          <div class="field<?= isset($errors[$column]) ? ' has-error' : '' ?>">
            <?php if ($inputType === 'bool'): ?>
              <label class="checkline">
                <input type="checkbox" name="<?= e($column) ?>" id="f-<?= e($column) ?>" value="1">
                <span><?= e((string) $definition['label']) ?></span>
              </label>
            <?php elseif ($inputType === 'media'): ?>
              <label for="f-<?= e($column) ?>"><?= e((string) $definition['label']) ?></label>

              <!-- Which image the row already has. Kept unless a new file is
                   chosen or "remove" is ticked, so an edit that only changes
                   the wording does not wipe the picture. -->
              <input type="hidden" name="<?= e($column) ?>_existing" id="f-<?= e($column) ?>-existing" value="0">

              <div class="media-preview" id="f-<?= e($column) ?>-preview" hidden
                   style="margin-bottom:8px;padding:8px;border:1px solid var(--line);border-radius:6px;background:var(--paper, #fff);">
                <img alt="" style="max-width:100%;max-height:180px;display:block;border-radius:4px;">
                <label class="checkline" style="margin-top:8px;">
                  <input type="checkbox" name="<?= e($column) ?>_remove" value="1">
                  <span class="u-small">Remove this image</span>
                </label>
              </div>

              <input class="input" type="file" id="f-<?= e($column) ?>" name="<?= e($column) ?>"
                     accept="image/png,image/jpeg,image/webp">
            <?php else: ?>
              <label for="f-<?= e($column) ?>">
                <?= e((string) $definition['label']) ?><?= !empty($definition['required']) ? ' <span class="req">*</span>' : '' ?>
              </label>
              <?php if ($inputType === 'textarea'): ?>
                <textarea class="textarea sm" id="f-<?= e($column) ?>" name="<?= e($column) ?>" data-autogrow
                          maxlength="<?= (int) ($definition['max'] ?? 500) ?>"
                          <?= !empty($definition['required']) ? 'required' : '' ?>></textarea>
              <?php elseif ($inputType === 'date'): ?>
                <input class="input" type="date" id="f-<?= e($column) ?>" name="<?= e($column) ?>">
              <?php else: ?>
                <input class="input" type="text" id="f-<?= e($column) ?>" name="<?= e($column) ?>"
                       maxlength="<?= (int) ($definition['max'] ?? 255) ?>"
                       <?= !empty($definition['required']) ? 'required' : '' ?>>
              <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($definition['help'])): ?>
              <span class="help"><?= e((string) $definition['help']) ?></span>
            <?php endif; ?>
            <?php if (isset($errors[$column])): ?>
              <span class="field-error"><?= e($errors[$column]) ?></span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="card-foot">
        <button type="submit" class="btn btn-red btn-sm">Save</button>
        <button type="button" class="btn btn-ghost btn-sm" onclick="resetItemForm()">Clear</button>
        <span id="delete-slot"></span>
      </div>
    </form>
  </section>

</div>

<script>
  // The edit buttons load a row into the panel rather than opening a new page,
  // which keeps reordering and editing on one screen.
  var COLUMNS = <?= json_encode(array_keys($columns)) ?>;
  var BOOLS = <?= json_encode(array_keys(array_filter($columns, function ($d) { return ($d['type'] ?? '') === 'bool'; }))) ?>;
  var MEDIA = <?= json_encode($mediaColumns) ?>;
  var THUMBS = <?= json_encode($thumbnails, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var DELETE_BASE = <?= json_encode(path('admin/cms/list/' . $type . '/')) ?>;
  var CSRF = <?= json_encode(App\Core\Csrf::token()) ?>;
  var SINGULAR = <?= json_encode((string) $collection['singular']) ?>;

  // A file input's value cannot be set from script, so image columns are driven
  // through a hidden id field plus a preview instead.
  function setMediaField(column, mediaId, thumbnail) {
    var existing = document.getElementById('f-' + column + '-existing');
    var preview = document.getElementById('f-' + column + '-preview');
    var input = document.getElementById('f-' + column);

    if (existing) existing.value = mediaId || 0;
    if (input) input.value = '';

    if (!preview) return;
    var remove = preview.querySelector('input[type="checkbox"]');
    if (remove) remove.checked = false;

    if (thumbnail) {
      preview.querySelector('img').src = thumbnail;
      preview.hidden = false;
    } else {
      preview.hidden = true;
      preview.querySelector('img').removeAttribute('src');
    }
  }

  function loadItem(item) {
    document.getElementById('item-id').value = item.id;
    document.getElementById('form-title').textContent = 'Edit ' + SINGULAR;

    COLUMNS.forEach(function (column) {
      if (MEDIA.indexOf(column) !== -1) {
        var row = THUMBS[item.id] || {};
        setMediaField(column, item[column], row[column]);
        return;
      }

      var field = document.getElementById('f-' + column);
      if (!field) return;
      if (BOOLS.indexOf(column) !== -1) {
        field.checked = String(item[column]) === '1';
      } else {
        field.value = item[column] === null ? '' : item[column];
        if (field.tagName === 'TEXTAREA') field.dispatchEvent(new Event('input'));
      }
    });

    var slot = document.getElementById('delete-slot');
    slot.innerHTML = '';
    var form = document.createElement('form');
    form.method = 'post';
    form.action = DELETE_BASE + item.id + '/delete';
    form.style.marginLeft = 'auto';
    form.setAttribute('data-confirm', 'Delete this ' + SINGULAR + '? This cannot be undone.');
    form.innerHTML = '<input type="hidden" name="_token" value="' + CSRF + '">' +
                     '<button type="submit" class="btn btn-danger btn-sm">Delete</button>';
    slot.appendChild(form);

    document.getElementById('item-form').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function resetItemForm() {
    document.getElementById('item-id').value = '0';
    document.getElementById('form-title').textContent = 'Add a ' + SINGULAR;
    COLUMNS.forEach(function (column) {
      if (MEDIA.indexOf(column) !== -1) {
        setMediaField(column, 0, null);
        return;
      }
      var field = document.getElementById('f-' + column);
      if (!field) return;
      if (BOOLS.indexOf(column) !== -1) { field.checked = false; } else { field.value = ''; }
    });
    document.getElementById('delete-slot').innerHTML = '';
  }
</script>
