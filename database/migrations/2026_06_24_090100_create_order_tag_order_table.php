<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_tag_order', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['order_id', 'order_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_tag_order');
    }
};
