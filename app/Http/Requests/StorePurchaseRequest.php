<?php

namespace App\Http\Requests;

/**
 * Validation rules for purchase store (backend layer).
 */
class StorePurchaseRequest extends FormRequest
{
    protected function prepareForValidation(): array
    {
        return [
            'amount' => $this->input('amount', ''),
            'buyer' => trim((string) $this->input('buyer', '')),
            'receipt_id' => trim((string) $this->input('receipt_id', '')),
            'items' => trim((string) $this->input('items', '')),
            'buyer_email' => trim((string) $this->input('buyer_email', '')),
            'note' => trim((string) $this->input('note', '')),
            'city' => trim((string) $this->input('city', '')),
            'phone' => trim((string) $this->input('phone', '')),
            'entry_by' => trim((string) $this->input('entry_by', '')),
        ];
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'digits'],
            'buyer' => ['required', 'alpha_num_spaces', 'max:20'],
            'receipt_id' => ['required', 'alpha', 'max:20'],
            'items' => ['required', 'items_list'],
            'buyer_email' => ['required', 'email', 'max:50'],
            'note' => ['required', 'max_words:30'],
            'city' => ['required', 'alpha_spaces', 'max:20'],
            'phone' => ['required', 'phone_bd'],
            'entry_by' => ['required', 'digits'],
        ];
    }
}
