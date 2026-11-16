<?php

namespace App\Services\Notifications;

use App\Models\Order;
use App\Models\UrbanCampaignCouponClaim;
use Carbon\Carbon;
use Illuminate\Support\Str;

class WhatsAppMessageFormatter
{
    public function orderPlaced(Order $order, string $timezone): string
    {
        $scheduledAt = $order->scheduled_at
            ? Carbon::parse($order->scheduled_at, 'UTC')->timezone($timezone)
            : null;

        return $this->orderPlacedSnapshot(
            (string) ($order->user?->name ?? '-'),
            (string) ($order->user?->phone ?? '-'),
            $scheduledAt,
            $order->items,
            [],
            $order->total !== null ? (float) $order->total : null,
            $order->store?->name,
            $order->payment_status,
            $order->paid_amount !== null ? (float) $order->paid_amount : null,
            $order->store?->address,
            $order->store?->latitude,
            $order->store?->longitude,
        );
    }

    public function orderPlacedSnapshot(
        string $name,
        string $phone,
        ?Carbon $scheduledAt,
        iterable $items,
        array $flavorNamesById = [],
        ?float $total = null,
        ?string $pickupStoreName = null,
        ?string $paymentStatus = null,
        ?float $paidAmount = null,
        ?string $pickupStoreAddress = null,
        ?float $pickupStoreLatitude = null,
        ?float $pickupStoreLongitude = null,
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

        $totalInCents = $total !== null ? $this->toCents($total) : null;
        $paidAmountInCents = $paidAmount !== null ? $this->toCents($paidAmount) : null;
        $totalLabel = $totalInCents !== null ? $this->formatCurrencyFromCents($totalInCents) : '-';
        $paymentLines = $paymentStatus === 'partial'
            ? [
                'Valor: '.$totalLabel,
                'Valor Pago: '.($paidAmountInCents !== null
                    ? $this->formatCurrencyFromCents($paidAmountInCents)
                    : '-'),
                'Falta Pagar: '.($totalInCents !== null
                    ? $this->formatCurrencyFromCents(max(0, $totalInCents - ($paidAmountInCents ?? 0)))
                    : '-'),
            ]
            : ['Valor total: '.$totalLabel];

        $customerLines = [
            'Nome: '.$name,
            'Tel: '.$phone,
            'Data/Hora: '.($scheduledAt?->format('d/m/Y H:i') ?? 'indefinido'),
        ];

        if ($scheduledAt !== null) {
            $customerLines[] = Str::upper(
                $scheduledAt->copy()->locale('pt')->translatedFormat('l')
            );
        }

        return implode("\n\n", [
            implode("\n", $customerLines),
            implode("\n", [
                'Pedido:',
                $lines !== '' ? $lines : '-',
            ]),
            implode("\n", array_merge($paymentLines, [
                'Pagamento: '.$this->formatPaymentStatus($paymentStatus),
            ])),
            implode("\n", array_filter([
                'RETIRADA NA LOJA "'.(trim((string) $pickupStoreName) ?: 'Loja não definida').'"',
                trim((string) $pickupStoreAddress) ?: null,
                $this->pickupStoreMapsUrl($pickupStoreAddress, $pickupStoreLatitude, $pickupStoreLongitude),
            ])),
        ]);
    }

    protected function pickupStoreMapsUrl(
        ?string $address,
        ?float $latitude,
        ?float $longitude,
    ): ?string {
        if ($latitude !== null && $longitude !== null && is_finite($latitude) && is_finite($longitude)) {
            $query = sprintf('%.7F,%.7F', $latitude, $longitude);
        } else {
            $query = trim((string) $address);
        }

        return $query === ''
            ? null
            : 'Localização: https://www.google.com/maps/search/?api=1&query='.rawurlencode($query);
    }

    protected function formatCurrency(float $amount): string
    {
        return $this->formatCurrencyFromCents($this->toCents($amount));
    }

    protected function toCents(float $amount): int
    {
        return (int) round($amount * 100, 0, PHP_ROUND_HALF_UP);
    }

    protected function formatCurrencyFromCents(int $amountInCents): string
    {
        return '€'.number_format($amountInCents / 100, 2, ',', '.');
    }

    protected function formatPaymentStatus(?string $status): string
    {
        return match ($status) {
            'paid' => 'Pago',
            'partial' => 'Pagamento Parcial',
            default => 'À Pagar',
        };
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
        $description = trim((string) ($claim->config?->description ?? ''));
        $expiresAt = $claim->expires_at
            ? $claim->expires_at->copy()->timezone($timezone)->format('d/m/Y H:i')
            : '-';

        return implode("\n", [
            $description !== '' ? $description : 'O teu cupom da Campanha Urbana foi gerado.',
            'Codigo: '.($claim->code ?: '-'),
            'Desconto: '.$this->formatDiscount((string) $claim->discount_type, (float) $claim->amount),
            'Valido ate: '.$expiresAt,
            'Apresente este codigo no momento da compra.',
            'Salgados do Marques',
            '',
            'Dizem que quem consegue achar os 5 desafios ganha uma surpresa!! 👀',
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
