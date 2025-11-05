<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('coupons', 'is_loyalty_reward')) {
                $table->boolean('is_loyalty_reward')
                    ->default(false)
                    ->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (Schema::hasColumn('coupons', 'is_loyalty_reward')) {
                $table->dropColumn('is_loyalty_reward');
            }
        });
    }
};
