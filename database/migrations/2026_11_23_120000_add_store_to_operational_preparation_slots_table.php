<?php

use App\Models\OperationalPreparationSlot;
use App\Models\ProductPreparationSetting;
use App\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operational_preparation_slots', function (Blueprint $table): void {
            $table->foreignId('store_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });

        // Existing cubas were global. Copy each configuration to every store so
        // that a deployment does not suddenly make current orders unplannable.
        // The original rows are retained (for historic allocations) but disabled.
        DB::transaction(function (): void {
            $stores = Store::query()->orderBy('id')->get(['id']);

            if ($stores->isEmpty()) {
                return;
            }

            $legacySlots = OperationalPreparationSlot::query()
                ->whereNull('store_id')
                ->with('productSettings')
                ->get();

            foreach ($legacySlots as $legacySlot) {
                foreach ($stores as $store) {
                    $slot = OperationalPreparationSlot::query()->create([
                        'store_id' => $store->id,
                        'name' => $legacySlot->name,
                        'active' => $legacySlot->active,
                        'display_order' => $legacySlot->display_order,
                    ]);

                    foreach ($legacySlot->productSettings as $setting) {
                        ProductPreparationSetting::query()->create([
                            'operational_preparation_slot_id' => $slot->id,
                            'product_id' => $setting->product_id,
                            'batch_size' => $setting->batch_size,
                            'preparation_time_seconds' => $setting->preparation_time_seconds,
                        ]);
                    }
                }

                $legacySlot->update(['active' => false]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('operational_preparation_slots', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('store_id');
        });
    }
};
