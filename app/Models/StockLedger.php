<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLedger extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'warehouse_id', 'quantity_change', 'balance_after', 'reference_type', 'reference_id', 'posted_by', 'posted_at'];

    protected $casts = ['quantity_change' => 'decimal:3', 'balance_after' => 'decimal:3', 'posted_at' => 'datetime'];
}
