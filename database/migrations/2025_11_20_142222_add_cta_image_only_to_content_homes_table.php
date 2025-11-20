<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('content_homes', function (Blueprint $table) {
            $table->boolean('cta_image_only')
                ->default(false)
                ->after('cta_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_homes', function (Blueprint $table) {
            $table->dropColumn('cta_image_only');
        });
    }
};
