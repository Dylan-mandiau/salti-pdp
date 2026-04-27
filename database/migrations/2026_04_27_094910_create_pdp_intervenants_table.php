<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Salariés intervenants côté EE (entreprise extérieure).
        // Chacun signe l'attestation de prise de connaissance (page 6 du PDF).
        Schema::create('pdp_intervenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pdp_id')->constrained()->cascadeOnDelete();
            $table->string('nom_prenom');
            $table->date('date_signature')->nullable();
            $table->longText('signature_data')->nullable(); // PNG dataURL
            $table->timestamps();

            // Habilitations / CACES (page 5 du PDF) - regroupées dans le même intervenant
            $table->string('habilitation')->nullable();
            $table->date('habilitation_validity')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdp_intervenants');
    }
};
