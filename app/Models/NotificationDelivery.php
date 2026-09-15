<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDelivery extends Model
{
    protected $fillable = ['recipient_id', 'title', 'message', 'context', 'status', 'attempts', 'failure_message', 'sent_at', 'failed_at', 'resent_from_id'];

    protected $casts = ['context' => 'array', 'sent_at' => 'datetime', 'failed_at' => 'datetime'];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function resentFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resent_from_id');
    }
}
