<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_summaries', function (Blueprint $table) {
            // Primero eliminar el FK que depende del unique index
            $table->dropForeign('production_summaries_season_id_foreign');
            // Ahora sí eliminar el unique roto
            $table->dropUnique('unique_production_summary');
        });

        Schema::table('production_summaries', function (Blueprint $table) {
            // Recrear el unique con las 3 columnas correctas
            $table->unique(['variety_id', 'season_id', 'team_id'], 'unique_production_summary');
            // Recrear el FK de season_id
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->dropForeign(['season_id']);
            $table->dropUnique('unique_production_summary');
        });

        Schema::table('production_summaries', function (Blueprint $table) {
            $table->unique(['season_id', 'team_id'], 'unique_production_summary');
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade');
        });
    }
};
