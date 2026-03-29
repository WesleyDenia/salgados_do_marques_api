<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeComponent extends Model
{
    protected $fillable = [
        'key',
        'label',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
