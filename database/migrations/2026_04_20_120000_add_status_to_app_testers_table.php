<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_testers', function (Blueprint $table) {
            $table->string('status', 20)
                ->default('Registrado')
                ->after('operating_system');

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('app_testers', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn('status');
        });
    }
};
