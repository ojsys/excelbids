<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Rule-string validator.
 *
 *   $v = Validator::make($request->all(), [
 *       'email' => 'required|email|max:190',
 *       'name'  => 'required|min:2|max:140',
 *   ], ['email' => 'Email address']);
 *
 *   if ($v->fails()) { ... $v->errors() ... }
 */
final class Validator
{
    /** @var array<string,mixed> */
    private array $data;

    /** @var array<string,string> */
    private array $rules;

    /** @var array<string,string> Field name → human label. */
    private array $labels;

    /** @var array<string,string> */
    private array $errors = [];

    private function __construct(array $data, array $rules, array $labels)
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->labels = $labels;
        $this->validate();
    }

    /**
     * @param array<string,mixed>  $data
     * @param array<string,string> $rules
     * @param array<string,string> $labels
     */
    public static function make(array $data, array $rules, array $labels = []): self
    {
        return new self($data, $rules, $labels);
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            $rules = explode('|', $ruleString);

            $isRequired = in_array('required', $rules, true);
            $isEmpty = $value === null || $value === '' || (is_array($value) && $value === []);

            if ($isEmpty && !$isRequired) {
                continue; // Optional and blank — nothing else to check.
            }

            foreach ($rules as $rule) {
                $parameter = null;
                if (str_contains($rule, ':')) {
                    [$rule, $parameter] = explode(':', $rule, 2);
                }

                if ($this->applyRule($field, $rule, $value, $parameter) === false) {
                    break; // One message per field is enough.
                }
            }
        }
    }

    /** @return bool False when the field failed and further rules should be skipped. */
    private function applyRule(string $field, string $rule, $value, ?string $parameter): bool
    {
        $label = $this->label($field);
        $isEmpty = $value === null || $value === '' || (is_array($value) && $value === []);

        switch ($rule) {
            case 'required':
                if ($isEmpty) {
                    return $this->fail($field, "{$label} is required.");
                }
                return true;

            case 'email':
                if (!filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
                    return $this->fail($field, "{$label} must be a valid email address.");
                }
                return true;

            case 'url':
                if (!filter_var((string) $value, FILTER_VALIDATE_URL)) {
                    return $this->fail($field, "{$label} must be a valid web address.");
                }
                return true;

            case 'numeric':
                if (!is_numeric($value)) {
                    return $this->fail($field, "{$label} must be a number.");
                }
                return true;

            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    return $this->fail($field, "{$label} must be a whole number.");
                }
                return true;

            case 'min':
                if (is_numeric($value) && !is_string($value)) {
                    if ((float) $value < (float) $parameter) {
                        return $this->fail($field, "{$label} must be at least {$parameter}.");
                    }
                } elseif (mb_strlen((string) $value) < (int) $parameter) {
                    return $this->fail($field, "{$label} must be at least {$parameter} characters.");
                }
                return true;

            case 'max':
                if (is_numeric($value) && !is_string($value)) {
                    if ((float) $value > (float) $parameter) {
                        return $this->fail($field, "{$label} must not be more than {$parameter}.");
                    }
                } elseif (mb_strlen((string) $value) > (int) $parameter) {
                    return $this->fail($field, "{$label} must be {$parameter} characters or fewer.");
                }
                return true;

            case 'between':
                [$low, $high] = array_pad(explode(',', (string) $parameter), 2, '0');
                $number = (float) $value;
                if ($number < (float) $low || $number > (float) $high) {
                    return $this->fail($field, "{$label} must be between {$low} and {$high}.");
                }
                return true;

            case 'in':
                $allowed = explode(',', (string) $parameter);
                if (!in_array((string) $value, $allowed, true)) {
                    return $this->fail($field, "{$label} is not a valid choice.");
                }
                return true;

            case 'date':
                if (strtotime((string) $value) === false) {
                    return $this->fail($field, "{$label} must be a valid date.");
                }
                return true;

            case 'confirmed':
                $other = $this->data[$field . '_confirm'] ?? null;
                if ((string) $value !== (string) $other) {
                    return $this->fail($field, "{$label} and its confirmation do not match.");
                }
                return true;

            case 'password':
                // Length beats forced character classes for real-world strength.
                if (mb_strlen((string) $value) < 10) {
                    return $this->fail($field, "{$label} must be at least 10 characters.");
                }
                if (preg_match('/^(.)\1*$/', (string) $value)) {
                    return $this->fail($field, "{$label} is too simple. Please choose something less predictable.");
                }
                return true;

            case 'phone':
                if (!preg_match('/^[0-9 ()+\-]{7,25}$/', (string) $value)) {
                    return $this->fail($field, "{$label} must be a valid phone number.");
                }
                return true;

            case 'slug':
                if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $value)) {
                    return $this->fail($field, "{$label} may contain lowercase letters, numbers and hyphens only.");
                }
                return true;

            case 'unique':
                // unique:table,column[,ignoreId]
                [$table, $column, $ignore] = array_pad(explode(',', (string) $parameter), 3, null);
                $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = ?";
                $params = [$value];
                if ($ignore !== null && $ignore !== '') {
                    $sql .= ' AND id <> ?';
                    $params[] = $ignore;
                }
                if ((int) Database::scalar($sql, $params, 0) > 0) {
                    return $this->fail($field, "That {$this->lowerLabel($field)} is already in use.");
                }
                return true;

            case 'exists':
                // exists:table
                if ((int) Database::scalar("SELECT COUNT(*) FROM `{$parameter}` WHERE id = ?", [$value], 0) === 0) {
                    return $this->fail($field, "The selected {$this->lowerLabel($field)} no longer exists.");
                }
                return true;

            case 'nullable':
                return true;

            default:
                return true;
        }
    }

    private function fail(string $field, string $message): bool
    {
        $this->errors[$field] = $message;
        return false;
    }

    private function label(string $field): string
    {
        if (isset($this->labels[$field])) {
            return $this->labels[$field];
        }
        return ucfirst(str_replace(['_id', '_'], ['', ' '], $field));
    }

    private function lowerLabel(string $field): string
    {
        return mb_strtolower($this->label($field));
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** Add an error discovered outside the rule set. */
    public function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }
}
