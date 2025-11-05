<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'code',
        'image_url',
        'starts_at',
        'ends_at',
        'recurrence',
        'active',
        'category_id',
        'type',
        'amount',
        'is_loyalty_reward',
    ];

    protected $casts = [
        'starts_at'=>'datetime',
        'ends_at'=>'datetime',
        'active'=>'boolean',
        'amount' => 'decimal:2',
        'is_loyalty_reward' => 'boolean',
    ];

    public function userCoupons()
    {
        return $this->hasMany(UserCoupon::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
