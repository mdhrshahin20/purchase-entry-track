<?php

namespace App\Foundation;

/**
 * Rule-based validator used by Form Requests.
 */
class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $data;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param array<string, list<string>> $rules
     */
    public function validate(array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $value, $rule);
                if (isset($this->errors[$field])) {
                    break;
                }
            }
        }

        return empty($this->errors);
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        if ($rule === 'required') {
            if ($value === null || $value === '') {
                $this->errors[$field] = $this->label($field) . ' is required.';
            }
            return;
        }

        if ($value === null || $value === '') {
            return;
        }

        $value = (string) $value;

        if ($rule === 'digits') {
            if (!preg_match('/^\d+$/', $value)) {
                $this->errors[$field] = $this->label($field) . ' must contain numbers only.';
            }
            return;
        }

        if ($rule === 'alpha') {
            if (!preg_match('/^[a-zA-Z]+$/', $value)) {
                $this->errors[$field] = $this->label($field) . ' must contain text (letters) only.';
            }
            return;
        }

        if ($rule === 'alpha_spaces') {
            if (!preg_match('/^[a-zA-Z ]+$/u', $value)) {
                $this->errors[$field] = $this->label($field) . ' may contain text and spaces only.';
            }
            return;
        }

        if ($rule === 'alpha_num_spaces') {
            if (!preg_match('/^[a-zA-Z0-9 ]+$/u', $value)) {
                $this->errors[$field] = $this->label($field) . ' may contain text, spaces, and numbers only.';
            }
            return;
        }

        if ($rule === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = $this->label($field) . ' must be a valid email address.';
            }
            return;
        }

        if ($rule === 'phone_bd') {
            if (!preg_match('/^\d+$/', $value)) {
                $this->errors[$field] = 'Phone must contain numbers only.';
                return;
            }
            if (!str_starts_with($value, '880')) {
                $this->errors[$field] = 'Phone must start with country code 880.';
                return;
            }
            if (mb_strlen($value) > 20) {
                $this->errors[$field] = 'Phone must be no more than 20 characters.';
            }
            return;
        }

        if ($rule === 'items_list') {
            $parts = array_filter(array_map('trim', explode(',', $value)));
            if ($parts === []) {
                $this->errors[$field] = 'At least one item is required.';
                return;
            }
            foreach ($parts as $item) {
                if (!preg_match('/^[a-zA-Z ]+$/u', $item)) {
                    $this->errors[$field] = 'Items must contain text only (letters and spaces).';
                    return;
                }
            }
            if (mb_strlen($value) > 255) {
                $this->errors[$field] = 'Items list is too long (max 255 characters).';
            }
            return;
        }

        if ($rule === 'max_words:30') {
            $words = preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY);
            if (count($words) > 30) {
                $this->errors[$field] = 'Note must be no more than 30 words.';
            }
            return;
        }

        if (str_starts_with($rule, 'max:')) {
            $max = (int) substr($rule, 4);
            if (mb_strlen($value) > $max) {
                $this->errors[$field] = $this->label($field) . " must be no more than {$max} characters.";
            }
        }
    }

    private function label(string $field): string
    {
        return match ($field) {
            'amount' => 'Amount',
            'buyer' => 'Buyer',
            'receipt_id' => 'Receipt ID',
            'items' => 'Items',
            'buyer_email' => 'Buyer email',
            'note' => 'Note',
            'city' => 'City',
            'phone' => 'Phone',
            'entry_by' => 'Entry by',
            default => ucfirst(str_replace('_', ' ', $field)),
        };
    }
}
