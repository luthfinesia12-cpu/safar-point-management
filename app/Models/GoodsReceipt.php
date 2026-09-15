<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    protected $attributes = ['status' => 'draft'];

    protected $fillable = ['document_number', 'purchase_order_id', 'warehouse_id', 'receiver_id', 'received_date', 'notes', 'status', 'posted_at', 'posted_by'];

    protected $casts = ['status' => DocumentStatus::class, 'received_date' => 'date', 'posted_at' => 'datetime'];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
}
