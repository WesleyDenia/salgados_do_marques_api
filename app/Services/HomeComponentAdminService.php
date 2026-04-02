<?php

namespace App\Services;

use App\Models\ContentHome;
use App\Models\HomeComponent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class HomeComponentAdminService
{
    public function list(): LengthAwarePaginator
    {
        return HomeComponent::query()
            ->orderByDesc('is_active')
            ->orderBy('label')
            ->paginate(20);
    }

    public function create(array $data): HomeComponent
    {
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        return HomeComponent::create($data);
    }

    public function update(HomeComponent $component, array $data): HomeComponent
    {
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $component->update($data);

        return $component;
    }

    public function delete(HomeComponent $component): bool
    {
        $inUse = $component->key
            && ContentHome::query()
                ->where('component_name', $component->key)
                ->exists();

        if ($inUse) {
            return false;
        }

        $component->delete();

        return true;
    }

    public function availableOptions(?string $selectedKey = null): array
    {
        return HomeComponent::query()
            ->when($selectedKey, function ($query, $selectedKey) {
                $query->where('is_active', true)
                    ->orWhere('key', $selectedKey);
            }, function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('label')
            ->pluck('label', 'key')
            ->all();
    }
}
