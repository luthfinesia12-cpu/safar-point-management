<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Adjustment extends Model
{
    protected $attributes = ['status' => 'draft'];

    protected $fillable = ['document_number', 'warehouse_id', 'requester_id', 'status', 'adjustment_type', 'reason', 'posted_at', 'posted_by'];

    protected $casts = ['status' => DocumentStatus::class, 'posted_at' => 'datetime'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AdjustmentItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(AdjustmentApproval::class);
    }
}
