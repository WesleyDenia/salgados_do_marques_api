<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_partial_withdrawals', function (Blueprint $table) {
            $table->json('flavor_ids')->nullable()->after('requested_units');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('order_partial_withdrawals', 'flavor_ids')) {
            return;
        }

        Schema::table('order_partial_withdrawals', function (Blueprint $table) {
            $table->dropColumn('flavor_ids');
        });
    }
};
