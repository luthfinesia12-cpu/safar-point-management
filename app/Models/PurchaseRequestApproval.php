<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestApproval extends Model
{
    protected $fillable = ['purchase_request_id', 'approver_id', 'decision', 'notes', 'decided_at'];

    protected $casts = ['decided_at' => 'datetime'];

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
