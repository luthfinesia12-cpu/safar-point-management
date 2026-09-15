<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('goods-receipts.create') ?? false;
    }

    public function rules(): array
    {
        return ['purchase_order_id' => ['required', 'exists:purchase_orders,id'], 'warehouse_id' => ['required', 'exists:warehouses,id'], 'received_date' => ['nullable', 'date'], 'notes' => ['nullable', 'string'], 'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'exists:products,id'], 'items.*.quantity' => ['required', 'numeric', 'gt:0'], 'items.*.damaged_quantity' => ['nullable', 'numeric', 'gte:0']];
    }
}
