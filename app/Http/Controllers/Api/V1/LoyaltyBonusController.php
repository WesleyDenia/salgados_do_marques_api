<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Services\LoyaltyService;
use App\Http\Controllers\Controller;

class LoyaltyBonusController extends Controller
{
    public function __construct(
        protected LoyaltyService $loyalty
    ) {}

    public function claim(Request $request)
    {
        $user = $request->user();

        // Já recebeu o bônus?
        if ($user->loyalty_synced) {
            return response()->json([
                'message' => 'Bônus de boas-vindas já foi recebido 🎁',
                'already_claimed' => true,
            ], 200);
        }

        // Concede o bônus
        $points = $this->loyalty->grantWelcomeBonus($user);

        return response()->json([
            'message' => "Bônus de boas-vindas de {$points} Coinxinhas concedido com sucesso 🎉",
            'points' => $points,
            'already_claimed' => false,
        ]);
    }
}
