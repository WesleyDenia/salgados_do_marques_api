<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Flavor;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFlavorAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_product_with_variants_and_allowed_flavors(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Salgados', 'active' => true]);
        $flavorA = Flavor::create(['name' => 'Frango', 'active' => true, 'display_order' => 1]);
        $flavorB = Flavor::create(['name' => 'Carne', 'active' => true, 'display_order' => 2]);

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Pack Festa',
            'description' => 'Teste',
            'price' => 12.5,
            'category_id' => $category->id,
            'active' => '1',
            'flavor_ids' => [$flavorA->id, $flavorB->id, $flavorA->id],
            'variants' => [
                [
                    'name' => 'Pack 25',
                    'unit_count' => 25,
                    'max_flavors' => 2,
                    'price' => 12.5,
                    'active' => '1',
                    'display_order' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('name', 'Pack Festa')->firstOrFail();

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'name' => 'Pack 25',
            'max_flavors' => 2,
        ]);
        $this->assertDatabaseCount('product_flavor', 2);
        $this->assertEqualsCanonicalizing(
            [$flavorA->id, $flavorB->id],
            $product->flavors()->pluck('flavors.id')->all()
        );
    }

    public function test_admin_cannot_store_active_variants_without_allowed_flavors(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Salgados', 'active' => true]);

        $response = $this->from(route('admin.products.create'))
            ->actingAs($admin)
            ->post(route('admin.products.store'), [
                'name' => 'Pack sem sabor',
                'price' => 10,
                'category_id' => $category->id,
                'variants' => [
                    [
                        'name' => 'Pack 10',
                        'unit_count' => 10,
                        'max_flavors' => 1,
                        'price' => 10,
                        'active' => '1',
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.products.create'));
        $response->assertSessionHasErrors(['flavor_ids']);
    }

    public function test_admin_cannot_store_variant_with_max_flavors_above_allowed_flavors_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Salgados', 'active' => true]);
        $flavor = Flavor::create(['name' => 'Frango', 'active' => true, 'display_order' => 1]);

        $response = $this->from(route('admin.products.create'))
            ->actingAs($admin)
            ->post(route('admin.products.store'), [
                'name' => 'Pack inválido',
                'price' => 10,
                'category_id' => $category->id,
                'flavor_ids' => [$flavor->id],
                'variants' => [
                    [
                        'name' => 'Pack 10',
                        'unit_count' => 10,
                        'max_flavors' => 2,
                        'price' => 10,
                        'active' => '1',
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.products.create'));
        $response->assertSessionHasErrors(['variants.0.max_flavors']);
    }
}
