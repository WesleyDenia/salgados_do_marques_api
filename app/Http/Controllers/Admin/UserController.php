<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserAddLoyaltyPointsRequest;
use App\Http\Requests\Admin\UserAssignCouponRequest;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Http\Requests\Admin\UserUpdatePasswordRequest;
use App\Models\User;
use App\Services\AdminUserService;
use App\Services\LoyaltyService;
use App\Services\UserCouponService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

    public function create()
    {
        return view('admin.users.create', [
            'roles' => User::STAFF_ROLES,
        ]);
    }

    public function store(UserStoreRequest $request)
    {
        $user = $this->adminUserService->create($request->validated());

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'Usuário criado com sucesso.');
    }

    public function show(User $user)
    {
        return view('admin.users.show', [
            ...$this->adminUserService->detailData($user),
            'context' => 'users',
        ]);
    }

    public function customersIndex(Request $request)
    {
        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));

        return view('admin.customers.index', [
            'users' => $this->adminUserService->paginateForCustomers([
                'search' => $search,
                'status' => $status,
            ], 20),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'stats' => $this->adminUserService->customerStats(),
        ]);
    }

    public function customerShow(User $user)
    {
        if ($user->role !== User::ROLE_CLIENTE) {
            throw new NotFoundHttpException();
        }

        return view('admin.users.show', [
            ...$this->adminUserService->detailData($user),
            'context' => 'customers',
        ]);
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $user->isStaff()
                ? User::STAFF_ROLES
                : array_values(array_unique([...User::STAFF_ROLES, $user->role])),
        ]);
    }

    public function update(UserUpdateRequest $request, User $user)
    {
        $this->adminUserService->update($user, $request->validated());

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'Dados do usuário atualizados com sucesso.');
    }

    public function destroy(Request $request, User $user)
    {
        try {
            $this->adminUserService->delete($user, $request->user());
        } catch (\InvalidArgumentException $exception) {
            return redirect()
                ->back()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Usuário excluído com sucesso.');
    }

    public function editPassword(Request $request)
    {
        return view('admin.users.password', [
            'user' => $request->user(),
        ]);
    }

    public function updatePassword(UserUpdatePasswordRequest $request)
    {
        $this->adminUserService->updateOwnPassword(
            $request->user(),
            $request->validated()['password']
        );

        return redirect()
            ->route('admin.users.password.edit')
            ->with('status', 'Senha atualizada com sucesso.');
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
