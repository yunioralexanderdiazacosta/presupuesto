<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_summaries', function (Blueprint $table) {
            // Primero FK, luego unique, luego columna
            $table->dropForeign(['cost_center_variety_id']);
            $table->dropUnique('unique_production_summary');
            $table->dropColumn('cost_center_variety_id');

            // Agregar variety_id
            $table->foreignId('variety_id')->after('id')->constrained('varieties')->onDelete('cascade');
            $table->unique(['variety_id', 'season_id', 'team_id'], 'unique_production_summary');
        });
    }

    public function down(): void
    {
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->dropUnique('unique_production_summary');
            $table->dropForeign(['variety_id']);
            $table->dropColumn('variety_id');

            $table->foreignId('cost_center_variety_id')->constrained('cost_center_varieties')->onDelete('cascade');
            $table->unique(['cost_center_variety_id', 'season_id', 'team_id'], 'unique_production_summary');
        });
    }
};
