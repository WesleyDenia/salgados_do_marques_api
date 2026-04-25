<?php

namespace App\Services;

use App\Models\Flavor;
use Illuminate\Database\Eloquent\Collection;

class AdminFlavorService
{
    public function list(): Collection
    {
        return Flavor::query()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function activeOptions(): Collection
    {
        return Flavor::query()
            ->where('active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @param array<int, int|string> $ids
     * @return array<int, string>
     */
    public function namesByIds(array $ids): array
    {
        $normalizedIds = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($normalizedIds === []) {
            return [];
        }

        return Flavor::query()
            ->whereIn('id', $normalizedIds)
            ->pluck('name', 'id')
            ->all();
    }

    public function create(array $data): Flavor
    {
        return Flavor::create($this->normalizePayload($data));
    }

    public function update(Flavor $flavor, array $data): Flavor
    {
        $flavor->update($this->normalizePayload($data));

        return $flavor;
    }

    public function delete(Flavor $flavor): void
    {
        $flavor->delete();
    }

    protected function normalizePayload(array $data): array
    {
        $data['active'] = (bool) ($data['active'] ?? false);
        $data['display_order'] = isset($data['display_order']) ? (int) $data['display_order'] : 0;

        return $data;
    }
}
