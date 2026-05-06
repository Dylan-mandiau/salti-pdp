<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Liste des interlocuteurs sécurité QSE affichés sur la page 1 du PDP.
        // Éditable par le QSE central via /admin/interlocutors.
        Schema::create('qse_interlocutors', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // Lydie Bernard
            $table->string('role');           // Coordinatrice QSE
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_main')->default(false); // affiché en gras
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qse_interlocutors');
    }
};
