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
            $table->timestamp('lgpd_consent_at')->nullable()->after('loyalty_synced_at');
            $table->string('lgpd_consent_version', 100)->nullable()->after('lgpd_consent_at');
            $table->string('lgpd_consent_hash', 64)->nullable()->after('lgpd_consent_version');
            $table->string('lgpd_consent_channel', 50)->nullable()->after('lgpd_consent_hash');
        });

        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('version', 100)->nullable();
            $table->string('hash', 64)->nullable();
            $table->text('content')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->string('channel', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_consents');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'lgpd_consent_at',
                'lgpd_consent_version',
                'lgpd_consent_hash',
                'lgpd_consent_channel',
            ]);
        });
    }
};
