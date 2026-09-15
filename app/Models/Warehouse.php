<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = ['code', 'name', 'address', 'pic', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
