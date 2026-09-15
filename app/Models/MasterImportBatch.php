<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterImportBatch extends Model
{
    protected $fillable = ['master_type', 'uploaded_by', 'original_name', 'status', 'row_count', 'valid_rows', 'errors'];

    protected $casts = ['valid_rows' => 'array', 'errors' => 'array'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
