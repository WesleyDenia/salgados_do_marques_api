<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_components', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('home_components')->insert([
            [
                'key' => 'WelcomeBonusButton',
                'label' => 'Botão Bônus de boas-vindas',
                'description' => 'CTA de ativação do benefício inicial do utilizador.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'CouponsCarousel',
                'label' => 'Carrossel de cupons',
                'description' => 'Lista horizontal de cupons disponíveis para ativação.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'PartnersCarousel',
                'label' => 'Carrossel de parceiros',
                'description' => 'Lista horizontal de parceiros com acesso rápido ao detalhe.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('home_components');
    }
};
