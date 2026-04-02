<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Flavor;
use App\Models\Product;
use App\Services\ProductAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductAdminServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_product_with_variants_flavors_and_image(): void
    {
        Storage::fake('public');

        $category = Category::create(['name' => 'Salgados', 'active' => true]);
        $flavorA = Flavor::create(['name' => 'Frango', 'active' => true, 'display_order' => 1]);
        $flavorB = Flavor::create(['name' => 'Carne', 'active' => true, 'display_order' => 2]);

        /** @var ProductAdminService $service */
        $service = $this->app->make(ProductAdminService::class);

        $product = $service->create([
            'name' => 'Pack Festa',
            'description' => 'Descrição',
            'price' => '12.50',
            'category_id' => $category->id,
            'active' => true,
            'flavor_ids' => [$flavorA->id, $flavorB->id, $flavorA->id],
            'variants' => [
                [
                    'name' => 'Pack 25',
                    'unit_count' => 25,
                    'max_flavors' => 2,
                    'price' => 12.5,
                    'active' => true,
                    'display_order' => 0,
                ],
            ],
        ], UploadedFile::fake()->image('product.jpg'));

        $this->assertSame('Pack Festa', $product->name);
        $this->assertStringStartsWith('/storage/products/', (string) $product->image_url);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'name' => 'Pack 25',
            'max_flavors' => 2,
        ]);
        $this->assertEqualsCanonicalizing(
            [$flavorA->id, $flavorB->id],
            $product->flavors()->pluck('flavors.id')->all()
        );
    }

    public function test_it_rejects_active_variants_without_allowed_flavors(): void
    {
        $category = Category::create(['name' => 'Salgados', 'active' => true]);

        $this->expectException(ValidationException::class);

        $service = $this->app->make(ProductAdminService::class);
        $service->create([
            'name' => 'Pack inválido',
            'price' => 10,
            'category_id' => $category->id,
            'variants' => [
                [
                    'name' => 'Pack 10',
                    'unit_count' => 10,
                    'max_flavors' => 1,
                    'price' => 10,
                    'active' => true,
                ],
            ],
        ]);
    }

    public function test_it_updates_variants_and_keeps_existing_flavors_when_all_variants_become_inactive(): void
    {
        $category = Category::create(['name' => 'Salgados', 'active' => true]);
        $flavor = Flavor::create(['name' => 'Frango', 'active' => true, 'display_order' => 1]);
        $product = Product::create([
            'name' => 'Pack',
            'price' => 10,
            'category_id' => $category->id,
            'active' => true,
        ]);
        $variant = $product->variants()->create([
            'name' => 'Pack 10',
            'unit_count' => 10,
            'max_flavors' => 1,
            'price' => 10,
            'active' => true,
            'display_order' => 0,
        ]);
        $product->flavors()->sync([$flavor->id]);

        $service = $this->app->make(ProductAdminService::class);
        $service->update($product, [
            'name' => 'Pack',
            'price' => 11,
            'category_id' => $category->id,
            'active' => true,
            'flavor_ids' => [],
            'variants' => [
                [
                    'id' => $variant->id,
                    'name' => 'Pack 10',
                    'unit_count' => 10,
                    'max_flavors' => 1,
                    'price' => 11,
                    'active' => false,
                    'display_order' => 0,
                ],
            ],
        ]);

        $this->assertSame([$flavor->id], $product->fresh()->flavors()->pluck('flavors.id')->all());
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'active' => false,
            'price' => 11,
        ]);
    }
}
