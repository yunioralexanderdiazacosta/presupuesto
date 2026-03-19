<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Desactivar FK checks temporalmente
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('production_summaries', function (Blueprint $table) {
            $table->dropUnique('unique_production_summary');
        });

        Schema::table('production_summaries', function (Blueprint $table) {
            $table->unique(['variety_id', 'season_id', 'team_id'], 'unique_production_summary');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('production_summaries', function (Blueprint $table) {
            $table->dropUnique('unique_production_summary');
        });

        Schema::table('production_summaries', function (Blueprint $table) {
            $table->unique(['season_id', 'team_id'], 'unique_production_summary');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
