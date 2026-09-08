<?php

namespace App\Services\Notifications;

use App\Models\Order;
use App\Models\UrbanCampaignCouponClaim;
use Carbon\Carbon;

class WhatsAppMessageFormatter
{
    public function orderPlaced(Order $order, string $timezone): string
    {
        $scheduledAt = Carbon::parse($order->scheduled_at, 'UTC')->timezone($timezone);

        return $this->orderPlacedSnapshot(
            (string) ($order->user?->name ?? '-'),
            (string) ($order->user?->phone ?? '-'),
            $scheduledAt,
            $order->items
        );
    }

    public function orderPlacedSnapshot(
        string $name,
        string $phone,
        Carbon $scheduledAt,
        iterable $items,
        array $flavorNamesById = []
    ): string {
        $lines = collect($items)->flatMap(function ($item) use ($flavorNamesById): array {
            $quantity = (int) data_get($item, 'quantity', 0);
            $nameSnapshot = (string) data_get($item, 'name_snapshot', '-');
            $options = data_get($item, 'options', []);
            $flavors = collect(data_get($options, 'flavors', []))
                ->map(fn ($flavorId) => (int) $flavorId)
                ->filter(fn (int $flavorId) => $flavorId > 0)
                ->values();

            $lines = [
                sprintf('%dx %s', $quantity, $nameSnapshot),
            ];

            if ($flavors->isNotEmpty()) {
                foreach ($flavors as $flavorId) {
                    $lines[] = ' - '.($flavorNamesById[$flavorId] ?? ('Sabor #'.$flavorId));
                }
            }

            return $lines;
        })->implode("\n");

        return implode("\n", [
            'Nome: '.$name,
            'Tel: '.$phone,
            'Data/Hora: '.$scheduledAt->format('d/m/Y H:i'),
            'Pedido:',
            $lines !== '' ? $lines : '-',
        ]);
    }

    public function otp(string $token): string
    {
        return sprintf(
            'Seu código de verificação Coinxinhas - Salgados do Marquês é %s. Ele expira em 15 minutos.',
            $token
        );
    }

    public function urbanCampaignCoupon(UrbanCampaignCouponClaim $claim, string $timezone): string
    {
        $expiresAt = $claim->expires_at
            ? $claim->expires_at->copy()->timezone($timezone)->format('d/m/Y H:i')
            : '-';

        return implode("\n", [
            'O teu cupom da Campanha Urbana foi gerado.',
            'Codigo: '.($claim->code ?: '-'),
            'Desconto: '.$this->formatDiscount((string) $claim->discount_type, (float) $claim->amount),
            'Valido ate: '.$expiresAt,
            'Apresente este codigo no momento da compra.',
            'Salgados do Marques',
        ]);
    }

    protected function formatDiscount(string $type, float $amount): string
    {
        if ($type === 'percent') {
            return rtrim(rtrim(number_format($amount, 2, ',', '.'), '0'), ',').'%';
        }

        return '€'.number_format($amount, 2, ',', '.');
    }
}
