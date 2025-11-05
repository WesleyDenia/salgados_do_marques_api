<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoyaltyEarnRequest;
use App\Http\Resources\{LoyaltyStatusResource, LoyaltySummaryResource, LoyaltyTransactionResource};
use App\Services\{LoyaltyRewardService, LoyaltyService};
use App\Models\User;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function __construct(
        protected LoyaltyService $service,
        protected LoyaltyRewardService $rewardService
    ) {}

    public function status(Request $request)
    {
        $data = $this->service->getStatus($request->user());
        return new LoyaltyStatusResource($data);
    }

    public function transactions(Request $request)
    {
        $transactions = $this->service->listTransactions(
            $request->user(),
            $request->get('per_page', 20)
        );

        return LoyaltyTransactionResource::collection($transactions);
    }

    public function summary(Request $request)
    {
        $user = $request->user();
        $status = $this->service->getStatus($user);
        $rewards = $this->rewardService->list($user);

        return new LoyaltySummaryResource([
            'points' => $status['points'] ?? 0,
            'next_reward_at' => $status['next_reward_at'] ?? null,
            'rewards' => $rewards,
        ]);
    }

    public function earn(LoyaltyEarnRequest $request)
    {
        $data = $request->validated();
        $user = User::findOrFail($data['user_id']);

        $this->service->earnPoints(
            $user,
            $data['points'],
            $data['reason'] ?? null,
            $data['meta'] ?? []
        );

        return response()->json(['message' => 'Pontos creditados com sucesso.']);
    }
}
