<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_preparation_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('operational_preparation_slot_id');
            $table->string('scheduled_slot', 50)->nullable();
            $table->date('scheduled_for_date');
            $table->unsignedInteger('batch_index');
            $table->unsignedInteger('batch_units');
            $table->unsignedInteger('preparation_time_seconds');
            $table->timestamps();

            $table->index(
                ['scheduled_for_date', 'scheduled_slot', 'operational_preparation_slot_id'],
                'order_preparation_allocations_load_idx'
            );
            $table->index(['order_id', 'order_item_id'], 'order_preparation_allocations_order_item_idx');
            $table->foreign(
                'operational_preparation_slot_id',
                'order_prep_slot_fk'
            )
                ->references('id')
                ->on('operational_preparation_slots')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_preparation_allocations');
    }
};
