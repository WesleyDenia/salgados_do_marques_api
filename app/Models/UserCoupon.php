<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserCoupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coupon_id',
        'type',
        'loyalty_reward_id',
        'external_id',
        'external_code',
        'usage_limit',
        'usage_count',
        'expires_at',
        'active',
        'status',
    ];

    protected $casts = [
        'usage_limit'=>'integer',
        'usage_count'=>'integer',
        'expires_at'=>'datetime',
        'active'=>'boolean',
        'loyalty_reward_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function loyaltyReward()
    {
        return $this->belongsTo(LoyaltyReward::class);
    }
}
