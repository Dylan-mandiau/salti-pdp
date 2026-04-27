<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Comptes SALTI : 1 ligne = 1 agence (ou le compte QSE central).
        // Le nom de l'agent qui rédige est saisi dans chaque PDP, pas ici.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');               // ex. "Agence Bordeaux" / "Service QSE"
            $table->string('email')->unique();    // ex. bordeaux@salti.fr / qse@salti.fr
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['agency', 'qse_admin'])->default('agency');
            $table->string('city')->nullable();   // ville de l'agence
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('require_otp_by_default')->default(false); // Option B activée par défaut pour cette agence
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
