<?php
/**
 * Renders one block-editor field from its registry definition.
 *
 * @var string               $name       Field key
 * @var array<string,mixed>  $field      Definition from Blocks
 * @var mixed                $value      Current value
 * @var string               $inputName  Full form input name, e.g. settings[heading]
 * @var string               $inputId    Unique DOM id
 * @var array<int,array<string,mixed>> $media  Media library, for image fields
 */

use App\Models\Media;

$type = (string) $field['type'];
$label = (string) ($field['label'] ?? labelize($name));
$help = (string) ($field['help'] ?? '');
$required = !empty($field['required']);
?>

<div class="bb-field bb-field-<?= e($type) ?>">
  <?php if ($type !== 'bool'): ?>
    <label for="<?= e($inputId) ?>"><?= e($label) ?><?= $required ? ' <span class="req">*</span>' : '' ?></label>
  <?php endif; ?>

  <?php if ($type === 'textarea'): ?>
    <textarea class="input" id="<?= e($inputId) ?>" name="<?= e($inputName) ?>"
              rows="<?= (int) ($field['rows'] ?? 3) ?>" data-autogrow><?= e((string) $value) ?></textarea>

  <?php elseif ($type === 'richtext'): ?>
    <?php
      // The visible editor is a contenteditable div; the textarea behind it is
      // what actually submits, and is the only thing the server trusts.
      $editorId = $inputId . '-editor';
    ?>
    <div class="wysiwyg" data-wysiwyg>
      <div class="wysiwyg-toolbar" role="toolbar" aria-label="Formatting">
        <button type="button" data-cmd="bold" title="Bold"><strong>B</strong></button>
        <button type="button" data-cmd="italic" title="Italic"><em>I</em></button>
        <button type="button" data-cmd="formatBlock" data-value="h2" title="Large heading">H2</button>
        <button type="button" data-cmd="formatBlock" data-value="h3" title="Medium heading">H3</button>
        <button type="button" data-cmd="formatBlock" data-value="p" title="Paragraph">¶</button>
        <button type="button" data-cmd="insertUnorderedList" title="Bulleted list">• List</button>
        <button type="button" data-cmd="insertOrderedList" title="Numbered list">1. List</button>
        <button type="button" data-cmd="formatBlock" data-value="blockquote" title="Quote">&ldquo;</button>
        <button type="button" data-cmd="createLink" title="Add link">Link</button>
        <button type="button" data-cmd="unlink" title="Remove link">Unlink</button>
        <button type="button" data-cmd="removeFormat" title="Clear formatting">Clear</button>
      </div>
      <div class="wysiwyg-area" id="<?= e($editorId) ?>" contenteditable="true"
           data-target="<?= e($inputId) ?>"><?= (string) $value ?></div>
      <textarea id="<?= e($inputId) ?>" name="<?= e($inputName) ?>" hidden><?= e((string) $value) ?></textarea>
    </div>

  <?php elseif ($type === 'select'): ?>
    <select class="input" id="<?= e($inputId) ?>" name="<?= e($inputName) ?>">
      <?php foreach (($field['options'] ?? []) as $optionValue => $optionLabel): ?>
        <option value="<?= e((string) $optionValue) ?>"<?= (string) $value === (string) $optionValue ? ' selected' : '' ?>>
          <?= e((string) $optionLabel) ?>
        </option>
      <?php endforeach; ?>
    </select>

  <?php elseif ($type === 'bool'): ?>
    <label class="checkline" for="<?= e($inputId) ?>">
      <input type="checkbox" id="<?= e($inputId) ?>" name="<?= e($inputName) ?>" value="1"
             <?= (string) $value === '1' ? 'checked' : '' ?>>
      <span><?= e($label) ?></span>
    </label>

  <?php elseif ($type === 'number'): ?>
    <input class="input" type="number" id="<?= e($inputId) ?>" name="<?= e($inputName) ?>"
           value="<?= e((string) $value) ?>">

  <?php elseif ($type === 'image'): ?>
    <?php
      $currentId = (int) $value;
      $currentUrl = Media::url($currentId);
    ?>
    <div class="bb-image-field">
      <div class="bb-image-preview">
        <?php if ($currentUrl !== null): ?>
          <img src="<?= e($currentUrl) ?>" alt="">
        <?php else: ?>
          <span class="bb-image-empty">No image</span>
        <?php endif; ?>
      </div>
      <div class="bb-image-choose">
        <select class="input" id="<?= e($inputId) ?>" name="<?= e($inputName) ?>">
          <option value="">— Choose an image —</option>
          <?php foreach ($media as $item): ?>
            <option value="<?= (int) $item['id'] ?>"<?= $currentId === (int) $item['id'] ? ' selected' : '' ?>>
              <?= e(str_excerpt((string) $item['original_name'], 42)) ?>
              (<?= $item['width'] ? (int) $item['width'] . '×' . (int) $item['height'] : e(filesize_human((int) $item['size_bytes'])) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <p class="bb-help">
          <a href="<?= e(path('admin/media')) ?>" target="_blank" rel="noopener">Upload images →</a>
          Save this block after uploading to pick the new file.
        </p>
      </div>
    </div>

  <?php else: ?>
    <input class="input" type="text" id="<?= e($inputId) ?>" name="<?= e($inputName) ?>"
           value="<?= e((string) $value) ?>">
  <?php endif; ?>

  <?php if ($help !== ''): ?>
    <p class="bb-help"><?= e($help) ?></p>
  <?php endif; ?>
</div>
