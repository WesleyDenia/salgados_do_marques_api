<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AdminCategoryService
{
    public function list(): Collection
    {
        return Category::query()
            ->withCount('products')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function options()
    {
        return Category::query()
            ->orderBy('display_order')
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function create(array $data): Category
    {
        return Category::create($this->normalizePayload($data));
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($this->normalizePayload($data));

        return $category;
    }

    public function delete(Category $category): bool
    {
        if ($category->products()->exists()) {
            return false;
        }

        $category->delete();

        return true;
    }

    public function reorder(array $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order as $item) {
                Category::query()
                    ->whereKey($item['id'])
                    ->update(['display_order' => (int) $item['position']]);
            }
        });
    }

    protected function normalizePayload(array $data): array
    {
        $data['active'] = (bool) ($data['active'] ?? false);
        $data['display_order'] = isset($data['display_order']) ? (int) $data['display_order'] : 0;

        return $data;
    }
}
