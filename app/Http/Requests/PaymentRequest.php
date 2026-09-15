<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payments.create') ?? false;
    }

    public function rules(): array
    {
        return ['purchase_order_id' => ['required', 'exists:purchase_orders,id'], 'bank_account_id' => ['nullable', 'exists:bank_accounts,id'], 'amount' => ['required', 'numeric', 'gt:0'], 'payment_date' => ['required', 'date'], 'payment_type' => ['required', 'in:dp,settlement'], 'method' => ['required', 'in:transfer,cash'], 'recipient' => ['nullable', 'string', 'max:255'], 'reference_number' => ['nullable', 'string', 'max:255'], 'proof_path' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'], 'receipt_path' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120']];
    }
}
