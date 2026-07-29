<?php
/**
 * A repeating group of fields — list items, cards, FAQ entries and so on.
 *
 * Rows are plain inputs indexed by position. A row whose primary field is left
 * blank is discarded on save, which is what makes "add a row" work without any
 * server round-trip.
 *
 * @var string              $name
 * @var array<string,mixed> $field
 * @var array<int,array<string,string>> $rows
 * @var int                 $blockId
 */

$subFields = $field['fields'];
$minRows = (int) ($field['min_rows'] ?? 0);

// Always offer one spare row so there is somewhere to type.
$displayRows = $rows;
$displayRows[] = [];
while (count($displayRows) < $minRows + 1) {
    $displayRows[] = [];
}

$renderRow = static function (int $index, array $row, array $subFields, string $name, int $blockId): void {
    ?>
    <div class="bb-row" data-bb-row>
      <span class="bb-row-handle" aria-hidden="true">⠿</span>
      <div class="bb-row-fields">
        <?php foreach ($subFields as $subName => $subField): ?>
          <?php
            $value = (string) ($row[$subName] ?? ($subField['default'] ?? ''));
            $inputName = 'settings[' . $name . '][' . $index . '][' . $subName . ']';
            $inputId = 'b' . $blockId . '-' . $name . '-' . $index . '-' . $subName;
            $type = (string) $subField['type'];
          ?>
          <div class="bb-row-field bb-row-field-<?= e($type) ?>">
            <?php if ($type === 'bool'): ?>
              <label class="checkline" for="<?= e($inputId) ?>">
                <input type="checkbox" id="<?= e($inputId) ?>" name="<?= e($inputName) ?>" value="1"
                       <?= $value === '1' ? 'checked' : '' ?>>
                <span><?= e((string) $subField['label']) ?></span>
              </label>

            <?php elseif ($type === 'select'): ?>
              <label for="<?= e($inputId) ?>"><?= e((string) $subField['label']) ?></label>
              <select class="input" id="<?= e($inputId) ?>" name="<?= e($inputName) ?>">
                <?php foreach (($subField['options'] ?? []) as $optValue => $optLabel): ?>
                  <option value="<?= e((string) $optValue) ?>"<?= $value === (string) $optValue ? ' selected' : '' ?>>
                    <?= e((string) $optLabel) ?>
                  </option>
                <?php endforeach; ?>
              </select>

            <?php elseif ($type === 'textarea'): ?>
              <label for="<?= e($inputId) ?>"><?= e((string) $subField['label']) ?></label>
              <textarea class="input" id="<?= e($inputId) ?>" name="<?= e($inputName) ?>" rows="2" data-autogrow><?= e($value) ?></textarea>

            <?php else: ?>
              <label for="<?= e($inputId) ?>"><?= e((string) $subField['label']) ?></label>
              <input class="input" type="text" id="<?= e($inputId) ?>" name="<?= e($inputName) ?>" value="<?= e($value) ?>">
            <?php endif; ?>

            <?php if (!empty($subField['help'])): ?>
              <span class="bb-help"><?= e((string) $subField['help']) ?></span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="bb-mini bb-mini-danger" data-bb-row-remove
              title="Remove this row" aria-label="Remove this row">✕</button>
    </div>
    <?php
};
?>

<div class="bb-repeater" data-bb-repeater data-name="<?= e($name) ?>" data-block="<?= (int) $blockId ?>">
  <div class="bb-repeater-head">
    <strong><?= e((string) $field['label']) ?></strong>
    <span class="bb-help">Leave a row blank to drop it. Reorder by editing, or remove and re-add.</span>
  </div>

  <div class="bb-rows">
    <?php foreach ($displayRows as $index => $row): ?>
      <?php $renderRow((int) $index, $row, $subFields, $name, $blockId); ?>
    <?php endforeach; ?>
  </div>

  <button type="button" class="btn btn-ghost btn-sm" data-bb-row-add>+ Add row</button>
</div>
