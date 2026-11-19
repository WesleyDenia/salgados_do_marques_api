<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\User;
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
}
