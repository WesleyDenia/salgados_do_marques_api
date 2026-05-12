<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('customer_name')->nullable()->after('user_id');
            $table->string('customer_contact')->nullable()->after('customer_name');
            $table->string('payment_status')->nullable()->after('status');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['customer_name', 'customer_contact', 'payment_status']);
        });

        if (DB::table('orders')->whereNull('user_id')->exists()) {
            throw new RuntimeException('Cannot rollback orders.user_id to non-nullable while null customer orders exist.');
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
