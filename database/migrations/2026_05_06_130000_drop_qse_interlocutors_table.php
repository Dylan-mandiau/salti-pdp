<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Suppression de la table qse_interlocutors.
 * La feature CRUD "Interlocuteurs QSE" a été retirée :
 * elle n'avait pas été demandée et n'apparaît pas sur le PDP final.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('qse_interlocutors');
    }

    public function down(): void
    {
        // Pas de rollback : la feature et son modèle ont été supprimés.
    }
};
