<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Carnet d'adresses des prestataires (entreprises extérieures) par agence.
        Schema::create('prestataires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('users')->cascadeOnDelete();
            $table->string('raison_sociale');
            $table->string('responsable_nom')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'raison_sociale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestataires');
    }
};
