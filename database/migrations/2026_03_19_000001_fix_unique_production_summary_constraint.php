<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear índices simples para que los FKs de season_id y team_id
        //    no dependan del índice unique compuesto
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->index('season_id', 'idx_ps_season_id');
            $table->index('team_id', 'idx_ps_team_id');
        });

        // 2. Ahora podemos eliminar el unique sin conflicto de FK
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->dropUnique('unique_production_summary');
        });

        // 3. Crear el unique correcto con variety_id incluido
        //    Los índices simples se mantienen porque los FKs los necesitan
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->unique(['variety_id', 'season_id', 'team_id'], 'unique_production_summary');
        });
    }

    public function down(): void
    {
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->dropUnique('unique_production_summary');
        });

        Schema::table('production_summaries', function (Blueprint $table) {
            $table->unique(['season_id', 'team_id'], 'unique_production_summary');
        });

        Schema::table('production_summaries', function (Blueprint $table) {
            $table->dropIndex('idx_ps_season_id');
            $table->dropIndex('idx_ps_team_id');
        });
    }
};
