<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Flavor;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderFlavorValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-03-16 10:00', 'Europe/Lisbon'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_order_with_variant_accepts_only_allowed_flavors_within_limit(): void
    {
        [$product, $variant, $store, $user, $flavors] = $this->makeOrderContext();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'scheduled_at' => now()->addHour()->format('Y-m-d H:i'),
            'items' => [
                [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => 1,
                    'flavors' => [$flavors['allowedA']->id, $flavors['allowedB']->id],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.items.0.options.flavors.0', $flavors['allowedA']->id);
        $response->assertJsonPath('data.items.0.options.flavors.1', $flavors['allowedB']->id);
    }

    public function test_order_rejects_flavors_for_product_without_variant(): void
    {
        [$product, $variant, $store, $user, $flavors] = $this->makeOrderContext();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'scheduled_at' => now()->addHour()->format('Y-m-d H:i'),
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'flavors' => [$flavors['allowedA']->id],
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
    }

    public function test_order_rejects_missing_flavors_above_limit_unrelated_or_inactive_flavors(): void
    {
        [$product, $variant, $store, $user, $flavors] = $this->makeOrderContext();

        Sanctum::actingAs($user);

        $basePayload = [
            'store_id' => $store->id,
            'scheduled_at' => now()->addHour()->format('Y-m-d H:i'),
            'items' => [
                [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $this->postJson('/api/v1/orders', $basePayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);

        $this->postJson('/api/v1/orders', [
            ...$basePayload,
            'items' => [[
                ...$basePayload['items'][0],
                'flavors' => [$flavors['allowedA']->id, $flavors['allowedB']->id, $flavors['allowedA']->id],
            ]],
        ])->assertStatus(422)->assertJsonValidationErrors(['items']);

        $this->postJson('/api/v1/orders', [
            ...$basePayload,
            'items' => [[
                ...$basePayload['items'][0],
                'flavors' => [$flavors['other']->id],
            ]],
        ])->assertStatus(422)->assertJsonValidationErrors(['items']);

        $this->postJson('/api/v1/orders', [
            ...$basePayload,
            'items' => [[
                ...$basePayload['items'][0],
                'flavors' => [$flavors['inactive']->id],
            ]],
        ])->assertStatus(422)->assertJsonValidationErrors(['items']);
    }

    /**
     * @return array{0: Product, 1: ProductVariant, 2: Store, 3: User, 4: array<string, Flavor>}
     */
    protected function makeOrderContext(): array
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Salgados', 'active' => true]);
        $store = Store::create([
            'name' => 'Loja Centro',
            'address' => 'Rua 1',
            'city' => 'Lisboa',
            'latitude' => 38.7169,
            'longitude' => -9.1399,
            'phone' => '123456789',
            'type' => 'principal',
            'is_active' => true,
            'accepts_orders' => true,
            'default_store' => true,
            'pickup_weekly_schedule' => [
                'monday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '18:00'],
                'tuesday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '18:00'],
                'wednesday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '18:00'],
                'thursday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '18:00'],
                'friday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '18:00'],
                'saturday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '18:00'],
                'sunday' => ['is_open' => true, 'start_time' => '09:00', 'end_time' => '18:00'],
            ],
            'pickup_date_exceptions' => [],
        ]);
        $product = Product::create([
            'name' => 'Pack Festa',
            'description' => 'Teste',
            'price' => 14.9,
            'category_id' => $category->id,
            'active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Pack 25',
            'unit_count' => 25,
            'max_flavors' => 2,
            'price' => 14.9,
            'active' => true,
            'display_order' => 0,
        ]);

        $allowedA = Flavor::create(['name' => 'Frango', 'active' => true, 'display_order' => 1]);
        $allowedB = Flavor::create(['name' => 'Carne', 'active' => true, 'display_order' => 2]);
        $inactive = Flavor::create(['name' => 'Inativo', 'active' => false, 'display_order' => 3]);
        $other = Flavor::create(['name' => 'Outro artigo', 'active' => true, 'display_order' => 4]);

        $product->flavors()->sync([$allowedA->id, $allowedB->id]);

        return [$product, $variant, $store, $user, compact('allowedA', 'allowedB', 'inactive', 'other')];
    }
}
