<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_queue_items', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('entity_type')->nullable()->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->string('recipient_name')->nullable();
            $table->string('phone')->index();
            $table->text('message');
            $table->string('status')->default('queued')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->string('last_error_code')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('manual_note')->nullable();
            $table->timestamp('manually_closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'status', 'queued_at'], 'whatsapp_queue_type_status_idx');
            $table->index(['status', 'queued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_queue_items');
    }
};
