<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('user_coupons', 'redeem_applied_at')) {
                $table->timestamp('redeem_applied_at')->nullable()->after('erp_sync_attempts');
            }

            if (!Schema::hasColumn('user_coupons', 'redeem_transaction_id')) {
                $table->foreignId('redeem_transaction_id')
                    ->nullable()
                    ->after('redeem_applied_at')
                    ->constrained('loyalty_transactions')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_coupons', function (Blueprint $table) {
            if (Schema::hasColumn('user_coupons', 'redeem_transaction_id')) {
                $table->dropConstrainedForeignId('redeem_transaction_id');
            }

            if (Schema::hasColumn('user_coupons', 'redeem_applied_at')) {
                $table->dropColumn('redeem_applied_at');
            }
        });
    }
};
