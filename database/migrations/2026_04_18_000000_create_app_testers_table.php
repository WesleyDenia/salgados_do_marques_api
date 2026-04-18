<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_testers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('operating_system', 20);
            $table->boolean('is_android_eligible')->default(false);
            $table->timestamp('consent_at');
            $table->string('source_path')->nullable();
            $table->timestamp('invite_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['operating_system', 'created_at']);
            $table->index(['is_android_eligible', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_testers');
    }
};
