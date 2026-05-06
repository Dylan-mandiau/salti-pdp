<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute une colonne JSON 'habilitations' à pdp_intervenants pour permettre
 * plusieurs habilitations par salarié (ex: CACES R489 + B2V + SST en même temps).
 *
 * Format : [
 *   {"code": "R489-3", "label": "CACES R489 cat. 3 — Chariot frontal ≤ 6 T", "validity": "2027-06-15"},
 *   {"code": "B2V", "label": "B2 / B2V — Chargé de travaux BT", "validity": "2027-09-30"}
 * ]
 *
 * Les anciens champs habilitation (string) et habilitation_validity (date) sont
 * conservés pour rétrocompatibilité — ils représentent toujours l'habilitation
 * principale d'un salarié, et sont automatiquement listés en premier si
 * habilitations est null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pdp_intervenants', function (Blueprint $table) {
            $table->json('habilitations')->nullable()->after('habilitation_validity');
        });
    }

    public function down(): void
    {
        Schema::table('pdp_intervenants', function (Blueprint $table) {
            $table->dropColumn('habilitations');
        });
    }
};
