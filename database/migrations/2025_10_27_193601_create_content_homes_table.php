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
        Schema::create('content_homes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('display_order')->default(0)->index();
            $table->string('title')->nullable();
            $table->string('image_url')->nullable();
            $table->text('text_body')->nullable();
            $table->string('type')->default('default');
            $table->string('layout')->default('default');
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('background_color')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('publish_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_homes');
    }
};
