<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = ['name', 'address', 'phone', 'email', 'logo_path'];
}
