<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UrbanCampaignCouponConfig extends Model
{
    use HasFactory;

    public const TYPE_MONEY = 'money';

    public const TYPE_PERCENT = 'percent';

    protected $fillable = [
        'coupon_type',
        'title',
        'description',
        'duration_days',
        'discount_type',
        'amount',
        'active',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'amount' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function claims(): HasMany
    {
        return $this->hasMany(UrbanCampaignCouponClaim::class, 'coupon_config_id');
    }
}
