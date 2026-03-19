<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buscar nombres de FK que referencien una columna específica.
     */
    private function getForeignKeyNames(string $table, string $column): array
    {
        $fks = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$table, $column]);

        return array_map(fn($fk) => $fk->CONSTRAINT_NAME, $fks);
    }

    public function up(): void
    {
        // 1. Eliminar TODOS los FKs que dependen del unique index
        //    (season_id y potencialmente team_id)
        $seasonFks = $this->getForeignKeyNames('production_summaries', 'season_id');
        $teamFks = $this->getForeignKeyNames('production_summaries', 'team_id');

        if (!empty($seasonFks)) {
            Schema::table('production_summaries', function (Blueprint $table) use ($seasonFks) {
                foreach ($seasonFks as $fk) {
                    $table->dropForeign($fk);
                }
            });
        }

        if (!empty($teamFks)) {
            Schema::table('production_summaries', function (Blueprint $table) use ($teamFks) {
                foreach ($teamFks as $fk) {
                    $table->dropForeign($fk);
                }
            });
        }

        // 2. Eliminar el unique incorrecto (season_id, team_id)
        $hasUnique = collect(DB::select("SHOW INDEX FROM production_summaries WHERE Key_name = 'unique_production_summary'"))->isNotEmpty();
        if ($hasUnique) {
            Schema::table('production_summaries', function (Blueprint $table) {
                $table->dropUnique('unique_production_summary');
            });
        }

        // 3. Crear el unique correcto (variety_id, season_id, team_id)
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->unique(['variety_id', 'season_id', 'team_id'], 'unique_production_summary');
        });

        // 4. Re-crear FKs
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        $seasonFks = $this->getForeignKeyNames('production_summaries', 'season_id');
        $teamFks = $this->getForeignKeyNames('production_summaries', 'team_id');

        if (!empty($seasonFks)) {
            Schema::table('production_summaries', function (Blueprint $table) use ($seasonFks) {
                foreach ($seasonFks as $fk) {
                    $table->dropForeign($fk);
                }
            });
        }

        if (!empty($teamFks)) {
            Schema::table('production_summaries', function (Blueprint $table) use ($teamFks) {
                foreach ($teamFks as $fk) {
                    $table->dropForeign($fk);
                }
            });
        }

        Schema::table('production_summaries', function (Blueprint $table) {
            $table->dropUnique('unique_production_summary');
        });

        Schema::table('production_summaries', function (Blueprint $table) {
            $table->unique(['season_id', 'team_id'], 'unique_production_summary');
        });

        Schema::table('production_summaries', function (Blueprint $table) {
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });
    }
};
