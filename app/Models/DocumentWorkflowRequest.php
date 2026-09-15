<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentWorkflowRequest extends Model
{
    protected $fillable = ['document_type', 'document_id', 'action', 'status', 'requested_by', 'decided_by', 'reason', 'decision_notes', 'decided_at'];

    protected $casts = ['decided_at' => 'datetime'];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
