<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_partial_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('parent_order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->foreignId('generated_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('requested_units');
            $table->dateTime('scheduled_at')->index();
            $table->string('status')->index();
            $table->text('notes')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['parent_order_id', 'status']);
            $table->index(['parent_order_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_partial_withdrawals');
    }
};
