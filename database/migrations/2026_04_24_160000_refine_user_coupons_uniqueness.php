<?php

use App\Models\UserCoupon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('user_coupons', 'origin_key')) {
                $table->string('origin_key')->nullable()->after('partner_campaign_id');
            }

            if (!Schema::hasColumn('user_coupons', 'erp_sync_error')) {
                $table->text('erp_sync_error')->nullable()->after('status');
            }

            if (!Schema::hasColumn('user_coupons', 'erp_synced_at')) {
                $table->timestamp('erp_synced_at')->nullable()->after('erp_sync_error');
            }

            if (!Schema::hasColumn('user_coupons', 'erp_sync_attempts')) {
                $table->unsignedInteger('erp_sync_attempts')->default(0)->after('erp_synced_at');
            }
        });

        DB::table('user_coupons')
            ->select(['id', 'type', 'loyalty_reward_id', 'partner_campaign_id'])
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $originKey = match ($row->type) {
                        'loyalty' => $row->loyalty_reward_id ? 'loyalty_reward:' . $row->loyalty_reward_id : null,
                        'partner' => $row->partner_campaign_id ? 'partner_campaign:' . $row->partner_campaign_id : null,
                        default => null,
                    };

                    if ($originKey === null) {
                        continue;
                    }

                    DB::table('user_coupons')
                        ->where('id', $row->id)
                        ->update(['origin_key' => $originKey]);
                }
            });

        DB::table('user_coupons')
            ->where('type', 'loyalty')
            ->whereNull('status')
            ->update(['status' => UserCoupon::STATUS_PENDING_ERP]);

        Schema::table('user_coupons', function (Blueprint $table) {
            $table->unique(['user_id', 'origin_key'], 'user_coupon_origin_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('user_coupons', function (Blueprint $table) {
            $table->dropUnique('user_coupon_origin_key_unique');

            foreach (['erp_sync_attempts', 'erp_synced_at', 'erp_sync_error', 'origin_key'] as $column) {
                if (Schema::hasColumn('user_coupons', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
