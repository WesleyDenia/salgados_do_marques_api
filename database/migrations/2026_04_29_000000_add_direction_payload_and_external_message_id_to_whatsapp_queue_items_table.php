<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_queue_items', function (Blueprint $table) {
            $table->string('direction')->default('outbound')->index()->after('type');
            $table->string('external_message_id')->nullable()->unique()->after('phone');
            $table->json('payload')->nullable()->after('last_error_code');
            $table->timestamp('received_at')->nullable()->after('sent_at');
        });

        DB::table('whatsapp_queue_items')->update([
            'direction' => 'outbound',
        ]);
    }

    public function down(): void
    {
        Schema::table('whatsapp_queue_items', function (Blueprint $table) {
            $table->dropUnique(['external_message_id']);
            $table->dropColumn([
                'direction',
                'external_message_id',
                'payload',
                'received_at',
            ]);
        });
    }
};
