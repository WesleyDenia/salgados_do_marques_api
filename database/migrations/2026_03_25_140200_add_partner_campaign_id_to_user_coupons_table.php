<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('user_coupons', 'partner_campaign_id')) {
                $table->foreignId('partner_campaign_id')
                    ->nullable()
                    ->after('loyalty_reward_id')
                    ->constrained('partner_campaigns')
                    ->nullOnDelete();

                $table->unique(['user_id', 'partner_campaign_id'], 'user_partner_campaign_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_coupons', function (Blueprint $table) {
            if (Schema::hasColumn('user_coupons', 'partner_campaign_id')) {
                $table->dropUnique('user_partner_campaign_unique');
                $table->dropConstrainedForeignId('partner_campaign_id');
            }
        });
    }
};
