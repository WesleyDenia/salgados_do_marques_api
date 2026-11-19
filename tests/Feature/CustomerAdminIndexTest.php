<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
