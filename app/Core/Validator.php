<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Backend validation for purchase form fields.
 *
 * Rules are independent of the JavaScript layer so bypassed browser
 * submissions are still rejected.
 */
class Validator
{
    /**
     * Field-level error messages keyed by input name.
     *
     * @var array<string, string>
     */
    private array $errors = [];

    /**
     * Validate the given input map against all purchase rules.
     *
     * @param array<string, mixed> $data Submitted field values.
     *
     * @return bool True when every field is valid.
     */
    public function validate(array $data): bool
    {
        $this->errors = [];

        $this->amount($data['amount'] ?? null);
        $this->buyer($data['buyer'] ?? null);
        $this->receiptId($data['receipt_id'] ?? null);
        $this->items($data['items'] ?? null);
        $this->buyerEmail($data['buyer_email'] ?? null);
        $this->note($data['note'] ?? null);
        $this->city($data['city'] ?? null);
        $this->phone($data['phone'] ?? null);
        $this->entryBy($data['entry_by'] ?? null);

        return empty($this->errors);
    }

    /**
     * Return collected validation errors.
     *
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @param mixed $value Amount value.
     *
     * @return void
     */
    private function amount($value): void
    {
        if ($value === null || $value === '') {
            $this->errors['amount'] = 'Amount is required.';
            return;
        }
        if (!preg_match('/^\d+$/', (string) $value)) {
            $this->errors['amount'] = 'Amount must contain numbers only.';
        }
    }

    /**
     * @param mixed $value Buyer name.
     *
     * @return void
     */
    private function buyer($value): void
    {
        if ($value === null || $value === '') {
            $this->errors['buyer'] = 'Buyer is required.';
            return;
        }
        $value = (string) $value;
        if (mb_strlen($value) > 20) {
            $this->errors['buyer'] = 'Buyer must be no more than 20 characters.';
            return;
        }
        if (!preg_match('/^[a-zA-Z0-9 ]+$/u', $value)) {
            $this->errors['buyer'] = 'Buyer may contain text, spaces, and numbers only.';
        }
    }

    /**
     * @param mixed $value Receipt identifier.
     *
     * @return void
     */
    private function receiptId($value): void
    {
        if ($value === null || $value === '') {
            $this->errors['receipt_id'] = 'Receipt ID is required.';
            return;
        }
        if (!preg_match('/^[a-zA-Z]+$/', (string) $value)) {
            $this->errors['receipt_id'] = 'Receipt ID must contain text (letters) only.';
        }
        if (mb_strlen((string) $value) > 20) {
            $this->errors['receipt_id'] = 'Receipt ID must be no more than 20 characters.';
        }
    }

    /**
     * @param mixed $value Comma-separated item list.
     *
     * @return void
     */
    private function items($value): void
    {
        if ($value === null || $value === '') {
            $this->errors['items'] = 'At least one item is required.';
            return;
        }
        $value = (string) $value;
        $parts = array_filter(array_map('trim', explode(',', $value)));
        if ($parts === []) {
            $this->errors['items'] = 'At least one item is required.';
            return;
        }
        foreach ($parts as $item) {
            if (!preg_match('/^[a-zA-Z ]+$/u', $item)) {
                $this->errors['items'] = 'Items must contain text only (letters and spaces).';
                return;
            }
        }
        if (mb_strlen($value) > 255) {
            $this->errors['items'] = 'Items list is too long (max 255 characters).';
        }
    }

    /**
     * @param mixed $value Email address.
     *
     * @return void
     */
    private function buyerEmail($value): void
    {
        if ($value === null || $value === '') {
            $this->errors['buyer_email'] = 'Buyer email is required.';
            return;
        }
        $value = (string) $value;
        if (mb_strlen($value) > 50) {
            $this->errors['buyer_email'] = 'Buyer email must be no more than 50 characters.';
            return;
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors['buyer_email'] = 'Buyer email must be a valid email address.';
        }
    }

    /**
     * @param mixed $value Free-text note (Unicode allowed).
     *
     * @return void
     */
    private function note($value): void
    {
        $value = trim((string) $value);
        if ($value === '') {
            $this->errors['note'] = 'Note is required.';
            return;
        }
        // Count Unicode letter/number groups as words (aligned with frontend).
        if (preg_match_all('/[\p{L}\p{N}]+/u', $value, $matches) === false) {
            $words = preg_split('/\s+/u', $value, -1, PREG_SPLIT_NO_EMPTY);
            $wordCount = count($words);
        } else {
            $wordCount = count($matches[0]);
        }
        if ($wordCount > 30) {
            $this->errors['note'] = 'Note must be no more than 30 words.';
        }
    }

    /**
     * @param mixed $value City name.
     *
     * @return void
     */
    private function city($value): void
    {
        if ($value === null || $value === '') {
            $this->errors['city'] = 'City is required.';
            return;
        }
        if (!preg_match('/^[a-zA-Z ]+$/u', (string) $value)) {
            $this->errors['city'] = 'City may contain text and spaces only.';
        }
        if (mb_strlen((string) $value) > 20) {
            $this->errors['city'] = 'City must be no more than 20 characters.';
        }
    }

    /**
     * @param mixed $value Phone number (must start with 880).
     *
     * @return void
     */
    private function phone($value): void
    {
        if ($value === null || $value === '') {
            $this->errors['phone'] = 'Phone is required.';
            return;
        }
        $value = (string) $value;
        if (!preg_match('/^\d+$/', $value)) {
            $this->errors['phone'] = 'Phone must contain numbers only.';
            return;
        }
        if (!str_starts_with($value, '880')) {
            $this->errors['phone'] = 'Phone must start with country code 880.';
            return;
        }
        if (mb_strlen($value) > 20) {
            $this->errors['phone'] = 'Phone must be no more than 20 characters.';
        }
    }

    /**
     * @param mixed $value Numeric user id (entry_by).
     *
     * @return void
     */
    private function entryBy($value): void
    {
        if ($value === null || $value === '') {
            $this->errors['entry_by'] = 'Entry by (user id) is required.';
            return;
        }
        if (!preg_match('/^\d+$/', (string) $value)) {
            $this->errors['entry_by'] = 'Entry by must contain numbers only.';
        }
    }
}
