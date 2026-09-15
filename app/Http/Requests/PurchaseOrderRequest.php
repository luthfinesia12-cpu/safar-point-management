<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchase-orders.create') ?? false;
    }

    public function rules(): array
    {
        return ['purchase_request_id' => ['required', 'exists:purchase_requests,id'], 'supplier_id' => ['required', 'exists:suppliers,id'], 'order_date' => ['nullable', 'date'], 'expected_arrival' => ['nullable', 'date'], 'shipping_address' => ['nullable', 'string'], 'notes' => ['nullable', 'string'], 'discount' => ['nullable', 'numeric', 'gte:0'], 'tax' => ['nullable', 'numeric', 'gte:0'], 'shipping_cost' => ['nullable', 'numeric', 'gte:0'], 'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'exists:products,id'], 'items.*.quantity' => ['required', 'numeric', 'gt:0'], 'items.*.unit_price' => ['required', 'numeric', 'gte:0']];
    }
}
