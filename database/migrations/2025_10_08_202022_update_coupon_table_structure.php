<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // 🔹 Renomeia 'description' para 'title'
            if (Schema::hasColumn('coupons', 'description')) {
                $table->renameColumn('description', 'body');
            }
            // 🔹 Adiciona novas colunas            
            $table->text('title')->nullable()->after('id');
            $table->string('image_url')->nullable()->after('code');            
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // 
        });
    }
};
