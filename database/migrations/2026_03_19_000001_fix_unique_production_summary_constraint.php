<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Obtener TODOS los FK constraints de la tabla.
     */
    private function getAllForeignKeys(string $table): array
    {
        $fks = DB::select("
            SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$table]);

        return $fks;
    }

    public function up(): void
    {
        // 1. Descubrir y eliminar TODOS los FKs de la tabla
        $fks = $this->getAllForeignKeys('production_summaries');
        $fkNames = array_unique(array_map(fn($fk) => $fk->CONSTRAINT_NAME, $fks));

        if (!empty($fkNames)) {
            Schema::table('production_summaries', function (Blueprint $table) use ($fkNames) {
                foreach ($fkNames as $fk) {
                    $table->dropForeign($fk);
                }
            });
        }

        // 2. Eliminar el unique (ahora sin FKs que lo bloqueen)
        $hasUnique = collect(DB::select("SHOW INDEX FROM production_summaries WHERE Key_name = 'unique_production_summary'"))->isNotEmpty();
        if ($hasUnique) {
            Schema::table('production_summaries', function (Blueprint $table) {
                $table->dropUnique('unique_production_summary');
            });
        }

        // 3. Crear el unique correcto
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->unique(['variety_id', 'season_id', 'team_id'], 'unique_production_summary');
        });

        // 4. Re-crear TODOS los FKs
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->foreign('variety_id')->references('id')->on('varieties')->onDelete('cascade');
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        $fks = $this->getAllForeignKeys('production_summaries');
        $fkNames = array_unique(array_map(fn($fk) => $fk->CONSTRAINT_NAME, $fks));

        if (!empty($fkNames)) {
            Schema::table('production_summaries', function (Blueprint $table) use ($fkNames) {
                foreach ($fkNames as $fk) {
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
            $table->foreign('variety_id')->references('id')->on('varieties')->onDelete('cascade');
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });
    }
};
