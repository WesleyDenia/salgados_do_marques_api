<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserAddLoyaltyPointsRequest;
use App\Http\Requests\Admin\UserAssignCouponRequest;
use App\Models\User;
use App\Services\AdminUserService;
use App\Services\LoyaltyService;
use App\Services\UserCouponService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserController extends Controller
{
    public function __construct(
        protected AdminUserService $adminUserService,
        protected LoyaltyService $loyaltyService,
        protected UserCouponService $userCouponService,
    ) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->string('search'));
        $role = trim((string) $request->string('role'));
        $status = trim((string) $request->string('status'));

        return view('admin.users.index', [
            'users' => $this->adminUserService->paginateForAdmin([
                'search' => $search,
                'role' => $role,
                'status' => $status,
            ], 20),
            'filters' => [
                'search' => $search,
                'role' => $role,
                'status' => $status,
            ],
            'stats' => $this->adminUserService->stats(),
        ]);
    }

    public function show(User $user)
    {
        return view('admin.users.show', $this->adminUserService->detailData($user));
    }

    public function storeLoyalty(UserAddLoyaltyPointsRequest $request, User $user)
    {
        $data = $request->validated();

        $this->loyaltyService->earnPoints(
            $user,
            (int) $data['points'],
            $data['reason'],
            [
                'source' => 'admin_panel',
                'admin_user_id' => $request->user()?->id,
            ]
        );

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'Pontos de fidelidade adicionados com sucesso.');
    }

    public function storeCoupon(UserAssignCouponRequest $request, User $user)
    {
        try {
            $this->userCouponService->activateForUser(
                $user,
                (int) $request->validated()['coupon_id']
            );
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.users.show', $user)
                ->with('error', collect($exception->errors())->flatten()->first() ?: 'Não foi possível atribuir o cupom.');
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() !== 422) {
                throw $exception;
            }

            return redirect()
                ->route('admin.users.show', $user)
                ->with('error', $exception->getMessage() ?: 'Não foi possível atribuir o cupom.');
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'Cupom atribuído ao usuário com sucesso.');
    }
}
