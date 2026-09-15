<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderRevision extends Model
{
    protected $fillable = ['purchase_order_id', 'changed_by', 'version', 'status', 'material_changed', 'reason', 'decided_by', 'decided_at', 'decision_notes', 'snapshot'];

    protected $casts = ['material_changed' => 'boolean', 'decided_at' => 'datetime', 'snapshot' => 'array'];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderRevisionItem::class);
    }
}
