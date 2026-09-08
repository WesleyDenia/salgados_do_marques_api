<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('urban_campaign_coupon_configs', function (Blueprint $table): void {
            $table->unsignedSmallInteger('duration_days')->default(7)->after('description');
            $table->dropIndex('urban_campaign_coupon_configs_starts_at_ends_at_index');
            $table->dropColumn(['starts_at', 'ends_at']);
        });

        Schema::table('urban_campaign_coupon_claims', function (Blueprint $table): void {
            $table->timestamp('generated_at')->nullable()->after('amount');
            $table->timestamp('expires_at')->nullable()->after('generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('urban_campaign_coupon_claims', function (Blueprint $table): void {
            $table->dropColumn(['generated_at', 'expires_at']);
        });

        Schema::table('urban_campaign_coupon_configs', function (Blueprint $table): void {
            $table->timestamp('starts_at')->nullable()->after('description');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
            $table->dropColumn('duration_days');

            $table->index(['starts_at', 'ends_at']);
        });
    }
};
