<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('erp_sync_status')->default('pending')->after('external_id');
            $table->unsignedInteger('erp_sync_attempts')->default(0)->after('erp_sync_status');
            $table->text('erp_sync_error')->nullable()->after('erp_sync_attempts');
            $table->timestamp('erp_sync_attempted_at')->nullable()->after('erp_sync_error');
            $table->timestamp('erp_synced_at')->nullable()->after('erp_sync_attempted_at');
        });

        DB::table('users')
            ->whereNotNull('external_id')
            ->update([
                'erp_sync_status' => 'synced',
                'erp_synced_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'erp_sync_status',
                'erp_sync_attempts',
                'erp_sync_error',
                'erp_sync_attempted_at',
                'erp_synced_at',
            ]);
        });
    }
};
