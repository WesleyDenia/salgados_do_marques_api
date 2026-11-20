<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Jobs\SyncCustomerToErpJob;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerAdminIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_list_customers_by_name_with_order_counts_and_search_by_phone(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_ATENDIMENTO]);
        $ana = User::factory()->create([
            'name' => 'Ana Costa',
            'phone' => '+351 912 345 678',
            'phone_normalized' => '912345678',
            'role' => User::ROLE_CLIENTE,
        ]);
        $maria = User::factory()->create([
            'name' => 'Maria Silva',
            'role' => User::ROLE_CLIENTE,
        ]);
        User::factory()->create(['name' => 'Administrador', 'role' => User::ROLE_ADMIN]);
        $store = Store::create([
            'name' => 'Loja de teste',
            'address' => 'Rua de teste',
            'city' => 'Lisboa',
            'latitude' => 38.7223,
            'longitude' => -9.1393,
            'phone' => '912345678',
            'type' => 'principal',
            'is_active' => true,
            'accepts_orders' => true,
            'default_store' => true,
        ]);

        foreach ([$ana, $ana, $maria] as $customer) {
            Order::create([
                'user_id' => $customer->id,
                'store_id' => $store->id,
                'status' => 'placed',
                'scheduled_at' => Carbon::now(),
                'total' => 10,
            ]);
        }

        Sanctum::actingAs($staff);

        $this->getJson('/api/v1/admin/customers')
            ->assertOk()
            ->assertJsonPath('data.0.id', $ana->id)
            ->assertJsonPath('data.0.orders_count', 2)
            ->assertJsonPath('data.1.id', $maria->id)
            ->assertJsonPath('data.1.orders_count', 1);

        $this->getJson('/api/v1/admin/customers?search=912%20345')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ana->id);
    }

    public function test_staff_can_view_and_update_a_customer_with_orders_sorted_by_pickup_date_descending(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_ATENDIMENTO]);
        $customer = User::factory()->create([
            'role' => User::ROLE_CLIENTE,
            'name' => 'Cliente para editar',
            'email' => 'cliente@example.test',
            'phone' => '912345678',
            'phone_normalized' => '912345678',
            'nif' => '123456789',
            'birth_date' => '1990-02-03',
        ]);
        $store = Store::create([
            'name' => 'Loja de teste',
            'address' => 'Rua de teste',
            'city' => 'Lisboa',
            'latitude' => 38.7223,
            'longitude' => -9.1393,
            'phone' => '912345678',
            'type' => 'principal',
            'is_active' => true,
            'accepts_orders' => true,
            'default_store' => true,
        ]);
        $olderOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'pickup_date' => '2026-01-10',
            'pickup_time' => '10:00',
            'total' => 10,
        ]);
        $newerOrder = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => 'ready',
            'pickup_date' => '2026-02-10',
            'pickup_time' => '14:00',
            'total' => 20,
        ]);

        Sanctum::actingAs($staff);

        $this->getJson("/api/v1/admin/customers/{$customer->id}")
            ->assertOk()
            ->assertJsonPath('data.email', 'cliente@example.test')
            ->assertJsonPath('data.orders_count', 2)
            ->assertJsonPath('data.orders.0.id', $newerOrder->id)
            ->assertJsonPath('data.orders.1.id', $olderOrder->id);

        Queue::fake();

        $this->putJson("/api/v1/admin/customers/{$customer->id}", [
            'name' => 'Cliente atualizado',
            'email' => 'atualizado@example.test',
            'phone' => '+351 913 222 111',
            'nif' => '987654321',
            'birth_date' => '1992-06-04',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Cliente atualizado')
            ->assertJsonPath('data.email', 'atualizado@example.test')
            ->assertJsonPath('data.phone', '913222111')
            ->assertJsonPath('data.nif', '987654321')
            ->assertJsonPath('data.birth_date', '1992-06-04');

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'name' => 'Cliente atualizado',
            'email' => 'atualizado@example.test',
            'phone_normalized' => '913222111',
        ]);
        Queue::assertPushed(SyncCustomerToErpJob::class);
    }
}
