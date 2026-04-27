<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Journal d'audit : qui a fait quoi, quand, depuis où.
        Schema::create('pdp_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pdp_id')->constrained()->cascadeOnDelete();
            $table->string('actor');          // 'agency:42' | 'prestataire:<token-truncated>' | 'qse_admin:1'
            $table->string('action');         // 'created' | 'sent' | 'viewed' | 'submitted' | 'validated' | 'signed' | etc.
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');

            $table->index(['pdp_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdp_audit_logs');
    }
};
