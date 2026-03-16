<?php

namespace App\Repositories;

use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StoreRepository
{
    public function adminPaginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Store::query();

        if (!empty($filters['city'])) {
            $query->where('city', 'like', '%' . $filters['city'] . '%');
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($filters);
    }

    public function apiIndex(array $filters): Collection
    {
        $query = Store::query()->where('is_active', true);

        if (!empty($filters['city'])) {
            $query->where('city', 'like', '%' . $filters['city'] . '%');
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (array_key_exists('accepts_orders', $filters) && $filters['accepts_orders'] !== null) {
            $query->where('accepts_orders', (bool) $filters['accepts_orders']);
        }

        if (($filters['lat'] ?? null) !== null && ($filters['lng'] ?? null) !== null) {
            $lat = (float) $filters['lat'];
            $lng = (float) $filters['lng'];

            $query->select(['stores.*'])->selectRaw(
                '2 * 6371 * ASIN(SQRT(POWER(SIN(RADIANS(? - latitude) / 2), 2) + COS(RADIANS(latitude)) * COS(RADIANS(?)) * POWER(SIN(RADIANS(? - longitude) / 2), 2))) AS distance_km',
                [$lat, $lat, $lng]
            );

            return $query->orderBy('distance_km')->get();
        }

        return $query->orderBy('name')->get();
    }

    public function findById(int $id): ?Store
    {
        return Store::query()->find($id);
    }

    public function create(array $data): Store
    {
        return Store::query()->create($data);
    }

    public function update(Store $store, array $data): Store
    {
        $store->update($data);

        return $store->fresh();
    }
}
