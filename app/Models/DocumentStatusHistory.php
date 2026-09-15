<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = ['document_type', 'document_id', 'from_status', 'to_status', 'changed_by', 'reason', 'changed_at'];

    protected $casts = ['changed_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
