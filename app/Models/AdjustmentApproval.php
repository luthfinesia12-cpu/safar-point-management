<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdjustmentApproval extends Model
{
    protected $fillable = ['adjustment_id', 'approver_id', 'decision', 'notes', 'decided_at'];

    protected $casts = ['decided_at' => 'datetime'];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(Adjustment::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
