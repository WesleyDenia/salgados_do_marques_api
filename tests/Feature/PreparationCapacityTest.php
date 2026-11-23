<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\OperationalPreparationSlot;
use App\Models\OrderPreparationAllocation;
use App\Models\Product;
use App\Models\ProductPreparationSetting;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PreparationCapacityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_configure_preparation_slots_and_product_times(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct('Coxinha');

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson('/api/v1/admin/settings/operational/preparation-capacity', [
                'slots' => [
                    ['name' => 'Cuba 1', 'active' => true, 'display_order' => 0],
                    ['name' => 'Cuba 2', 'active' => true, 'display_order' => 1],
                ],
                'settings' => [
                    [
                        'slot_index' => 0,
                        'product_id' => $product->id,
                        'batch_size' => 25,
                        'preparation_time_seconds' => 220,
                    ],
                    [
                        'slot_index' => 1,
                        'product_id' => $product->id,
                        'batch_size' => 25,
                        'preparation_time_seconds' => 220,
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonCount(2, 'data.slots')
            ->assertJsonPath('data.settings.0.product_id', $product->id)
            ->assertJsonPath('data.settings.0.preparation_time_seconds', 220);

        $this->assertDatabaseCount('operational_preparation_slots', 2);
        $this->assertDatabaseCount('product_preparation_settings', 2);
    }

    public function test_admin_can_edit_and_delete_a_store_preparation_slot_and_product_time(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = $this->makeStore();
        $product = $this->makeProduct('Coxinha editável');
        $slot = OperationalPreparationSlot::create([
            'store_id' => $store->id,
            'name' => 'Cuba antiga',
            'active' => true,
            'display_order' => 0,
        ]);
        $setting = ProductPreparationSetting::create([
            'operational_preparation_slot_id' => $slot->id,
            'product_id' => $product->id,
            'batch_size' => 25,
            'preparation_time_seconds' => 180,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/settings/operational/preparation-capacity/slots/{$slot->id}", [
                'store_id' => $store->id,
                'name' => 'Cuba salgados',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Cuba salgados');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/settings/operational/preparation-capacity/times/{$setting->id}", [
                'store_id' => $store->id,
                'operational_preparation_slot_id' => $slot->id,
                'product_id' => $product->id,
                'batch_size' => 40,
                'preparation_minutes' => 4.5,
            ])
            ->assertOk()
            ->assertJsonPath('data.batch_size', 40)
            ->assertJsonPath('data.preparation_time_seconds', 270);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/settings/operational/preparation-capacity/times/{$setting->id}", [
                'store_id' => $store->id,
            ])
            ->assertNoContent();
        $this->assertDatabaseMissing('product_preparation_settings', ['id' => $setting->id]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/settings/operational/preparation-capacity/slots/{$slot->id}", [
                'store_id' => $store->id,
            ])
            ->assertNoContent();
        $this->assertDatabaseMissing('operational_preparation_slots', ['id' => $slot->id]);
    }

    public function test_admin_can_save_the_heating_time_for_a_cuba(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = $this->makeStore();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/settings/operational/preparation-capacity/slots', [
                'store_id' => $store->id,
                'name' => 'Cuba com aquecimento',
                'heating_minutes' => 10,
            ])
            ->assertCreated()
            ->assertJsonPath('data.heating_time_seconds', 600);

        $this->assertDatabaseHas('operational_preparation_slots', [
            'store_id' => $store->id,
            'name' => 'Cuba com aquecimento',
            'heating_time_seconds' => 600,
        ]);
    }

    public function test_admin_cannot_change_a_slot_or_time_from_another_store(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $north = $this->makeStore();
        $south = $this->makeStore('Loja Sul');
        $product = $this->makeProduct('Coxinha por loja editável');
        $slot = OperationalPreparationSlot::create([
            'store_id' => $north->id,
            'name' => 'Cuba Norte',
            'active' => true,
            'display_order' => 0,
        ]);
        $setting = ProductPreparationSetting::create([
            'operational_preparation_slot_id' => $slot->id,
            'product_id' => $product->id,
            'batch_size' => 25,
            'preparation_time_seconds' => 180,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/settings/operational/preparation-capacity/slots/{$slot->id}", ['store_id' => $south->id])
            ->assertNotFound();
        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/settings/operational/preparation-capacity/times/{$setting->id}", ['store_id' => $south->id])
            ->assertNotFound();

        $this->assertDatabaseHas('operational_preparation_slots', ['id' => $slot->id]);
        $this->assertDatabaseHas('product_preparation_settings', ['id' => $setting->id]);
    }

    public function test_order_preparation_batches_are_distributed_to_the_least_loaded_cuba(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 10:00', 'Europe/Lisbon'));
        $this->useHourlySlots();
        $user = User::factory()->create(['role' => 'atendimento']);
        $store = $this->makeStore();
        $product = $this->makeProduct('Coxinha');
        [$slotOne, $slotTwo] = $this->makePreparationSlotsForProduct($product);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'scheduled_at' => '2026-03-17 14:37',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 75],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.slot', '14h')
            ->assertJsonCount(3, 'data.preparation_allocations');

        $loads = OrderPreparationAllocation::query()
            ->selectRaw('operational_preparation_slot_id, COUNT(*) as batches, SUM(preparation_time_seconds) as seconds')
            ->groupBy('operational_preparation_slot_id')
            ->pluck('seconds', 'operational_preparation_slot_id')
            ->all();

        $this->assertSame(440, (int) $loads[$slotOne->id]);
        $this->assertSame(220, (int) $loads[$slotTwo->id]);
    }

    public function test_existing_load_controls_the_next_preparation_slot_assignment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 10:00', 'Europe/Lisbon'));
        $this->useHourlySlots();
        $user = User::factory()->create(['role' => 'atendimento']);
        $store = $this->makeStore();
        $product = $this->makeProduct('Coxinha');
        [$slotOne, $slotTwo] = $this->makePreparationSlotsForProduct($product);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'scheduled_at' => '2026-03-17 14:10',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 25],
            ],
        ])->assertOk();

        $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'scheduled_at' => '2026-03-17 14:20',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 25],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('order_preparation_allocations', [
            'operational_preparation_slot_id' => $slotOne->id,
            'batch_index' => 1,
        ]);
        $this->assertDatabaseHas('order_preparation_allocations', [
            'operational_preparation_slot_id' => $slotTwo->id,
            'batch_index' => 1,
        ]);
    }

    public function test_order_uses_only_the_cubas_configured_for_its_store(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 10:00', 'Europe/Lisbon'));
        $this->useHourlySlots();
        $user = User::factory()->create(['role' => 'atendimento']);
        $northStore = $this->makeStore();
        $southStore = $this->makeStore('Loja Sul');
        $product = $this->makeProduct('Coxinha por loja');

        $northSlot = OperationalPreparationSlot::create([
            'store_id' => $northStore->id,
            'name' => 'Cuba Norte',
            'active' => true,
            'display_order' => 0,
        ]);
        $southSlot = OperationalPreparationSlot::create([
            'store_id' => $southStore->id,
            'name' => 'Cuba Sul',
            'active' => true,
            'display_order' => 0,
        ]);

        ProductPreparationSetting::create([
            'operational_preparation_slot_id' => $northSlot->id,
            'product_id' => $product->id,
            'batch_size' => 25,
            'preparation_time_seconds' => 120,
        ]);
        ProductPreparationSetting::create([
            'operational_preparation_slot_id' => $southSlot->id,
            'product_id' => $product->id,
            'batch_size' => 25,
            'preparation_time_seconds' => 300,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/orders', [
            'store_id' => $northStore->id,
            'scheduled_at' => '2026-03-17 14:00',
            'items' => [['product_id' => $product->id, 'quantity' => 25]],
        ])->assertOk()
            ->assertJsonPath('data.preparation_allocations.0.operational_preparation_slot_id', $northSlot->id)
            ->assertJsonPath('data.preparation_allocations.0.preparation_time_seconds', 120);

        $this->postJson('/api/v1/orders', [
            'store_id' => $southStore->id,
            'scheduled_at' => '2026-03-17 14:00',
            'items' => [['product_id' => $product->id, 'quantity' => 25]],
        ])->assertOk()
            ->assertJsonPath('data.preparation_allocations.0.operational_preparation_slot_id', $southSlot->id)
            ->assertJsonPath('data.preparation_allocations.0.preparation_time_seconds', 300);
    }

    public function test_preparation_capacity_blocks_hour_overflow_unless_staff_allows_exception(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 10:00', 'Europe/Lisbon'));
        $this->useHourlySlots();
        $user = User::factory()->create(['role' => 'atendimento']);
        $store = $this->makeStore();
        $product = $this->makeProduct('Coxinha');
        $this->makePreparationSlotsForProduct($product);

        Sanctum::actingAs($user);

        $payload = [
            'store_id' => $store->id,
            'scheduled_at' => '2026-03-17 14:00',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 850],
            ],
        ];

        $this->postJson('/api/v1/orders', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['allow_preparation_capacity_overflow']);

        $this->postJson('/api/v1/orders', [
            ...$payload,
            'allow_preparation_capacity_overflow' => true,
        ])->assertOk()
            ->assertJsonCount(34, 'data.preparation_allocations');
    }

    protected function useHourlySlots(): void
    {
        Setting::create([
            'key' => 'ORDER_SLOT_MODE',
            'value' => 'horario',
            'type' => 'string',
            'editable' => true,
        ]);
    }

    protected function makeProduct(string $name): Product
    {
        $category = Category::firstOrCreate(
            ['name' => 'Salgados'],
            ['description' => 'Categoria de teste', 'active' => true]
        );

        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'description' => 'Teste',
            'price' => 2.5,
            'active' => true,
        ]);
    }

    /**
     * @return array{OperationalPreparationSlot, OperationalPreparationSlot}
     */
    protected function makePreparationSlotsForProduct(Product $product): array
    {
        $slotOne = OperationalPreparationSlot::create([
            'name' => 'Cuba 1',
            'active' => true,
            'display_order' => 0,
        ]);
        $slotTwo = OperationalPreparationSlot::create([
            'name' => 'Cuba 2',
            'active' => true,
            'display_order' => 1,
        ]);

        foreach ([$slotOne, $slotTwo] as $slot) {
            ProductPreparationSetting::create([
                'operational_preparation_slot_id' => $slot->id,
                'product_id' => $product->id,
                'batch_size' => 25,
                'preparation_time_seconds' => 220,
            ]);
        }

        return [$slotOne, $slotTwo];
    }

    protected function makeStore(string $name = 'Loja Centro'): Store
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
            'pickup_date_exceptions' => [],
        ]);
    }
}
