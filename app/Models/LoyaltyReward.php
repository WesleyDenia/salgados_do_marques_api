<?php

// app/Models/LoyaltyReward.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'threshold',
        'value',
        'image_url',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'threshold' => 'integer',
        'value' => 'decimal:2',
    ];

    public function userCoupons()
    {
        return $this->hasMany(UserCoupon::class);
    }
}
