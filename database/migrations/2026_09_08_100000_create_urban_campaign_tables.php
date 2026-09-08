<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 60);
            $table->string('code', 120)->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['type', 'active']);
        });

        Schema::create('questions', function (Blueprint $table): void {
            $table->id();
            $table->string('code_type', 60);
            $table->text('question');
            $table->unsignedTinyInteger('secret_number');
            $table->string('collection_label');
            $table->string('eyebrow')->nullable();
            $table->unsignedTinyInteger('reward_when_correct');
            $table->unsignedTinyInteger('reward_when_wrong');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['code_type', 'active', 'display_order']);
        });

        Schema::create('question_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('response');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['question_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_responses');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('qr_codes');
    }
};
