<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'name',
        'address',
        'city',
        'latitude',
        'longitude',
        'phone',
        'type',
        'is_active',
        'accepts_orders',
        'default_store',
        'pickup_weekly_schedule',
        'pickup_date_exceptions',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
        'accepts_orders' => 'boolean',
        'default_store' => 'boolean',
        'pickup_weekly_schedule' => 'array',
        'pickup_date_exceptions' => 'array',
    ];
}
