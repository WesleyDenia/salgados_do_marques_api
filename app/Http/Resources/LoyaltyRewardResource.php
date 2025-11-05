<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyRewardResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'threshold'   => $this->threshold,
            'value'       => $this->value !== null ? (float) $this->value : null,
            'image'       => $this->image_url,
            'active'      => $this->active,
            'user_coupon' => $this->when(
                $this->relationLoaded('userCoupon'),
                function () {
                    $coupon = $this->getRelationValue('userCoupon');

                    if (!$coupon) {
                        return null;
                    }

                    return [
                        'id' => $coupon->id,
                        'external_code' => $coupon->external_code,
                        'status' => $coupon->status,
                        'type' => $coupon->type,
                        'coupon' => $coupon->relationLoaded('coupon')
                            ? new CouponResource($coupon->coupon)
                            : null,
                    ];
                }
            ),
        ];
    }
}
