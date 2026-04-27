<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('settings')->where('key', 'WHATSAPP_ORDER_TO')->exists()) {
            return;
        }

        DB::table('settings')->insert([
            'key' => 'WHATSAPP_ORDER_TO',
            'value' => '',
            'type' => 'string',
            'editable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'WHATSAPP_ORDER_TO')->delete();
    }
};
