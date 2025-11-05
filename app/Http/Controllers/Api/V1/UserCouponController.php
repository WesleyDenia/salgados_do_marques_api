<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserCouponResource;
use App\Repositories\UserCouponRepository;
use App\Services\Erp\Vendus\VendusCouponSyncService;

class UserCouponController extends Controller
{
    protected UserCouponRepository $repo;

    public function __construct(
        UserCouponRepository $repo,
        protected VendusCouponSyncService $vendusSyncService
        )
    {
        $this->repo = $repo;
    }

    /**
     * Lista cupons do usuário autenticado.
     */
    public function index(Request $request)
    {
        $status = $request->get('status'); // ex: ?status=pending
        $userCoupons = $this->repo->forUser($request->user()->id, $status);
        return UserCouponResource::collection($userCoupons);
    }

    /**
     * Ativa (ou cria) o cupom para o usuário autenticado.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'coupon_id' => ['required', 'integer', 'exists:coupons,id'],
        ]);

        $userCoupon = $this->repo->activateForUser(
            $request->user()->id,
            $data['coupon_id']
        );

        // 1️⃣ Cria/sincroniza no Vendus
        $couponResponse = $this->vendusSyncService->create($userCoupon);
        Log::info('💬 [Vendus] create returned', ['response' => $couponResponse]);

        // 2️⃣ Persiste localmente o retorno do ERP
        if ($couponResponse) {
            $userCoupon = $this->repo->syncFromErp($userCoupon, $couponResponse);
        }

        return new UserCouponResource($userCoupon);
    }


    /**
     * (Opcional) Desativa cupom do usuário.
     */
    public function destroy(Request $request, int $couponId)
    {
        $this->repo->decrementForUser($request->user()->id, $couponId);
        return response()->json(['message' => 'Cupom desativado.']);
    }
}
