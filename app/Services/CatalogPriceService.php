<?php

namespace App\Services;

use App\Models\PriceEntry;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\PriceListRepository;
use Carbon\CarbonInterface;

class CatalogPriceService
{
    public function __construct(protected PriceListRepository $prices) {}

    public function resolve(Product $product, ?ProductVariant $variant, array $tagIds, CarbonInterface $createdAt): PriceEntry
    {
        $entry = $this->prices->resolveEntry($product, $variant, $tagIds, $createdAt);

        if ($entry !== null) {
            return $entry;
        }

        // Transitional fallback for catalogue records created outside the new
        // admin/API flows. It is still exact-target: a pack never uses its parent price.
        return new PriceEntry([
            'product_id' => $variant === null ? $product->id : null,
            'product_variant_id' => $variant?->id,
            'amount' => $variant?->price ?? $product->price,
        ]);
    }

    public function syncNormalPrices(Product $product): void
    {
        $this->prices->syncNormalPrices($product);
    }
}
