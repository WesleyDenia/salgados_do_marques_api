<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('city');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('phone')->nullable();
            $table->enum('type', ['principal', 'revenda'])->default('principal');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['name', 'city']);
            $table->index(['city', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
