<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_summaries', function (Blueprint $table) {
            // Limpiar cost_center_variety_id si aún existe
            if (Schema::hasColumn('production_summaries', 'cost_center_variety_id')) {
                // Intentar soltar FK si existe
                try {
                    $table->dropForeign(['cost_center_variety_id']);
                } catch (\Exception $e) {}
                $table->dropColumn('cost_center_variety_id');
            }

            // Asegurar unique constraint si no existe
            if (!collect(\DB::select("SHOW INDEX FROM production_summaries WHERE Key_name = 'unique_production_summary'"))->count()) {
                $table->unique(['variety_id', 'season_id', 'team_id'], 'unique_production_summary');
            }
        });
    }

    public function down(): void
    {
        //
    }
};
