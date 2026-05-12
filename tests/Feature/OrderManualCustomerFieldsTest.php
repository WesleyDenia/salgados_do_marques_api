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

class OrderManualCustomerFieldsTest extends TestCase
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

    public function test_it_keeps_authenticated_app_user_when_manual_customer_data_is_not_sent(): void
    {
        [$product, $store, $user] = $this->makeOrderContext();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'scheduled_at' => now()->addHour()->format('Y-m-d H:i'),
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.id', $user->id);
        $response->assertJsonPath('data.customer_name', null);
        $response->assertJsonPath('data.customer_contact', null);
        $response->assertJsonPath('data.slot', null);
    }

    public function test_it_creates_manual_order_with_nullable_user_and_explicit_customer_fields(): void
    {
        [$product, $store, $staff] = $this->makeOrderContext();

        Sanctum::actingAs($staff);

        $response = $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'scheduled_at' => now()->addHour()->format('Y-m-d H:i'),
            'customer_name' => 'Maria Silva',
            'customer_contact' => '912345678',
            'payment_status' => 'pending',
            'slot' => 'manha',
            'notes' => 'Sem picante',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user', null);
        $response->assertJsonPath('data.customer_name', 'Maria Silva');
        $response->assertJsonPath('data.customer_contact', '912345678');
        $response->assertJsonPath('data.payment_status', 'pending');
        $response->assertJsonPath('data.slot', 'manha');

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Maria Silva',
            'customer_contact' => '912345678',
            'payment_status' => 'pending',
            'slot' => 'manha',
            'user_id' => null,
        ]);
    }

    /**
     * @return array{0: Product, 1: Store, 2: User}
     */
    protected function makeOrderContext(): array
    {
        $user = User::factory()->create([
            'role' => 'atendimento',
        ]);
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

        return [$product, $store, $user];
    }
}
