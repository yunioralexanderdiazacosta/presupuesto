<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement('ALTER TABLE production_summaries DROP INDEX unique_production_summary');
        DB::statement('ALTER TABLE production_summaries ADD UNIQUE unique_production_summary (variety_id, season_id, team_id)');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement('ALTER TABLE production_summaries DROP INDEX unique_production_summary');
        DB::statement('ALTER TABLE production_summaries ADD UNIQUE unique_production_summary (season_id, team_id)');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
