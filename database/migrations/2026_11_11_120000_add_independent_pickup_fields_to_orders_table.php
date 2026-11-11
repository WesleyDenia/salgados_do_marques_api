<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->date('pickup_date')->nullable()->after('scheduled_at')->index();
            $table->time('pickup_time')->nullable()->after('pickup_date');
            $table->dateTime('scheduled_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['pickup_date']);
            $table->dropColumn(['pickup_date', 'pickup_time']);
            // Existing indefinite pickups cannot be converted to a mandatory
            // timestamp without inventing operational data.
        });
    }
};
