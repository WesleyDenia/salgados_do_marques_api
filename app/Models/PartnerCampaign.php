<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'coupon_id',
        'public_name',
        'code',
        'active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function userCoupons()
    {
        return $this->hasMany(UserCoupon::class);
    }
}
