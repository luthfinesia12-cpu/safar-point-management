<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('adjustments.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'adjustment_type' => ['required', 'in:increase,decrease,correction'],
            'reason' => ['required', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity_change' => ['required', 'numeric', 'not_in:0'],
        ];
    }
}
