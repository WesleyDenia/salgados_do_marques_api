<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\UrbanCampaignCouponClaim;
use App\Models\UrbanCampaignCouponConfig;
use App\Models\User;
use App\Services\Notifications\WhatsAppMessageFormatter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class WhatsAppMessageFormatterTest extends TestCase
{
    public function test_it_formats_otp_message(): void
    {
        $formatter = new WhatsAppMessageFormatter;

        $this->assertSame(
            'Seu código de verificação Coinxinhas - Salgados do Marquês é 123456. Ele expira em 15 minutos.',
            $formatter->otp('123456')
        );
    }

    public function test_it_formats_urban_campaign_coupon_message(): void
    {
        $formatter = new WhatsAppMessageFormatter;
        $claim = new UrbanCampaignCouponClaim([
            'code' => 'VD-URBANA-10',
            'discount_type' => 'percent',
            'amount' => 10,
            'expires_at' => Carbon::create(2026, 9, 15, 12, 0, 0, 'UTC'),
        ]);
        $claim->setRelation('config', new UrbanCampaignCouponConfig([
            'description' => 'Parabens, encontraste o segredo do Kibe. Aqui esta o teu premio.',
        ]));

        $this->assertSame(
            "Parabens, encontraste o segredo do Kibe. Aqui esta o teu premio.\n"
                ."Codigo: VD-URBANA-10\n"
                ."Desconto: 10%\n"
                ."Valido ate: 15/09/2026 12:00\n"
                ."Apresente este codigo no momento da compra.\n"
                ."Salgados do Marques\n"
                ."\n"
                .'Dizem que quem consegue achar os 5 desafios ganha uma surpresa!! 👀',
            $formatter->urbanCampaignCoupon($claim, 'Europe/Lisbon')
        );
    }

    public function test_it_formats_order_message(): void
    {
        $formatter = new WhatsAppMessageFormatter;

        $user = new User;
        $user->name = 'Joao Silva';
        $user->phone = '351911928481';

        $order = new Order;
        $order->scheduled_at = Carbon::create(2026, 1, 15, 12, 30, 0, 'UTC');
        $order->setRelation('user', $user);

        $items = new Collection;

        $firstItem = new OrderItem;
        $firstItem->quantity = 3;
        $firstItem->name_snapshot = 'Coxinha';
        $firstItem->options = ['flavors' => [1, 2]];
        $items->push($firstItem);

        $secondItem = new OrderItem;
        $secondItem->quantity = 1;
        $secondItem->name_snapshot = 'Pastel';
        $secondItem->options = null;
        $items->push($secondItem);

        $order->setRelation('items', $items);

        $this->assertSame(
            "Nome: Joao Silva\n"
                ."Tel: 351911928481\n"
                ."Data/Hora: 15/01/2026 12:30\n"
                ."QUINTA-FEIRA\n\n"
                ."Pedido:\n"
                ."3x Coxinha\n"
                ." - Pack Mix\n"
                ." - Pack Doce\n"
                ."1x Pastel\n\n"
                ."Valor total: €16,50\n"
                ."Pagamento: Pago\n\n"
                .'RETIRADA NA LOJA "Loja Centro"',
            $formatter->orderPlacedSnapshot(
                'Joao Silva',
                '351911928481',
                Carbon::create(2026, 1, 15, 12, 30, 0, 'UTC'),
                $order->items,
                [
                    1 => 'Pack Mix',
                    2 => 'Pack Doce',
                ],
                16.50,
                'Loja Centro',
                'paid',
            )
        );
    }

    public function test_it_formats_order_message_from_array_items(): void
    {
        $formatter = new WhatsAppMessageFormatter;

        $scheduledAt = Carbon::create(2026, 1, 15, 12, 30, 0, 'UTC');

        $this->assertSame(
            "Nome: Joao Silva\n"
                ."Tel: 351911928481\n"
                ."Data/Hora: 15/01/2026 12:30\n"
                ."QUINTA-FEIRA\n\n"
                ."Pedido:\n"
                ."3x Coxinha\n"
                ." - Pack Mix\n"
                ." - Pack Doce\n"
                ."1x Pastel\n\n"
                ."Valor: €16,50\n"
                ."Valor Pago: €6,50\n"
                ."Falta Pagar: €10,00\n"
                ."Pagamento: Pagamento Parcial\n\n"
                .'RETIRADA NA LOJA "Loja Centro"',
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
                ],
                16.50,
                'Loja Centro',
                'partial',
                6.50,
            )
        );
    }

    public function test_it_includes_the_category_snapshot_in_the_order_item_label(): void
    {
        $formatter = new WhatsAppMessageFormatter;

        $message = $formatter->orderPlacedSnapshot(
            'Joana Silva',
            '351911928482',
            null,
            [[
                'quantity' => 1,
                'name_snapshot' => 'Pack 50 unidades',
                'options' => [
                    'category_name_snapshot' => 'Minis salgados Congelados',
                    'flavors' => [1],
                ],
            ]],
            [1 => 'Frango'],
            20,
            'Loja Norte',
        );

        $this->assertStringContainsString(
            "1x Minis salgados Congelados — Pack 50 unidades\n - Frango",
            $message,
        );
    }

    public function test_it_marks_flavors_from_a_frozen_pack(): void
    {
        $formatter = new WhatsAppMessageFormatter;

        $message = $formatter->orderPlacedSnapshot(
            'Joana Silva',
            '351911928482',
            null,
            [[
                'quantity' => 1,
                'name_snapshot' => 'Pack 50 unidades',
                'options' => ['is_frozen' => true, 'flavors' => [1, 2]],
            ]],
            [1 => 'Coxinha de Frango', 2 => 'Bolinha de Queijo'],
            20,
            'Loja Norte',
        );

        $this->assertStringContainsString(' - Coxinha de Frango - CONGELADO', $message);
        $this->assertStringContainsString(' - Bolinha de Queijo - CONGELADO', $message);
    }

    public function test_it_formats_an_indefinite_pickup_order_message(): void
    {
        $formatter = new WhatsAppMessageFormatter;

        $this->assertSame(
            "Nome: Joana Silva\n"
                ."Tel: 351911928482\n"
                ."Data/Hora: indefinido\n\n"
                ."Pedido:\n"
                ."2x Rissol\n\n"
                ."Valor total: €5,00\n"
                ."Pagamento: À Pagar\n\n"
                .'RETIRADA NA LOJA "Loja Norte"',
            $formatter->orderPlacedSnapshot(
                'Joana Silva',
                '351911928482',
                null,
                [[
                    'quantity' => 2,
                    'name_snapshot' => 'Rissol',
                    'options' => [],
                ]],
                [],
                5,
                'Loja Norte',
            )
        );
    }

    public function test_it_keeps_the_pickup_date_when_only_the_pickup_time_is_indefinite(): void
    {
        $formatter = new WhatsAppMessageFormatter;

        $message = $formatter->orderPlacedSnapshot(
            'Joana Silva',
            '351911928482',
            null,
            [],
            [],
            5,
            'Loja Norte',
            pickupDate: '2026-12-20',
            pickupTime: null,
        );

        $this->assertStringContainsString(
            "Data: 20/12/2026\nHora: indefinido\nDOMINGO",
            $message,
        );
    }

    public function test_it_calculates_partial_payment_balances_in_cents(): void
    {
        $formatter = new WhatsAppMessageFormatter;

        $message = $formatter->orderPlacedSnapshot(
            'Wesley Teste',
            '911928481',
            Carbon::create(2026, 11, 18, 15, 0, 0, 'UTC'),
            [[
                'quantity' => 1,
                'name_snapshot' => 'Pack 100 Unidades',
                'options' => [],
            ]],
            [],
            60,
            'Loja Principal',
            'partial',
            30,
        );

        $this->assertStringContainsString("Data/Hora: 18/11/2026 15:00\nQUARTA-FEIRA", $message);
        $this->assertStringContainsString(
            "Valor: €60,00\nValor Pago: €30,00\nFalta Pagar: €30,00",
            $message,
        );
    }

    public function test_it_includes_the_pickup_store_address_and_google_maps_link(): void
    {
        $formatter = new WhatsAppMessageFormatter;

        $message = $formatter->orderPlacedSnapshot(
            'Wesley Teste',
            '911928481',
            null,
            [],
            [],
            60,
            'Loja Principal',
            'pending',
            0,
            'Rua Filarmónica Artística Pombalense, 17',
            39.9167,
            -8.6282,
        );

        $this->assertStringContainsString(
            "RETIRADA NA LOJA \"Loja Principal\"\nRua Filarmónica Artística Pombalense, 17",
            $message,
        );
        $this->assertStringContainsString(
            'Localização: https://www.google.com/maps/search/?api=1&query=39.9167000%2C-8.6282000',
            $message,
        );
    }
}
