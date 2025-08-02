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
        Schema::create('groupings', function (Blueprint $table) {
            $table->id();
            // Nombre del agrupamiento
            $table->string('name');
            // Llave foránea a centros de costo
            $table->foreignId('costcenter_id')
                  ->constrained('cost_centers')
                  ->cascadeOnDelete();
            // Llave foránea a temporadas
            $table->foreignId('season_id')
                  ->constrained()
                  ->cascadeOnDelete();
            // Llave foránea a equipos
            $table->foreignId('team_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groupings');
    }
};
