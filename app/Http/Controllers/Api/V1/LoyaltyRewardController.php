<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoyaltyRewardResource;
use App\Http\Resources\UserCouponResource;
use App\Models\LoyaltyReward;
use App\Services\LoyaltyRewardService;
use Illuminate\Http\Request;

class LoyaltyRewardController extends Controller
{
    public function __construct(
        protected LoyaltyRewardService $service
    ) {}

    public function index(Request $request)
    {
        $rewards = $this->service->list($request->user());
        return LoyaltyRewardResource::collection($rewards);
    }

    public function redeem(Request $request, LoyaltyReward $loyaltyReward)
    {
        $userCoupon = $this->service->redeem($request->user(), $loyaltyReward);

        return new UserCouponResource($userCoupon);
    }
}
