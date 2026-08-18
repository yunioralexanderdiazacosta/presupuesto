<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->comment('Quien solicita el pago');
            $table->string('number', 20)->comment('Correlativo: SP-001');
            $table->date('date');
            $table->enum('character', ['normal', 'importante', 'urgente'])->default('normal');
            $table->text('concept_observations')->nullable();
            $table->string('file_path')->nullable()->comment('Ruta factura/imagen adjunta');
            $table->enum('status', ['pendiente', 'gestionada'])->default('pendiente');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'season_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
