<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_sync_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('operation')->index();
            $table->string('entity_type')->index();
            $table->unsignedBigInteger('entity_id')->index();
            $table->string('active_key')->nullable()->unique();
            $table->string('status')->default('queued')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('external_id')->nullable()->index();
            $table->string('external_code')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->string('last_error_code')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['operation', 'entity_type', 'entity_id', 'status'], 'erp_tasks_operation_entity_status_idx');
            $table->index(['status', 'queued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_sync_tasks');
    }
};
