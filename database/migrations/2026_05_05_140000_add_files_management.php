<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plan d'accès / circulation / zone d'attente : SPÉCIFIQUE à chaque agence
        Schema::table('users', function (Blueprint $table) {
            $table->string('access_plan_path')->nullable()->after('phone');
            $table->string('access_plan_filename')->nullable()->after('access_plan_path');
        });

        // Réglages globaux QSE : permis feu + convention de prêt de matériel
        // (key/value JSON simple, suffisant pour quelques dizaines de paramètres)
        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['access_plan_path', 'access_plan_filename']);
        });
        Schema::dropIfExists('app_settings');
    }
};
