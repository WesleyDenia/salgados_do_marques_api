<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Notifications\WhatsAppMessageFormatter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class WhatsAppMessageFormatterTest extends TestCase
{
    public function test_it_formats_otp_message(): void
    {
        $formatter = new WhatsAppMessageFormatter();

        $this->assertSame(
            'Seu código de verificação Coinxinhas - Salgados do Marquês é 123456. Ele expira em 15 minutos.',
            $formatter->otp('123456')
        );
    }

    public function test_it_formats_order_message(): void
    {
        $formatter = new WhatsAppMessageFormatter();

        $user = new User();
        $user->name = 'Joao Silva';
        $user->phone = '351911928481';

        $order = new Order();
        $order->scheduled_at = Carbon::create(2026, 1, 15, 12, 30, 0, 'UTC');
        $order->setRelation('user', $user);

        $items = new Collection();

        $firstItem = new OrderItem();
        $firstItem->quantity = 3;
        $firstItem->name_snapshot = 'Coxinha';
        $firstItem->options = ['flavors' => [1, 2]];
        $items->push($firstItem);

        $secondItem = new OrderItem();
        $secondItem->quantity = 1;
        $secondItem->name_snapshot = 'Pastel';
        $secondItem->options = null;
        $items->push($secondItem);

        $order->setRelation('items', $items);

        $this->assertSame(
            "Nome: Joao Silva\n"
                . "Tel: 351911928481\n"
                . "Data/Hora: 15/01/2026 12:30\n"
                . "Pedido:\n"
                . "3x Coxinha\n"
                . " - Pack Mix\n"
                . " - Pack Doce\n"
                . "1x Pastel",
            $formatter->orderPlacedSnapshot(
                'Joao Silva',
                '351911928481',
                Carbon::create(2026, 1, 15, 12, 30, 0, 'UTC'),
                $order->items,
                [
                    1 => 'Pack Mix',
                    2 => 'Pack Doce',
                ]
            )
        );
    }

    public function test_it_formats_order_message_from_array_items(): void
    {
        $formatter = new WhatsAppMessageFormatter();

        $scheduledAt = Carbon::create(2026, 1, 15, 12, 30, 0, 'UTC');

        $this->assertSame(
            "Nome: Joao Silva\n"
                . "Tel: 351911928481\n"
                . "Data/Hora: 15/01/2026 12:30\n"
                . "Pedido:\n"
                . "3x Coxinha\n"
                . " - Pack Mix\n"
                . " - Pack Doce\n"
                . "1x Pastel",
            $formatter->orderPlacedSnapshot(
                'Joao Silva',
                '351911928481',
                $scheduledAt,
                [
                    [
                        'quantity' => 3,
                        'name_snapshot' => 'Coxinha',
                        'options' => ['flavors' => [1, 2]],
                    ],
                    [
                        'quantity' => 1,
                        'name_snapshot' => 'Pastel',
                        'options' => null,
                    ],
                ],
                [
                    1 => 'Pack Mix',
                    2 => 'Pack Doce',
                ]
            )
        );
    }
}
