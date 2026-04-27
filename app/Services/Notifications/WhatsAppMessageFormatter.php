<?php

namespace App\Services\Notifications;

use App\Models\Order;
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
    ): string
    {
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
                    $lines[] = ' - ' . ($flavorNamesById[$flavorId] ?? ('Sabor #' . $flavorId));
                }
            }

            return $lines;
        })->implode("\n");

        return implode("\n", [
            'Nome: ' . $name,
            'Tel: ' . $phone,
            'Data/Hora: ' . $scheduledAt->format('d/m/Y H:i'),
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
}
