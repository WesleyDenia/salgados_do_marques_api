<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendus_discount_card_imports', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable()->index();
            $table->string('external_code')->nullable()->index();
            $table->string('vendus_status')->nullable()->index();
            $table->timestamp('date_used')->nullable();
            $table->string('sync_status')->default('downloaded')->index();
            $table->unsignedInteger('sync_attempts')->default(0);
            $table->text('sync_error')->nullable();
            $table->json('payload')->nullable();
            $table->foreignId('user_coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('manually_closed_at')->nullable();
            $table->foreignId('manually_closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('manual_note')->nullable();
            $table->timestamps();

            $table->unique('external_id');
            $table->unique('external_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendus_discount_card_imports');
    }
};
