<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['code', 'name', 'contact', 'address', 'bank_account', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
