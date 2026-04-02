<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserCouponActivateRequest;
use App\Http\Resources\UserCouponResource;
use App\Services\UserCouponService;
use Illuminate\Http\Request;

class UserCouponController extends Controller
{
    public function __construct(protected UserCouponService $service) {}

    /**
     * Lista cupons do usuário autenticado.
     */
    public function index(Request $request)
    {
        $status = $request->get('status'); // ex: ?status=pending
        $userCoupons = $this->service->listForUser($request->user(), $status);
        return UserCouponResource::collection($userCoupons);
    }

    /**
     * Ativa (ou cria) o cupom para o usuário autenticado.
     */
    public function store(UserCouponActivateRequest $request)
    {
        $userCoupon = $this->service->activateForUser(
            $request->user(),
            $request->validated()['coupon_id']
        );

        return new UserCouponResource($userCoupon);
    }


    /**
     * (Opcional) Desativa cupom do usuário.
     */
    public function destroy(Request $request, int $couponId)
    {
        $this->service->decrementForUser($request->user(), $couponId);
        return response()->json(['message' => 'Cupom desativado.']);
    }
}
