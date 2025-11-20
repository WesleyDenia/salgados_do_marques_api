<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoyaltyRewardResource;
use App\Http\Resources\UserCouponResource;
use App\Models\LoyaltyReward;
use App\Services\LoyaltyRewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $validated = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $quantity = $validated['quantity'] ?? 1;

        if (config('app.debug')) {
            Log::info('Loyalty reward redeem request', [
                'user_id' => $request->user()?->id,
                'reward_id' => $loyaltyReward->id,
                'quantity' => $quantity,
            ]);
        }

        $userCoupon = $this->service->redeem($request->user(), $loyaltyReward, $quantity);

        return new UserCouponResource($userCoupon);
    }
}
