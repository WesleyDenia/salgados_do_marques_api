<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_preparation_settings')) {
            if (DB::table('product_preparation_settings')->exists()) {
                throw new RuntimeException('product_preparation_settings already exists with data; refusing to drop it.');
            }

            // Recovery path for a failed production migration that left the empty table behind.
            Schema::drop('product_preparation_settings');
        }

        Schema::create('product_preparation_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operational_preparation_slot_id');
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedInteger('batch_size')->default(25);
            $table->unsignedInteger('preparation_time_seconds');
            $table->timestamps();

            $table->unique(
                ['operational_preparation_slot_id', 'product_id'],
                'product_preparation_slot_product_unique'
            );
            $table->index(['product_id', 'operational_preparation_slot_id'], 'product_preparation_product_slot_idx');
            $table->foreign(
                'operational_preparation_slot_id',
                'product_prep_slot_fk'
            )
                ->references('id')
                ->on('operational_preparation_slots')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_preparation_settings');
    }
};
