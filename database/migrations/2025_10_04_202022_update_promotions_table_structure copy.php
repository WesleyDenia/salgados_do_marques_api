<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // 🔹 Renomeia 'description' para 'title'
            if (Schema::hasColumn('promotions', 'description')) {
                $table->renameColumn('description', 'title');
            }

            // 🔹 Adiciona novas colunas
            $table->text('body')->nullable()->after('title');
            $table->string('image')->nullable()->after('code');
            $table->decimal('discount_percent', 5, 2)->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // 🔹 Reverte as alterações
            if (Schema::hasColumn('promotions', 'title')) {
                $table->renameColumn('title', 'description');
            }

            $table->dropColumn(['body', 'image', 'discount_percent']);
        });
    }
};
