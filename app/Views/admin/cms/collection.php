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

$errors = Flash::errors();
$columns = $collection['columns'];
$firstColumn = array_key_first($columns);
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
                  <td class="primary-cell"><?= e(str_excerpt((string) $item[$firstColumn], 46)) ?></td>
                  <td class="u-small u-muted">
                    <?php
                      $detail = [];
                      foreach (array_slice(array_keys($columns), 1) as $column) {
                          $value = (string) ($item[$column] ?? '');
                          if (($columns[$column]['type'] ?? '') === 'bool') {
                              if ($value === '1') { $detail[] = $columns[$column]['label']; }
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

    <form method="post" action="<?= e(path('admin/cms/list/' . $type . '/save')) ?>" data-guard-submit>
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
            <?php else: ?>
              <label for="f-<?= e($column) ?>">
                <?= e((string) $definition['label']) ?><?= !empty($definition['required']) ? ' <span class="req">*</span>' : '' ?>
              </label>
              <?php if ($inputType === 'textarea'): ?>
                <textarea class="textarea sm" id="f-<?= e($column) ?>" name="<?= e($column) ?>" data-autogrow
                          maxlength="<?= (int) ($definition['max'] ?? 500) ?>"
                          <?= !empty($definition['required']) ? 'required' : '' ?>></textarea>
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
  var DELETE_BASE = <?= json_encode(path('admin/cms/list/' . $type . '/')) ?>;
  var CSRF = <?= json_encode(App\Core\Csrf::token()) ?>;
  var SINGULAR = <?= json_encode((string) $collection['singular']) ?>;

  function loadItem(item) {
    document.getElementById('item-id').value = item.id;
    document.getElementById('form-title').textContent = 'Edit ' + SINGULAR;

    COLUMNS.forEach(function (column) {
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
      var field = document.getElementById('f-' + column);
      if (!field) return;
      if (BOOLS.indexOf(column) !== -1) { field.checked = false; } else { field.value = ''; }
    });
    document.getElementById('delete-slot').innerHTML = '';
  }
</script>
