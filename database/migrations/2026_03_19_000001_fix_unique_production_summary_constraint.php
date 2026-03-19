<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Eliminar FK de season_id (depende del unique index)
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->dropForeign('production_summaries_season_id_foreign');
        });

        // 2. Eliminar el unique incorrecto (season_id, team_id)
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->dropUnique('unique_production_summary');
        });

        // 3. Crear el unique correcto (variety_id, season_id, team_id)
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->unique(['variety_id', 'season_id', 'team_id'], 'unique_production_summary');
        });

        // 4. Re-crear FK de season_id
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->dropForeign('production_summaries_season_id_foreign');
        });

        Schema::table('production_summaries', function (Blueprint $table) {
            $table->dropUnique('unique_production_summary');
        });

        Schema::table('production_summaries', function (Blueprint $table) {
            $table->unique(['season_id', 'team_id'], 'unique_production_summary');
        });

        Schema::table('production_summaries', function (Blueprint $table) {
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade');
        });
    }
};
