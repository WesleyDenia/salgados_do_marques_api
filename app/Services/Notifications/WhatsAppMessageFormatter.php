<?php

namespace App\Services\Notifications;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Arr;

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

    public function orderPlacedSnapshot(string $name, string $phone, Carbon $scheduledAt, iterable $items): string
    {
        $lines = collect($items)->map(function ($item): string {
            $quantity = (int) data_get($item, 'quantity', 0);
            $nameSnapshot = (string) data_get($item, 'name_snapshot', '-');
            $options = data_get($item, 'options', []);
            $flavors = Arr::get($options, 'flavors', []);

            $line = sprintf('%dx %s', $quantity, $nameSnapshot);

            if (filled($flavors)) {
                $line .= sprintf(' (sabores: %s)', implode(', ', array_map('strval', (array) $flavors)));
            }

            return $line;
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
