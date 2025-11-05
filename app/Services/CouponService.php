<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\UserCoupon;
use Illuminate\Support\Facades\DB;

class CouponService
{
    public function assignCoupon(int $userId, Coupon $coupon, ?int $usageLimit = null, ?string $expiresAt = null): UserCoupon
    {
        return DB::transaction(function() use ($userId,$coupon,$usageLimit,$expiresAt){
            return UserCoupon::updateOrCreate(
                ['user_id'=>$userId,'coupon_id'=>$coupon->id],
                ['usage_limit'=>$usageLimit,'expires_at'=>$expiresAt]
            );
        });
    }
}
