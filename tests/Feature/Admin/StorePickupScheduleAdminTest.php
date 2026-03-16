<?php

namespace Tests\Feature\Admin;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePickupScheduleAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_pickup_schedule_and_date_exceptions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.stores.store'), $this->storePayload());

        $response->assertRedirect(route('admin.stores.index'));

        $store = Store::query()->where('name', 'Loja Agenda Admin')->firstOrFail();

        $this->assertSame('12:00', $store->pickup_weekly_schedule['tuesday']['start_time']);
        $this->assertSame('2026-12-24', $store->pickup_date_exceptions[0]['date']);
        $this->assertFalse($store->pickup_date_exceptions[0]['is_open']);
        $this->assertSame('2026-12-31', $store->pickup_date_exceptions[1]['date']);
    }

    public function test_admin_cannot_submit_duplicate_exception_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $payload = $this->storePayload();
        $payload['pickup_date_exceptions'][1]['date'] = '2026-12-24';

        $response = $this->from(route('admin.stores.create'))
            ->actingAs($admin)
            ->post(route('admin.stores.store'), $payload);

        $response->assertRedirect(route('admin.stores.create'));
        $response->assertSessionHasErrors(['pickup_date_exceptions.1.date']);
    }

    public function test_admin_can_update_existing_schedule(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::create([
            'name' => 'Loja Atualizar',
            'address' => 'Rua 1',
            'city' => 'Lisboa',
            'latitude' => 38.7169,
            'longitude' => -9.1399,
            'phone' => '123456789',
            'type' => 'principal',
            'is_active' => true,
            'accepts_orders' => true,
            'default_store' => false,
            'pickup_weekly_schedule' => [
                'monday' => ['is_open' => false, 'start_time' => null, 'end_time' => null],
                'tuesday' => ['is_open' => true, 'start_time' => '10:00', 'end_time' => '18:00'],
                'wednesday' => ['is_open' => true, 'start_time' => '10:00', 'end_time' => '18:00'],
                'thursday' => ['is_open' => true, 'start_time' => '10:00', 'end_time' => '18:00'],
                'friday' => ['is_open' => true, 'start_time' => '10:00', 'end_time' => '18:00'],
                'saturday' => ['is_open' => true, 'start_time' => '10:00', 'end_time' => '18:00'],
                'sunday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '18:00'],
            ],
            'pickup_date_exceptions' => [],
        ]);

        $payload = $this->storePayload([
            'name' => $store->name,
            'pickup_weekly_schedule' => [
                'monday' => ['is_open' => 0, 'start_time' => '', 'end_time' => ''],
                'tuesday' => ['is_open' => 1, 'start_time' => '11:00', 'end_time' => '19:00'],
                'wednesday' => ['is_open' => 1, 'start_time' => '11:00', 'end_time' => '19:00'],
                'thursday' => ['is_open' => 1, 'start_time' => '11:00', 'end_time' => '19:00'],
                'friday' => ['is_open' => 1, 'start_time' => '11:00', 'end_time' => '19:00'],
                'saturday' => ['is_open' => 1, 'start_time' => '11:00', 'end_time' => '19:00'],
                'sunday' => ['is_open' => 1, 'start_time' => '14:00', 'end_time' => '20:00'],
            ],
        ]);

        $response = $this->actingAs($admin)->put(route('admin.stores.update', $store), $payload);

        $response->assertRedirect(route('admin.stores.index'));
        $store->refresh();

        $this->assertSame('11:00', $store->pickup_weekly_schedule['tuesday']['start_time']);
        $this->assertSame('20:00', $store->pickup_weekly_schedule['sunday']['end_time']);
    }

    protected function storePayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'name' => 'Loja Agenda Admin',
            'address' => 'Rua 1',
            'city' => 'Lisboa',
            'latitude' => 38.7169,
            'longitude' => -9.1399,
            'phone' => '123456789',
            'type' => 'principal',
            'is_active' => '1',
            'accepts_orders' => '1',
            'default_store' => '1',
            'pickup_weekly_schedule' => [
                'monday' => ['is_open' => 0, 'start_time' => '', 'end_time' => ''],
                'tuesday' => ['is_open' => 1, 'start_time' => '12:00', 'end_time' => '20:00'],
                'wednesday' => ['is_open' => 1, 'start_time' => '12:00', 'end_time' => '20:00'],
                'thursday' => ['is_open' => 1, 'start_time' => '12:00', 'end_time' => '20:00'],
                'friday' => ['is_open' => 1, 'start_time' => '12:00', 'end_time' => '20:00'],
                'saturday' => ['is_open' => 1, 'start_time' => '12:00', 'end_time' => '20:00'],
                'sunday' => ['is_open' => 1, 'start_time' => '14:00', 'end_time' => '20:00'],
            ],
            'pickup_date_exceptions' => [
                ['date' => '2026-12-24', 'is_open' => 0, 'start_time' => '', 'end_time' => ''],
                ['date' => '2026-12-31', 'is_open' => 1, 'start_time' => '15:00', 'end_time' => '18:00'],
            ],
        ], $overrides);
    }
}
