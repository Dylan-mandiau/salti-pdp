<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute le flag is_representant sur pdp_intervenants.
 *
 * Quand un salarié EE est aussi le représentant légal de l'entreprise extérieure,
 * cocher 'is_representant' évite qu'il signe deux fois (1× son attestation +
 * 1× la signature du représentant). Sa signature d'attestation servira aussi
 * comme signature_ee finale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pdp_intervenants', function (Blueprint $table) {
            $table->boolean('is_representant')->default(false)->after('habilitations');
        });
    }

    public function down(): void
    {
        Schema::table('pdp_intervenants', function (Blueprint $table) {
            $table->dropColumn('is_representant');
        });
    }
};
