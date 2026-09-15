<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'action', 'module', 'description', 'before_data', 'after_data', 'ip_address', 'user_agent', 'created_at'];

    protected $casts = ['before_data' => 'array', 'after_data' => 'array', 'created_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
