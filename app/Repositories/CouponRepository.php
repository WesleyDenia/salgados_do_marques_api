<?php

namespace App\Repositories;

use App\Models\Coupon;

class CouponRepository extends BaseRepository
{
    public function __construct(Coupon $model) { parent::__construct($model); }

    public function publicPaginate(int $perPage = 15)
    {
        return $this->query()
            ->where('is_loyalty_reward', false)
            ->paginate($perPage);
    }

    public function activeByCode(string $code): ?Coupon
    {
        $now = now();
        return $this->query()
            ->where('code',$code)
            ->where('active', true)
            ->where(function($q) use ($now){
                $q->whereNull('starts_at')->orWhere('starts_at','<=',$now);
            })
            ->where(function($q) use ($now){
                $q->whereNull('ends_at')->orWhere('ends_at','>=',$now);
            })
            ->first();
    }
}
