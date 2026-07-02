<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('order_items', 'parent_order_item_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->unsignedBigInteger('parent_order_item_id')
                    ->nullable()
                    ->after('order_id');
            });
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->index(
                'parent_order_item_id',
                'order_items_parent_order_item_id_idx',
            );

            $table->foreign(
                'parent_order_item_id',
                'order_items_parent_order_item_id_fk',
            )
                ->references('id')
                ->on('order_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('order_items', 'parent_order_item_id')) {
            return;
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign('order_items_parent_order_item_id_fk');
            $table->dropIndex('order_items_parent_order_item_id_idx');
            $table->dropColumn('parent_order_item_id');
        });
    }
};
