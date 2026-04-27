<?php

namespace Tests\Unit;

use App\Contracts\Notifications\WhatsAppClient;
use App\Jobs\SendOrderPlacedWhatsAppJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\User;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendOrderPlacedWhatsAppJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_the_expected_message_for_a_new_order(): void
    {
        $user = User::create([
            'name' => 'Joao Silva',
            'email' => 'joao@example.com',
            'password' => bcrypt('password'),
            'phone' => '351911928481',
            'role' => 'cliente',
            'active' => true,
        ]);

        $store = Store::create([
            'name' => 'Loja Centro',
            'address' => 'Rua A, 1',
            'city' => 'Lisboa',
            'latitude' => 38.7223,
            'longitude' => -9.1393,
            'phone' => '210000000',
            'type' => 'principal',
            'is_active' => true,
            'accepts_orders' => true,
            'default_store' => true,
            'pickup_weekly_schedule' => null,
            'pickup_date_exceptions' => null,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'status' => 'placed',
            'scheduled_at' => Carbon::create(2026, 1, 15, 12, 30, 0, 'UTC'),
            'total' => 12.5,
            'notes' => null,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'variant_id' => null,
            'name_snapshot' => 'Coxinha',
            'price_snapshot' => 2.50,
            'quantity' => 3,
            'options' => ['flavors' => [1, 2]],
            'total' => 7.50,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'variant_id' => null,
            'name_snapshot' => 'Pastel',
            'price_snapshot' => 5.00,
            'quantity' => 1,
            'options' => null,
            'total' => 5.00,
        ]);

        $whatsAppClient = Mockery::mock(WhatsAppClient::class);
        $whatsAppClient->shouldReceive('sendMessage')
            ->once()
            ->with(
                '351911928481',
                "Nome: Joao Silva\n"
                    . "Tel: 351911928481\n"
                    . "Data/Hora: 15/01/2026 12:30\n"
                    . "Pedido:\n"
                    . "3x Coxinha (sabores: 1, 2)\n"
                    . "1x Pastel"
            )
            ->andReturn(true);

        $settings = Mockery::mock(SettingService::class);
        $settings->shouldReceive('get')
            ->with('order_timezone', Mockery::any())
            ->andReturn('UTC');
        $settings->shouldReceive('get')
            ->with('ORDER_TIMEZONE', Mockery::any())
            ->andReturn('UTC');

        $job = new SendOrderPlacedWhatsAppJob($order->id);
        $this->app->instance(WhatsAppClient::class, $whatsAppClient);
        $this->app->instance(SettingService::class, $settings);

        $this->app->call([$job, 'handle']);
    }
}
