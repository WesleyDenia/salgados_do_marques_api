<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Flavor;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiFlavorTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_uses_a_default_page_size_of_one_hundred_and_respects_an_explicit_size(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $category = Category::create(['name' => 'Salgados', 'active' => true]);

        foreach (range(1, 101) as $index) {
            Product::create([
                'name' => "Produto {$index}",
                'description' => null,
                'price' => 5,
                'category_id' => $category->id,
                'active' => true,
            ]);
        }

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonCount(100, 'data')
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonPath('meta.total', 101);

        $this->getJson('/api/v1/products?per_page=15')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 101);
    }

    public function test_products_index_filters_active_products_by_category(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $miniPortions = Category::create(['name' => 'Mini porções', 'active' => true]);
        $salgados = Category::create(['name' => 'Salgados', 'active' => true]);
        $visibleProductIds = collect(range(1, 5))
            ->map(fn (int $index) => Product::create([
                'name' => "Mini porção {$index}",
                'price' => 4,
                'category_id' => $miniPortions->id,
                'active' => true,
            ])->id)
            ->sort()
            ->values()
            ->all();
        Product::create([
            'name' => 'Mini indisponível',
            'price' => 4,
            'category_id' => $miniPortions->id,
            'active' => false,
        ]);
        Product::create([
            'name' => 'Rissol',
            'price' => 4,
            'category_id' => $salgados->id,
            'active' => true,
        ]);

        $response = $this->getJson("/api/v1/products?category_id={$miniPortions->id}")
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 100);

        $this->assertSame(
            $visibleProductIds,
            collect($response->json('data'))->pluck('id')->sort()->values()->all(),
        );
    }

    public function test_products_index_returns_only_active_allowed_flavors_for_each_product(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $category = Category::create(['name' => 'Salgados', 'active' => true]);
        $product = Product::create([
            'name' => 'Pack Festa',
            'description' => 'Teste',
            'price' => 14.9,
            'category_id' => $category->id,
            'active' => true,
        ]);

        $allowedA = Flavor::create(['name' => 'Azeitona', 'active' => true, 'display_order' => 2]);
        $allowedB = Flavor::create(['name' => 'Bacalhau', 'active' => true, 'display_order' => 1]);
        $inactive = Flavor::create(['name' => 'Inativo', 'active' => false, 'display_order' => 0]);
        $other = Flavor::create(['name' => 'Outro', 'active' => true, 'display_order' => 0]);

        $product->flavors()->sync([$allowedA->id, $allowedB->id, $inactive->id]);

        Product::create([
            'name' => 'Produto extra',
            'description' => null,
            'price' => 5,
            'category_id' => $category->id,
            'active' => true,
        ])->flavors()->sync([$other->id]);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk();
        $products = collect($response->json('data'));
        $pack = $products->firstWhere('id', $product->id);

        $this->assertNotNull($pack);
        $this->assertSame(
            ['Bacalhau', 'Azeitona'],
            collect($pack['allowed_flavors'] ?? [])->pluck('name')->all()
        );
        $this->assertNotContains('Inativo', collect($pack['allowed_flavors'] ?? [])->pluck('name')->all());
    }

    public function test_product_detail_returns_only_active_allowed_flavors(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $category = Category::create(['name' => 'Salgados', 'active' => true]);
        $product = Product::create([
            'name' => 'Pack Festa',
            'description' => 'Teste',
            'price' => 14.9,
            'category_id' => $category->id,
            'active' => true,
        ]);

        $allowed = Flavor::create(['name' => 'Frango', 'active' => true, 'display_order' => 1]);
        $blocked = Flavor::create(['name' => 'Doce', 'active' => false, 'display_order' => 2]);

        $product->flavors()->sync([$allowed->id, $blocked->id]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertOk();
        $response->assertJsonPath('data.allowed_flavors.0.name', 'Frango');
        $response->assertJsonCount(1, 'data.allowed_flavors');
    }
}
