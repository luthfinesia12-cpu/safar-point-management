<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequest extends Model
{
    protected $attributes = ['status' => 'draft', 'priority' => 'normal'];

    protected $fillable = ['document_number', 'requester_id', 'department', 'priority', 'reason', 'notes', 'status', 'submitted_at', 'cancellation_reason', 'cancelled_by', 'cancelled_at'];

    protected $casts = ['status' => DocumentStatus::class, 'submitted_at' => 'datetime', 'cancelled_at' => 'datetime'];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PurchaseRequestApproval::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(DocumentStatusHistory::class, 'document_id')->where('document_type', self::class);
    }
}
