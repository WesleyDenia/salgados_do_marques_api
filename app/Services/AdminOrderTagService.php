<?php

namespace App\Services;

use App\Models\OrderTag;
use Illuminate\Database\Eloquent\Collection;

class AdminOrderTagService
{
    public function list(): Collection
    {
        return OrderTag::query()
            ->withCount('orders')
            ->orderByDesc('active')
            ->orderBy('name')
            ->get();
    }

    public function options(bool $activeOnly = false): Collection
    {
        return OrderTag::query()
            ->when($activeOnly, fn ($query) => $query->where('active', true))
            ->orderByDesc('active')
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'active']);
    }

    public function create(array $data): OrderTag
    {
        return OrderTag::query()->create($this->normalizePayload($data));
    }

    public function update(OrderTag $orderTag, array $data): OrderTag
    {
        $orderTag->update($this->normalizePayload($data));

        return $orderTag->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizePayload(array $data): array
    {
        return [
            'name' => trim((string) $data['name']),
            'color' => strtoupper((string) $data['color']),
            'active' => array_key_exists('active', $data) ? (bool) $data['active'] : true,
        ];
    }
}
