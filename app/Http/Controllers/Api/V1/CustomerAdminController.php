<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerUpdateRequest;
use App\Http\Resources\CustomerDetailResource;
use App\Http\Resources\CustomerResource;
use App\Jobs\SyncCustomerToErpJob;
use App\Models\User;
use App\Services\CustomerDirectoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CustomerAdminController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim((string) ($validated['search'] ?? ''));
        $phoneDigits = preg_replace('/\D+/', '', $search) ?? '';

        if (str_starts_with($phoneDigits, '351') && strlen($phoneDigits) > 9) {
            $phoneDigits = substr($phoneDigits, 3);
        }

        $customers = User::query()
            ->where('role', User::ROLE_CLIENTE)
            ->withCount('orders')
            ->when($search !== '', function ($query) use ($search, $phoneDigits) {
                $query->where(function ($customerQuery) use ($search, $phoneDigits) {
                    $customerQuery->where('name', 'like', "%{$search}%");

                    if ($phoneDigits !== '') {
                        $customerQuery
                            ->orWhere('phone', 'like', "%{$phoneDigits}%")
                            ->orWhere('phone_normalized', 'like', "%{$phoneDigits}%");
                    }
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return CustomerResource::collection($customers);
    }

    public function show(User $user)
    {
        $this->ensureCustomer($user);

        $user->load([
            'orders' => fn ($query) => $query
                ->with('store')
                ->orderByDesc('pickup_date')
                ->orderByDesc('pickup_time')
                ->orderByDesc('id'),
        ])->loadCount('orders');

        return new CustomerDetailResource($user);
    }

    public function update(
        CustomerUpdateRequest $request,
        User $user,
        CustomerDirectoryService $customers,
    ) {
        $this->ensureCustomer($user);
        $validated = $request->validated();
        $erpFields = ['name', 'email', 'nif', 'phone'];

        DB::transaction(function () use ($user, $validated, $erpFields, $customers): void {
            $user->fill($validated);

            if (array_key_exists('phone', $validated)) {
                $user->phone_normalized = $customers->normalizePhone($validated['phone']);
            }

            $shouldSyncErp = $user->isDirty($erpFields);

            if ($shouldSyncErp) {
                $user->forceFill([
                    'erp_sync_status' => 'pending',
                    'erp_sync_error' => null,
                ]);
            }

            $user->save();

            if ($shouldSyncErp) {
                SyncCustomerToErpJob::dispatch($user->id)->afterCommit();
            }
        });

        return $this->show($user->refresh());
    }

    private function ensureCustomer(User $user): void
    {
        abort_unless($user->role === User::ROLE_CLIENTE, 404);
    }
}
