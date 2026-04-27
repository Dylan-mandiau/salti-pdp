<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plan de Prévention. Toutes les données du formulaire sont stockées
        // dans la colonne JSON `data` pour rester souple si le PDF évolue.
        // Les champs structurés (statut, dates, agence) restent en colonnes
        // SQL pour l'indexation et les requêtes du dashboard.
        Schema::create('pdps', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // référence externe non-devinable

            $table->foreignId('agency_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('prestataire_id')->nullable()->constrained('prestataires')->nullOnDelete();

            // Mode de remplissage choisi à la création
            $table->enum('mode', ['presentiel', 'distance'])->default('distance');

            // Statut du workflow
            $table->enum('status', [
                'draft',                  // Brouillon (SALTI remplit)
                'awaiting_prestataire',   // Lien envoyé, en attente du prestataire
                'awaiting_validation',    // Prestataire a soumis, SALTI doit valider
                'corrections_requested',  // SALTI a demandé des corrections
                'awaiting_signatures',    // Validé, en attente des signatures
                'signed',                 // Signé par les deux parties
                'archived',               // PDF final archivé
                'cancelled',              // Annulé
            ])->default('draft');

            // Identité du donneur d'ordre SALTI (saisi par l'agent qui crée le PDP)
            $table->string('donneur_ordre_nom');

            // Sécurité prestataire (Option A par défaut, Option B activable)
            $table->boolean('require_otp')->default(false);

            // Lien magique
            $table->string('magic_token', 64)->nullable()->unique();
            $table->timestamp('magic_token_expires_at')->nullable();

            // Données structurées du formulaire (sérialisé)
            $table->json('data');

            // Métadonnées
            $table->timestamp('sent_to_prestataire_at')->nullable();
            $table->timestamp('submitted_by_prestataire_at')->nullable();
            $table->timestamp('validated_by_salti_at')->nullable();
            $table->timestamp('signed_by_salti_at')->nullable();
            $table->timestamp('signed_by_prestataire_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_reason')->nullable();

            // PDF final
            $table->string('final_pdf_path')->nullable();
            $table->string('final_pdf_sha256', 64)->nullable();

            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdps');
    }
};
