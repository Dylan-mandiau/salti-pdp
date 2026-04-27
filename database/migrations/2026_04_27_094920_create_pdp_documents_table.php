<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pièces jointes : CACES, habilitations, permis feu, etc.
        Schema::create('pdp_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pdp_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'caces',
                'autorisation_conduite',
                'habilitation',
                'permis_feu',
                'fds',                // Fiche de Données de Sécurité
                'plan_acces',
                'convention_pret',
                'autre',
            ]);
            $table->string('label')->nullable();
            $table->string('path');                  // chemin sur le disque
            $table->string('original_filename');
            $table->string('mime_type', 64);
            $table->unsignedInteger('size');
            $table->string('uploaded_by');           // 'salti' | 'prestataire'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdp_documents');
    }
};
