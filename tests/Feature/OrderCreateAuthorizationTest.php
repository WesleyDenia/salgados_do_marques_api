<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderCreateAuthorizationTest extends TestCase
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

    public function test_cliente_role_cannot_create_orders_through_api(): void
    {
        [$product, $store] = $this->makeOrderContext();
        $user = User::factory()->create(['role' => 'cliente']);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', $this->payload($store->id, $product->id));

        $response->assertForbidden();
    }

    public function test_atendimento_role_can_create_orders_through_api(): void
    {
        [$product, $store] = $this->makeOrderContext();
        $user = User::factory()->create(['role' => 'atendimento']);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', $this->payload($store->id, $product->id));

        $response->assertOk();
    }

    /**
     * @return array{store_id:int, scheduled_at:string, customer_name:string, customer_contact:string, payment_status:string, slot:string, items:array<int, array{product_id:int, quantity:int}>}
     */
    protected function payload(int $storeId, int $productId): array
    {
        return [
            'store_id' => $storeId,
            'scheduled_at' => now()->addHour()->format('Y-m-d H:i'),
            'customer_name' => 'Maria Silva',
            'customer_contact' => '912345678',
            'payment_status' => 'pending',
            'slot' => 'manha',
            'items' => [
                [
                    'product_id' => $productId,
                    'quantity' => 1,
                ],
            ],
        ];
    }

    /**
     * @return array{0: Product, 1: Store}
     */
    protected function makeOrderContext(): array
    {
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
            'name' => 'Coxinha',
            'description' => 'Teste',
            'price' => 2.5,
            'category_id' => $category->id,
            'active' => true,
        ]);

        return [$product, $store];
    }
}
