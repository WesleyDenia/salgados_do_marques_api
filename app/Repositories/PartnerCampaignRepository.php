<?php

namespace App\Repositories;

use App\Models\PartnerCampaign;

class PartnerCampaignRepository extends BaseRepository
{
    public function __construct(PartnerCampaign $model)
    {
        parent::__construct($model);
    }

    public function eligibleByCode(string $code): ?PartnerCampaign
    {
        $now = now();

        return $this->query()
            ->with(['partner', 'coupon.category'])
            ->whereRaw('UPPER(code) = ?', [mb_strtoupper(trim($code))])
            ->where('active', true)
            ->whereHas('partner', fn ($query) => $query->where('active', true))
            ->whereHas('coupon', function ($query) use ($now) {
                $query->where('active', true)
                    ->where('is_loyalty_reward', false)
                    ->where(function ($couponQuery) use ($now) {
                        $couponQuery->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                    })
                    ->where(function ($couponQuery) use ($now) {
                        $couponQuery->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                    });
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->first();
    }
}
