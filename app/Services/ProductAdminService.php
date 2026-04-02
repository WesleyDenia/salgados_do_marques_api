<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductAdminService
{
    public function __construct(
        protected AdminImageService $images,
        protected AdminCategoryService $categories,
        protected AdminFlavorService $flavors,
    ) {}

    public function list(): LengthAwarePaginator
    {
        return Product::query()
            ->with('category')
            ->orderBy('name')
            ->paginate(15);
    }

    public function categoryOptions()
    {
        return $this->categories->options();
    }

    public function flavorOptions(): Collection
    {
        return $this->flavors->activeOptions();
    }

    public function loadForEdit(Product $product): Product
    {
        return $product->load(['variants', 'flavors']);
    }

    public function create(array $data, ?UploadedFile $image = null): Product
    {
        $payload = $this->preparePayload($data, $image);

        return DB::transaction(function () use ($payload) {
            $variants = $payload['variants'];
            $flavorIds = $payload['flavor_ids'];
            unset($payload['variants'], $payload['flavor_ids']);

            $product = Product::create($payload);
            $this->syncVariants($product, $variants);
            $this->syncFlavors($product, $flavorIds, true);

            return $product;
        });
    }

    public function update(Product $product, array $data, ?UploadedFile $image = null): Product
    {
        $payload = $this->preparePayload($data, $image, $product);

        return DB::transaction(function () use ($product, $payload) {
            $variants = $payload['variants'];
            $flavorIds = $payload['flavor_ids'];
            $shouldSyncFlavors = $this->hasActiveVariants($variants);
            unset($payload['variants'], $payload['flavor_ids']);

            $product->update($payload);
            $this->syncVariants($product, $variants);
            $this->syncFlavors($product, $flavorIds, $shouldSyncFlavors);

            return $product;
        });
    }

    public function delete(Product $product): void
    {
        $this->images->delete($product->image_url);
        $product->delete();
    }

    protected function preparePayload(array $data, ?UploadedFile $image = null, ?Product $product = null): array
    {
        $data['variants'] = $this->normalizeVariants($data['variants'] ?? []);
        $this->assertVariantsBelongToProduct($product, $data['variants']);

        $data['flavor_ids'] = collect($data['flavor_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->validateVariantFlavorRules($data['variants'], $data['flavor_ids']);

        $removeImage = (bool) ($data['remove_image'] ?? false);
        unset($data['image'], $data['remove_image']);

        $data['active'] = (bool) ($data['active'] ?? false);
        $data['price'] = (float) $data['price'];
        $data['image_url'] = $this->resolveImageUrl($image, $product, $removeImage);

        return $data;
    }

    protected function assertVariantsBelongToProduct(?Product $product, array $variants): void
    {
        if (!$product) {
            return;
        }

        $variantIds = collect($variants)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($variantIds->isEmpty()) {
            return;
        }

        $ownedIds = $product->variants()
            ->whereIn('id', $variantIds)
            ->pluck('id');

        if ($ownedIds->count() !== $variantIds->count()) {
            throw ValidationException::withMessages([
                'variants' => 'Foram recebidas variações inválidas para este produto.',
            ]);
        }
    }

    protected function resolveImageUrl(?UploadedFile $image, ?Product $product, bool $removeImage): ?string
    {
        if (!$product) {
            return $image instanceof UploadedFile
                ? $this->images->store($image, 'products')
                : null;
        }

        return $this->images->replace(
            $product->image_url,
            $image,
            'products',
            $removeImage
        );
    }

    protected function normalizeVariants(array $variants): array
    {
        $normalized = [];

        foreach ($variants as $index => $variant) {
            $hasData = collect($variant)
                ->except(['id', 'active', 'display_order', 'remove'])
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->isNotEmpty();

            if (!$hasData) {
                continue;
            }

            if (empty($variant['name']) || $variant['price'] === null || $variant['price'] === '') {
                throw ValidationException::withMessages([
                    "variants.$index.name" => 'Nome obrigatório',
                    "variants.$index.price" => 'Preço obrigatório',
                ]);
            }

            $normalized[] = [
                'id' => $variant['id'] ?? null,
                'name' => $variant['name'],
                'unit_count' => (int) ($variant['unit_count'] ?? 0),
                'max_flavors' => (int) ($variant['max_flavors'] ?? 0),
                'price' => (float) $variant['price'],
                'active' => (bool) ($variant['active'] ?? false),
                'display_order' => (int) ($variant['display_order'] ?? 0),
                'remove' => (bool) ($variant['remove'] ?? false),
            ];
        }

        return $normalized;
    }

    protected function validateVariantFlavorRules(array $variants, array $flavorIds): void
    {
        $activeVariants = collect($variants)
            ->reject(fn (array $variant) => $variant['remove'])
            ->filter(fn (array $variant) => $variant['active'])
            ->values();

        foreach ($variants as $index => $variant) {
            if ($variant['remove']) {
                continue;
            }

            if ((int) $variant['max_flavors'] < 1) {
                throw ValidationException::withMessages([
                    "variants.$index.max_flavors" => 'Cada variação deve permitir pelo menos 1 sabor.',
                ]);
            }
        }

        if ($activeVariants->isEmpty()) {
            return;
        }

        if (count($flavorIds) < 1) {
            throw ValidationException::withMessages([
                'flavor_ids' => 'Selecione pelo menos 1 sabor permitido para artigos com variações ativas.',
            ]);
        }

        foreach ($variants as $index => $variant) {
            if ($variant['remove'] || !$variant['active']) {
                continue;
            }

            if ((int) $variant['max_flavors'] > count($flavorIds)) {
                throw ValidationException::withMessages([
                    "variants.$index.max_flavors" => 'O limite de sabores da variação não pode exceder os sabores permitidos do artigo.',
                ]);
            }
        }
    }

    protected function syncVariants(Product $product, array $variants): void
    {
        foreach ($variants as $variant) {
            $variantId = $variant['id'] ?? null;
            $remove = $variant['remove'] ?? false;

            if ($variantId && $remove) {
                $product->variants()->where('id', $variantId)->delete();
                continue;
            }

            if ($remove) {
                continue;
            }

            $payload = [
                'name' => $variant['name'],
                'unit_count' => $variant['unit_count'],
                'max_flavors' => $variant['max_flavors'],
                'price' => $variant['price'],
                'active' => $variant['active'],
                'display_order' => $variant['display_order'],
            ];

            if ($variantId) {
                $product->variants()->where('id', $variantId)->update($payload);
            } else {
                $product->variants()->create($payload);
            }
        }
    }

    protected function syncFlavors(Product $product, array $flavorIds, bool $shouldSync): void
    {
        if (!$shouldSync) {
            return;
        }

        $product->flavors()->sync($flavorIds);
    }

    protected function hasActiveVariants(array $variants): bool
    {
        return collect($variants)
            ->reject(fn (array $variant) => $variant['remove'])
            ->contains(fn (array $variant) => $variant['active']);
    }
}
