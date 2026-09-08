<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('urban_campaign_coupon_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('coupon_type', 60)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('discount_type', 20)->default('percent');
            $table->decimal('amount', 10, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['coupon_type', 'active']);
            $table->index(['starts_at', 'ends_at']);
        });

        Schema::create('urban_campaign_coupon_claims', function (Blueprint $table): void {
            $table->id();
            $table->string('phone', 20);
            $table->string('coupon_type', 60);
            $table->foreignId('coupon_config_id')->nullable()->constrained('urban_campaign_coupon_configs')->nullOnDelete();
            $table->foreignId('qr_code_id')->nullable()->constrained('qr_codes')->nullOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->foreignId('response_id')->nullable()->constrained('question_responses')->nullOnDelete();
            $table->boolean('is_correct')->default(false);
            $table->string('discount_type', 20)->default('percent');
            $table->decimal('amount', 10, 2);
            $table->string('external_id')->nullable()->index();
            $table->string('code')->nullable()->index();
            $table->string('status', 30)->default('pending_erp')->index();
            $table->text('erp_sync_error')->nullable();
            $table->timestamp('erp_synced_at')->nullable();
            $table->unsignedInteger('erp_sync_attempts')->default(0);
            $table->timestamps();

            $table->unique(['phone', 'coupon_type'], 'urban_campaign_phone_coupon_type_unique');
            $table->index(['coupon_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('urban_campaign_coupon_claims');
        Schema::dropIfExists('urban_campaign_coupon_configs');
    }
};
