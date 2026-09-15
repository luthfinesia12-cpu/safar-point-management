<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAttachment extends Model
{
    protected $fillable = ['document_type', 'document_id', 'uploaded_by', 'original_name', 'storage_path', 'mime_type', 'size', 'version', 'replaced_attachment_id'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function replacedAttachment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_attachment_id');
    }
}
