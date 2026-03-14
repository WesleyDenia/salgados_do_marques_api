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
