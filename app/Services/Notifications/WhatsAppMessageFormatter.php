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

    public function orderPlacedSnapshot(string $name, string $phone, Carbon $scheduledAt, iterable $items): string
    {
        $lines = collect($items)->map(function ($item): string {
            $line = sprintf('%dx %s', (int) $item->quantity, (string) $item->name_snapshot);

            if (filled($item->options['flavors'] ?? null)) {
                $line .= sprintf(' (sabores: %s)', implode(', ', array_map('strval', $item->options['flavors'])));
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
