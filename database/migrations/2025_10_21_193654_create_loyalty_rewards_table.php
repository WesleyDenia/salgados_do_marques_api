<?php

// database/migrations/2025_10_21_000003_create_loyalty_rewards_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // ex.: "Vale 1 coxinha"
            $table->text('description')->nullable();
            $table->unsignedBigInteger('threshold'); // pontos necessários
            $table->decimal('value', 10, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->string('image_url')->nullable();
            $table->timestamps();
            $table->unique(['threshold']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('loyalty_rewards');
    }
};
