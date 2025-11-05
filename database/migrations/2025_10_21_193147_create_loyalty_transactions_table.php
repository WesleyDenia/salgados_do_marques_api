<?php

// database/migrations/2025_10_21_000002_create_loyalty_transactions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['earn','redeem','adjust']);
            $table->integer('points'); // + ou -, validaremos via type
            $table->string('reason')->nullable();   // ex.: "Compra #1234", "Cupom X"
            $table->json('meta')->nullable();       // payload livre
            $table->timestamps();
            $table->index(['user_id','created_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('loyalty_transactions');
    }
};
