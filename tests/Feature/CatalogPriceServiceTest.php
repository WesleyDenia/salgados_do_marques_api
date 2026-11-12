<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\OrderTag;
use App\Models\PriceList;
use App\Models\Product;
use App\Services\CatalogPriceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPriceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_normal_prices_and_resolves_them_per_exact_catalog_target(): void
    {
        [$product, $variant] = $this->productWithVariant();
        $service = $this->app->make(CatalogPriceService::class);

        $service->syncNormalPrices($product);

        $this->assertDatabaseHas('price_entries', ['product_id' => $product->id, 'amount' => 1.2]);
        $this->assertDatabaseHas('price_entries', ['product_variant_id' => $variant->id, 'amount' => 25]);
        $this->assertSame(1.2, (float) $service->resolve($product, null, [], Carbon::parse('2026-11-12 12:00'))->amount);
        $this->assertSame(25.0, (float) $service->resolve($product, $variant, [], Carbon::parse('2026-11-12 12:00'))->amount);
    }

    public function test_higher_priority_campaign_applies_by_creation_time_and_staff_tag(): void
    {
        [$product] = $this->productWithVariant();
        $service = $this->app->make(CatalogPriceService::class);
        $service->syncNormalPrices($product);
        $tag = OrderTag::create(['name' => 'Parceiro', 'active' => true]);
        $campaign = PriceList::create([
            'name' => 'Campanha Parceiro',
            'priority' => 10,
            'active' => true,
            'starts_at' => '2026-11-01 00:00:00',
            'ends_at' => '2026-11-30 23:59:59',
        ]);
        $campaign->tags()->sync([$tag->id]);
        $campaign->entries()->create(['product_id' => $product->id, 'amount' => 0.9]);

        $this->assertSame(0.9, (float) $service->resolve($product, null, [$tag->id], Carbon::parse('2026-11-12 12:00'))->amount);
        $this->assertSame(1.2, (float) $service->resolve($product, null, [], Carbon::parse('2026-11-12 12:00'))->amount);
        $this->assertSame(1.2, (float) $service->resolve($product, null, [$tag->id], Carbon::parse('2026-12-01 12:00'))->amount);
    }

    public function test_variant_uses_its_own_legacy_fallback_never_its_parent_price(): void
    {
        [$product, $variant] = $this->productWithVariant();
        $campaign = PriceList::create(['name' => 'Campanha', 'priority' => 10, 'active' => true]);
        $campaign->entries()->create(['product_id' => $product->id, 'amount' => 0.8]);

        $this->assertSame(25.0, (float) $this->app->make(CatalogPriceService::class)->resolve($product, $variant, [], Carbon::now())->amount);
    }

    private function productWithVariant(): array
    {
        $category = Category::create(['name' => 'Salgados', 'active' => true]);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Coxinha', 'price' => 1.2, 'active' => true]);
        $variant = $product->variants()->create(['name' => 'Pack 25', 'unit_count' => 25, 'max_flavors' => 1, 'price' => 25, 'active' => true]);

        return [$product, $variant];
    }
}
