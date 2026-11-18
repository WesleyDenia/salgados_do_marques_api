<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillOrderCustomersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_write_and_commit_creates_one_customer_and_associates_the_order(): void
    {
        $order = $this->createLegacyOrder('+351 912 345 678');

        $this->artisan('orders:backfill-customers')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['phone_normalized' => '912345678']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => null]);

        $this->artisan('orders:backfill-customers', ['--commit' => true])
            ->assertExitCode(0);

        $customer = User::query()->where('phone_normalized', '912345678')->sole();

        $this->assertSame('Maria Legado', $customer->name);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $customer->id,
            'customer_contact' => '912345678',
        ]);
    }

    public function test_commit_aborts_without_writes_when_existing_customers_have_the_same_normalized_phone(): void
    {
        User::factory()->create([
            'role' => User::ROLE_CLIENTE,
            'phone' => '+351 912 345 678',
        ]);
        User::factory()->create([
            'role' => User::ROLE_CLIENTE,
            'phone' => '912345678',
        ]);
        $order = $this->createLegacyOrder('912 345 678');

        $this->artisan('orders:backfill-customers', ['--commit' => true])
            ->assertExitCode(1);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => null]);
        $this->assertDatabaseMissing('users', ['phone_normalized' => '912345678']);
    }

    private function createLegacyOrder(string $phone): Order
    {
        $store = Store::query()->create([
            'name' => 'Loja Legado',
            'address' => 'Rua do Teste, 1',
            'city' => 'Lisboa',
            'latitude' => 38.7169,
            'longitude' => -9.1399,
        ]);

        return Order::query()->create([
            'store_id' => $store->id,
            'customer_name' => 'Maria Legado',
            'customer_contact' => $phone,
            'status' => 'placed',
            'scheduled_at' => now(),
            'total' => 0,
        ]);
    }
}
