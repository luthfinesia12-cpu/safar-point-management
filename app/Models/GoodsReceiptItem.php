<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceiptItem extends Model
{
    protected $fillable = ['product_id', 'quantity', 'damaged_quantity'];

    protected $casts = ['quantity' => 'decimal:3', 'damaged_quantity' => 'decimal:3'];
}
