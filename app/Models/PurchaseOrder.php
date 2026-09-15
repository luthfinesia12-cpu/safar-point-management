<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $attributes = ['status' => 'draft', 'payment_status' => 'unpaid'];

    protected $fillable = ['document_number', 'purchase_request_id', 'supplier_id', 'purchaser_id', 'order_date', 'expected_arrival', 'shipping_address', 'notes', 'discount', 'tax', 'shipping_cost', 'total', 'status', 'payment_status', 'cancellation_reason', 'cancelled_by', 'cancelled_at', 'close_short_reason', 'close_short_requested_by', 'close_short_requested_at', 'close_short_approved_by', 'close_short_approved_at'];

    protected $casts = ['status' => DocumentStatus::class, 'payment_status' => PaymentStatus::class, 'order_date' => 'date', 'expected_arrival' => 'date', 'total' => 'decimal:2', 'discount' => 'decimal:2', 'tax' => 'decimal:2', 'shipping_cost' => 'decimal:2', 'cancelled_at' => 'datetime', 'close_short_requested_at' => 'datetime', 'close_short_approved_at' => 'datetime'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PurchaseOrderRevision::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(DocumentStatusHistory::class, 'document_id')->where('document_type', self::class);
    }
}
