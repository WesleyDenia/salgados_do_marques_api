<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoyaltyRewardRequest;
use App\Models\LoyaltyReward;
use App\Services\LoyaltyRewardService;

class LoyaltyRewardController extends Controller
{
    public function __construct(protected LoyaltyRewardService $rewards) {}

    public function index()
    {
        return view('admin.loyalty-rewards.index', [
            'rewards' => $this->rewards->listAdmin(),
        ]);
    }

    public function create()
    {
        return view('admin.loyalty-rewards.create', [
            'reward' => new LoyaltyReward([
                'active' => true,
            ]),
        ]);
    }

    public function store(LoyaltyRewardRequest $request)
    {
        $this->rewards->createAdmin($request->validated(), $request->file('image'));

        return redirect()
            ->route('admin.loyalty-rewards.index')
            ->with('status', 'Recompensa criada com sucesso.');
    }

    public function edit(LoyaltyReward $loyaltyReward)
    {
        return view('admin.loyalty-rewards.edit', [
            'reward' => $loyaltyReward,
        ]);
    }

    public function update(LoyaltyRewardRequest $request, LoyaltyReward $loyaltyReward)
    {
        $this->rewards->updateAdmin($loyaltyReward, $request->validated(), $request->file('image'));

        return redirect()
            ->route('admin.loyalty-rewards.index')
            ->with('status', 'Recompensa atualizada com sucesso.');
    }

    public function destroy(LoyaltyReward $loyaltyReward)
    {
        $this->rewards->deleteAdmin($loyaltyReward);

        return redirect()
            ->route('admin.loyalty-rewards.index')
            ->with('status', 'Recompensa removida com sucesso.');
    }
}
