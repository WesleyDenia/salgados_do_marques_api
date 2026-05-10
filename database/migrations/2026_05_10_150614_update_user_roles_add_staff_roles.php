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
        Schema::table('users', function (Blueprint $table) {
            // Change from enum to string to support more roles flexibly
            // Existing roles: admin, cliente, revendedor
            // New staff roles: operacional, atendimento
            $table->string('role')->default('cliente')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Reverting to enum might lose data if new roles are present, 
            // but for a rollback we restore the original constraint.
            $table->enum('role', ['admin', 'cliente', 'revendedor'])->default('cliente')->change();
        });
    }
};
