<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->json('pickup_weekly_schedule')->nullable()->after('default_store');
            $table->json('pickup_date_exceptions')->nullable()->after('pickup_weekly_schedule');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['pickup_weekly_schedule', 'pickup_date_exceptions']);
        });
    }
};
