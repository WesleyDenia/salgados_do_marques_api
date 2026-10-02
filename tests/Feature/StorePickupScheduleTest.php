<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StorePickupScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_stores_endpoint_excludes_ordering_stores_without_valid_schedule(): void
    {
        $eligibleStore = $this->makeStore('Loja Agenda OK');
        $invalidScheduleStore = Store::create([
            'name' => 'Loja Sem Agenda',
            'address' => 'Rua 2',
            'city' => 'Lisboa',
            'latitude' => 38.71,
            'longitude' => -9.13,
            'phone' => '111111111',
            'type' => 'principal',
            'is_active' => true,
            'accepts_orders' => true,
            'default_store' => false,
            'pickup_weekly_schedule' => null,
            'pickup_date_exceptions' => [],
        ]);

        $response = $this->getJson('/api/v1/stores?accepts_orders=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $eligibleStore->id);
        $response->assertJsonMissing(['id' => $invalidScheduleStore->id]);
    }

    public function test_order_accepts_valid_store_specific_pickup_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 10:00', 'Europe/Lisbon'));
        [$user, $product, $store] = $this->makeOrderContext();
        $user->update(['role' => 'atendimento']);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'scheduled_at' => '2026-03-17 16:30',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.store.id', $store->id);
    }

    public function test_order_rejects_closed_date_outside_sunday_window_and_closed_exception(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 10:00', 'Europe/Lisbon'));
        [$user, $product, $store] = $this->makeOrderContext();

        Sanctum::actingAs($user);

        $payload = [
            'store_id' => $store->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $this->postJson('/api/v1/orders', [
            ...$payload,
            'scheduled_at' => '2026-03-16 12:30',
        ])->assertStatus(422)->assertJsonValidationErrors(['scheduled_at']);

        $this->postJson('/api/v1/orders', [
            ...$payload,
            'scheduled_at' => '2026-03-22 13:30',
        ])->assertStatus(422)->assertJsonValidationErrors(['scheduled_at']);

        $this->postJson('/api/v1/orders', [
            ...$payload,
            'scheduled_at' => '2026-03-18 15:00',
        ])->assertStatus(422)->assertJsonValidationErrors(['scheduled_at']);
    }

    public function test_order_accepts_custom_exception_window_and_rejects_bypassed_invalid_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 10:00', 'Europe/Lisbon'));
        [$user, $product, $store] = $this->makeOrderContext();

        Sanctum::actingAs($user);

        $payload = [
            'store_id' => $store->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $this->postJson('/api/v1/orders', [
            ...$payload,
            'scheduled_at' => '2026-03-19 15:30',
        ])->assertOk();

        $this->postJson('/api/v1/orders', [
            ...$payload,
            'scheduled_at' => '2026-03-19 14:30',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_at'])
            ->assertJsonPath('errors.scheduled_at.0', 'O horário escolhido está fora do funcionamento dessa loja.');
    }

    public function test_order_accepts_schedule_exception_outside_store_hours_but_keeps_closed_date_blocked(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 10:00', 'Europe/Lisbon'));
        [$user, $product, $store] = $this->makeOrderContext();
        $user->update(['role' => 'atendimento']);

        Sanctum::actingAs($user);

        $payload = [
            'store_id' => $store->id,
            'allow_schedule_exception' => true,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $this->postJson('/api/v1/orders', [
            ...$payload,
            'scheduled_at' => '2026-03-17 21:30',
        ])->assertOk();

        $this->postJson('/api/v1/orders', [
            ...$payload,
            'scheduled_at' => '2026-03-18 15:00',
        ])->assertStatus(422)->assertJsonValidationErrors(['scheduled_at']);
    }

    public function test_staff_can_schedule_at_eleven_before_store_opening_with_exception(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 10:00', 'Europe/Lisbon'));
        [$user, $product, $store] = $this->makeOrderContext();
        $user->update(['role' => 'atendimento']);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'scheduled_at' => '2026-03-17 11:00',
            'allow_schedule_exception' => true,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertOk()->assertJsonPath('data.slot', 'manha');
    }

    public function test_order_at_eleven_before_store_opening_is_rejected_without_exception(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 10:00', 'Europe/Lisbon'));
        [$user, $product, $store] = $this->makeOrderContext();
        $user->update(['role' => 'atendimento']);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'scheduled_at' => '2026-03-17 11:00',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_at'])
            ->assertJsonPath('errors.scheduled_at.0', 'O horário escolhido está fora do funcionamento dessa loja.');
    }

    public function test_schedule_exception_does_not_bypass_full_slot_capacity(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 10:00', 'Europe/Lisbon'));
        [$user, $product, $store] = $this->makeOrderContext();
        $user->update(['role' => 'atendimento']);
        Setting::create([
            'key' => 'ORDER_SLOT_BASE_CAPACITY',
            'value' => json_encode(['manha' => 1, 'tarde' => 10, 'noite' => 10], JSON_THROW_ON_ERROR),
            'type' => 'json',
            'editable' => true,
        ]);
        Cache::forget('setting_ORDER_SLOT_BASE_CAPACITY');
        Order::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'status' => 'accepted',
            'payment_status' => 'pending',
            'slot' => 'manha',
            'scheduled_at' => Carbon::parse('2026-03-17 10:00', 'Europe/Lisbon')->utc(),
            'total' => 2.5,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'scheduled_at' => '2026-03-17 11:00',
            'allow_schedule_exception' => true,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertStatus(422)
            ->assertJsonPath('errors.scheduled_at.0', 'SLOT_CAPACITY_FULL');

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_schedule_exception_does_not_bypass_operationally_blocked_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 10:00', 'Europe/Lisbon'));
        [$user, $product, $store] = $this->makeOrderContext();
        $user->update(['role' => 'atendimento']);
        Setting::create([
            'key' => 'ORDER_SLOT_OPERATIONAL_RULES',
            'value' => json_encode([
                'lead_times' => ['manha' => 120, 'tarde' => 60, 'noite' => 60],
                'blocked_dates' => [
                    ['date' => '2026-03-17', 'slots' => ['manha']],
                ],
            ], JSON_THROW_ON_ERROR),
            'type' => 'json',
            'editable' => true,
        ]);
        Cache::forget('setting_ORDER_SLOT_OPERATIONAL_RULES');

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'scheduled_at' => '2026-03-17 11:00',
            'allow_schedule_exception' => true,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertStatus(422)
            ->assertJsonPath('errors.scheduled_at.0', 'SLOT_DATE_BLOCKED');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_accepts_retroactive_schedule_exception_for_staff(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-17 19:00', 'Europe/Lisbon'));
        [$user, $product, $store] = $this->makeOrderContext();
        $user->update(['role' => 'atendimento']);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'scheduled_at' => '2026-03-17 16:30',
            'slot' => 'tarde',
            'allow_schedule_exception' => true,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.slot', 'tarde');
    }

    public function test_order_assigns_hourly_slot_from_scheduled_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 10:00', 'Europe/Lisbon'));
        [$user, $product, $store] = $this->makeOrderContext();
        Setting::create([
            'key' => 'ORDER_SLOT_MODE',
            'value' => 'horario',
            'type' => 'string',
            'editable' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'scheduled_at' => '2026-03-17 14:37',
            'slot' => 'manha',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.slot', '14h');
    }

    public function test_hourly_slot_availability_uses_hour_keys(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 10:00', 'Europe/Lisbon'));
        [$user, , $store] = $this->makeOrderContext();
        Setting::create([
            'key' => 'ORDER_SLOT_MODE',
            'value' => 'horario',
            'type' => 'string',
            'editable' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/orders/availability/slots?store_id={$store->id}&date=2026-03-17");

        $response->assertOk();
        $response->assertJsonPath('data.slots.0.slot', '08h');
        $response->assertJsonPath('data.slots.6.slot', '14h');
        $response->assertJsonPath('data.slots.14.slot', '22h');
    }

    protected function makeOrderContext(): array
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Salgados', 'active' => true]);
        $product = Product::create([
            'name' => 'Coxinha',
            'description' => 'Teste',
            'price' => 2.5,
            'category_id' => $category->id,
            'active' => true,
        ]);

        return [$user, $product, $this->makeStore('Loja Centro')];
    }

    protected function makeStore(string $name): Store
    {
        return Store::create([
            'name' => $name,
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
                'monday' => ['is_open' => false, 'start_time' => null, 'end_time' => null],
                'tuesday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '20:00'],
                'wednesday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '20:00'],
                'thursday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '20:00'],
                'friday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '20:00'],
                'saturday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '20:00'],
                'sunday' => ['is_open' => true, 'start_time' => '14:00', 'end_time' => '20:00'],
            ],
            'pickup_date_exceptions' => [
                ['date' => '2026-03-18', 'is_open' => false, 'start_time' => null, 'end_time' => null],
                ['date' => '2026-03-19', 'is_open' => true, 'start_time' => '15:00', 'end_time' => '17:00'],
            ],
        ]);
    }
}
