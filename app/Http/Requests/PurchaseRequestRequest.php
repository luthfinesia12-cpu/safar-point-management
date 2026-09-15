<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchase-requests.create') ?? false;
    }

    public function rules(): array
    {
        return ['department' => ['nullable', 'string', 'max:100'], 'priority' => ['required', 'in:low,normal,high'], 'reason' => ['required', 'string', 'max:2000'], 'notes' => ['nullable', 'string'], 'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'exists:products,id'], 'items.*.quantity' => ['required', 'numeric', 'gt:0'], 'items.*.estimated_price' => ['nullable', 'numeric', 'gte:0'], 'items.*.specification' => ['nullable', 'string']];
    }
}
