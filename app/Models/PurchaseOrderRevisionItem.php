<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderRevisionItem extends Model
{
    protected $fillable = ['purchase_order_revision_id', 'product_id', 'quantity', 'unit_price'];

    protected $casts = ['quantity' => 'decimal:3', 'unit_price' => 'decimal:2'];

    public function revision(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderRevision::class, 'purchase_order_revision_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
