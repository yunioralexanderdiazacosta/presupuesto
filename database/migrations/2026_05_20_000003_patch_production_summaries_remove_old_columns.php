<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Parche: completa la refactorización de production_summaries.
 * La migración 000002 falló al intentar soltar el unique antes que los FKs.
 * Este migration hace los pasos restantes en el orden correcto.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Soltar TODAS las FKs de la tabla (incluyendo variety_id y production_id)
        //    MySQL no permite eliminar un índice si una FK lo usa como base.
        $fks = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'production_summaries'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        if (!empty($fks)) {
            Schema::table('production_summaries', function (Blueprint $table) use ($fks) {
                foreach ($fks as $fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                }
            });
        }

        // 2. Soltar índices auxiliares que ya no son necesarios
        $idxToDrop = ['idx_ps_season_id', 'idx_ps_team_id'];
        foreach ($idxToDrop as $idx) {
            $exists = collect(DB::select("SHOW INDEX FROM production_summaries WHERE Key_name = ?", [$idx]))->isNotEmpty();
            if ($exists) {
                DB::statement("ALTER TABLE production_summaries DROP INDEX `{$idx}`");
            }
        }

        // 3. Soltar el unique constraint viejo (variety_id, season_id, team_id)
        $hasUnique = collect(DB::select(
            "SHOW INDEX FROM production_summaries WHERE Key_name = 'unique_production_summary'"
        ))->isNotEmpty();

        if ($hasUnique) {
            DB::statement("ALTER TABLE production_summaries DROP INDEX `unique_production_summary`");
        }

        // 4. Soltar columnas season_id y team_id
        $cols = collect(DB::select('SHOW COLUMNS FROM production_summaries'))->pluck('Field');

        Schema::table('production_summaries', function (Blueprint $table) use ($cols) {
            $toDrop = [];
            if ($cols->contains('season_id')) $toDrop[] = 'season_id';
            if ($cols->contains('team_id'))   $toDrop[] = 'team_id';
            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });

        // 5. Re-agregar FKs necesarias (production_id y variety_id)
        Schema::table('production_summaries', function (Blueprint $table) {
            // Verificar si ya existen antes de agregar
            $existingFks = collect(DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_summaries'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            "))->pluck('CONSTRAINT_NAME');

            if (!$existingFks->contains('production_summaries_production_id_foreign')) {
                $table->foreign('production_id')->references('id')->on('productions')->onDelete('cascade');
            }
            if (!$existingFks->contains('production_summaries_variety_id_foreign')) {
                $table->foreign('variety_id')->references('id')->on('varieties')->onDelete('cascade');
            }
        });

        // 6. Agregar nuevo unique (production_id, variety_id)
        $hasNewUnique = collect(DB::select(
            "SHOW INDEX FROM production_summaries WHERE Key_name = 'unique_production_summary'"
        ))->isNotEmpty();

        if (!$hasNewUnique) {
            Schema::table('production_summaries', function (Blueprint $table) {
                $table->unique(['production_id', 'variety_id'], 'unique_production_summary');
            });
        }
    }

    public function down(): void
    {
        // Revertir: agregar season_id y team_id de vuelta
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->dropUnique('unique_production_summary');
            $table->unsignedBigInteger('season_id')->nullable()->after('production_id');
            $table->unsignedBigInteger('team_id')->nullable()->after('season_id');
        });

        $summaries = DB::table('production_summaries as ps')
            ->join('productions as p', 'ps.production_id', '=', 'p.id')
            ->select('ps.id', 'p.season_id', 'p.team_id')
            ->get();

        foreach ($summaries as $summary) {
            DB::table('production_summaries')
                ->where('id', $summary->id)
                ->update(['season_id' => $summary->season_id, 'team_id' => $summary->team_id]);
        }

        Schema::table('production_summaries', function (Blueprint $table) {
            $table->unsignedBigInteger('season_id')->nullable(false)->change();
            $table->unsignedBigInteger('team_id')->nullable(false)->change();
            $table->foreign('season_id')->references('id')->on('seasons')->cascadeOnDelete();
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
        });

        Schema::table('production_summaries', function (Blueprint $table) {
            $table->unique(['variety_id', 'season_id', 'team_id'], 'unique_production_summary');
        });
    }
};
