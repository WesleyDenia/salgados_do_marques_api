<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('loyalty_rewards', function (Blueprint $table) {
            if (!Schema::hasColumn('loyalty_rewards', 'value')) {
                $table->decimal('value', 10, 2)->default(0)->after('threshold');
            }
        });

        Schema::table('user_coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('user_coupons', 'type')) {
                $table->string('type')->default('regular')->after('coupon_id');
            }

            if (!Schema::hasColumn('user_coupons', 'loyalty_reward_id')) {
                $table->foreignId('loyalty_reward_id')
                    ->nullable()
                    ->after('type')
                    ->constrained()
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_coupons', function (Blueprint $table) {
            if (Schema::hasColumn('user_coupons', 'loyalty_reward_id')) {
                $table->dropConstrainedForeignId('loyalty_reward_id');
            }

            if (Schema::hasColumn('user_coupons', 'type')) {
                $table->dropColumn('type');
            }
        });

        Schema::table('loyalty_rewards', function (Blueprint $table) {
            if (Schema::hasColumn('loyalty_rewards', 'value')) {
                $table->dropColumn('value');
            }
        });
    }
};
