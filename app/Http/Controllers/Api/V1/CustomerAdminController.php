<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerUpdateRequest;
use App\Http\Resources\CustomerDetailResource;
use App\Http\Resources\CustomerResource;
use App\Jobs\SyncCustomerToErpJob;
use App\Models\User;
use App\Services\CustomerDirectoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerAdminController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['name', 'phone', 'orders_count', 'orders_total'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $search = trim((string) ($validated['search'] ?? ''));
        $sort = $validated['sort'] ?? 'name';
        $direction = $validated['direction'] ?? 'asc';
        $phoneDigits = preg_replace('/\D+/', '', $search) ?? '';

        if (str_starts_with($phoneDigits, '351') && strlen($phoneDigits) > 9) {
            $phoneDigits = substr($phoneDigits, 3);
        }

        $customers = User::query()
            ->where('role', User::ROLE_CLIENTE)
            ->withCount('orders')
            ->withSum([
                'orders as orders_total' => fn ($query) => $query->where('status', '!=', 'canceled'),
            ], 'total')
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
            ->when($sort === 'phone', fn ($query) => $query->orderByRaw("COALESCE(phone_normalized, phone) {$direction}"))
            ->when($sort !== 'phone', fn ($query) => $query->orderBy($sort, $direction))
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
