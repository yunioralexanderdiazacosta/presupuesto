<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('application_orders', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('mojamiento', 10, 2)->comment('Litros de agua/líquido a usar');
            $table->string('recomendado')->comment('Nombre de la persona que recomendó');
            $table->text('aplicadores')->comment('Nombres de los aplicadores');
            $table->enum('status', ['pendiente', 'en_proceso', 'completada', 'cancelada'])->default('pendiente');
            $table->string('responsable')->comment('Responsable/supervisor de la orden');
            $table->text('observations')->nullable()->comment('Observaciones generales');
            
            // Filtrado por equipo y temporada (CRÍTICO)
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('season_id')->constrained('seasons')->onDelete('cascade');
            
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['team_id', 'season_id']);
            $table->index('date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_orders');
    }
};
