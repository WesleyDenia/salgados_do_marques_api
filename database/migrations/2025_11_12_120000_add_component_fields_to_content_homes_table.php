<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_homes', function (Blueprint $table) {
            $table->string('component_name')->nullable()->after('layout');
            $table->json('component_props')->nullable()->after('component_name');
        });
    }

    public function down(): void
    {
        Schema::table('content_homes', function (Blueprint $table) {
            $table->dropColumn(['component_name', 'component_props']);
        });
    }
};
