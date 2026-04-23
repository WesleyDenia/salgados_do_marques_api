<?php

namespace App\Jobs;

use App\Models\UserCoupon;
use App\Models\VendusDiscountCardImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class ProcessVendusDiscountCardImportJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $uniqueFor = 300;

    public function __construct(public int $importId)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->importId;
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        $import = VendusDiscountCardImport::find($this->importId);

        if (!$import || in_array($import->sync_status, [
            VendusDiscountCardImport::STATUS_PROCESSED,
            VendusDiscountCardImport::STATUS_MANUALLY_CLOSED,
        ], true)) {
            return;
        }

        $import->forceFill([
            'sync_status' => VendusDiscountCardImport::STATUS_PROCESSING,
            'sync_attempts' => $import->sync_attempts + 1,
            'sync_error' => null,
        ])->save();

        try {
            $this->process($import);
        } catch (Throwable $e) {
            $import->forceFill([
                'sync_status' => VendusDiscountCardImport::STATUS_FAILED,
                'sync_error' => $e->getMessage(),
            ])->save();

            throw $e;
        }
    }

    protected function process(VendusDiscountCardImport $import): void
    {
        $userCoupon = UserCoupon::query()
            ->when($import->external_code, fn ($query) => $query->where('external_code', $import->external_code))
            ->when($import->external_code && $import->external_id, fn ($query) => $query->orWhere('external_id', $import->external_id))
            ->when(!$import->external_code && $import->external_id, fn ($query) => $query->where('external_id', $import->external_id))
            ->first();

        if (!$userCoupon) {
            throw new RuntimeException(sprintf(
                'Cupom Vendus não encontrado localmente. external_id=%s external_code=%s',
                $import->external_id ?: '—',
                $import->external_code ?: '—'
            ));
        }

        $userCoupon->update([
            'status' => 'done',
            'active' => false,
        ]);

        if ($userCoupon->coupon && ($userCoupon->coupon->is_loyalty_reward || $userCoupon->partner_campaign_id)) {
            $userCoupon->coupon->update(['active' => false]);
        }

        $import->forceFill([
            'sync_status' => VendusDiscountCardImport::STATUS_PROCESSED,
            'sync_error' => null,
            'user_coupon_id' => $userCoupon->id,
            'processed_at' => now(),
        ])->save();
    }
}
