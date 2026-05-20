<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verificar si el esquema ya está en el estado final correcto:
        // production_id existe, season_id/team_id no existen, y unique es el nuevo (con production_id)
        $alreadyDone = Schema::hasColumn('production_summaries', 'production_id')
            && !Schema::hasColumn('production_summaries', 'season_id')
            && !Schema::hasColumn('production_summaries', 'team_id')
            && collect(DB::select(
                "SHOW INDEX FROM production_summaries WHERE Key_name = 'unique_production_summary' AND Column_name = 'production_id'"
            ))->isNotEmpty();

        if ($alreadyDone) {
            return; // Ya migrado correctamente, no hacer nada
        }

        // 1. Agregar production_id nullable (idempotente)
        if (!Schema::hasColumn('production_summaries', 'production_id')) {
            Schema::table('production_summaries', function (Blueprint $table) {
                $table->unsignedBigInteger('production_id')->nullable()->after('id');
            });
        }

        // 2. Migrar datos: solo los que aún no tienen production_id
        //    (si season_id ya no existe, este paso ya se ejecutó antes)
        if (Schema::hasColumn('production_summaries', 'season_id')) {
            $summaries = DB::table('production_summaries as ps')
                ->join('varieties as v', 'ps.variety_id', '=', 'v.id')
                ->whereNull('ps.production_id')
                ->select('ps.id', 'ps.season_id', 'ps.team_id', 'v.fruit_id')
                ->get();

            foreach ($summaries as $summary) {
                if (!$summary->fruit_id) continue;

                $productionId = DB::table('productions')->where([
                    'season_id' => $summary->season_id,
                    'team_id'   => $summary->team_id,
                    'fruit_id'  => $summary->fruit_id,
                ])->value('id');

                if (!$productionId) {
                    $productionId = DB::table('productions')->insertGetId([
                        'season_id'  => $summary->season_id,
                        'team_id'    => $summary->team_id,
                        'fruit_id'   => $summary->fruit_id,
                        'discount'   => 0,
                        'advance'    => 0,
                        'notes'      => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('production_summaries')
                    ->where('id', $summary->id)
                    ->update(['production_id' => $productionId]);
            }
        }

        // 3. Hacer production_id NOT NULL y agregar FK (idempotente)
        $col = DB::selectOne("SHOW COLUMNS FROM production_summaries WHERE Field = 'production_id'");
        if ($col && $col->Null === 'YES') {
            Schema::table('production_summaries', function (Blueprint $table) {
                $table->unsignedBigInteger('production_id')->nullable(false)->change();
            });
        }

        $fkProdExists = collect(DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'production_summaries'
              AND COLUMN_NAME = 'production_id'
              AND REFERENCED_TABLE_NAME = 'productions'
        "))->isNotEmpty();

        if (!$fkProdExists) {
            Schema::table('production_summaries', function (Blueprint $table) {
                $table->foreign('production_id')
                    ->references('id')
                    ->on('productions')
                    ->cascadeOnDelete();
            });
        }

        // 4. Soltar FKs de season_id y team_id (idempotente)
        $fks = DB::select("
            SELECT DISTINCT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'production_summaries'
              AND COLUMN_NAME IN ('season_id', 'team_id')
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        if (!empty($fks)) {
            Schema::table('production_summaries', function (Blueprint $table) use ($fks) {
                foreach ($fks as $fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                }
            });
        }

        // 5. Agregar índice simple en variety_id ANTES de borrar el unique compuesto
        //    (MySQL necesita que variety_id tenga su propio índice para la FK)
        $varietyIdxExists = collect(DB::select(
            "SHOW INDEX FROM production_summaries WHERE Key_name = 'ps_variety_id_tmp_idx'"
        ))->isNotEmpty();

        if (!$varietyIdxExists) {
            Schema::table('production_summaries', function (Blueprint $table) {
                $table->index('variety_id', 'ps_variety_id_tmp_idx');
            });
        }

        // 6. Soltar unique constraint antiguo (variety_id, season_id, team_id)
        $hasOldUnique = collect(DB::select(
            "SHOW INDEX FROM production_summaries WHERE Key_name = 'unique_production_summary'"
        ))->isNotEmpty();

        if ($hasOldUnique) {
            Schema::table('production_summaries', function (Blueprint $table) {
                $table->dropUnique('unique_production_summary');
            });
        }

        // 7. Soltar columnas season_id y team_id si aún existen
        $colsToDrop = array_filter(
            ['season_id', 'team_id'],
            fn($c) => Schema::hasColumn('production_summaries', $c)
        );

        if (!empty($colsToDrop)) {
            Schema::table('production_summaries', function (Blueprint $table) use ($colsToDrop) {
                $table->dropColumn(array_values($colsToDrop));
            });
        }

        // 8. Nuevo unique constraint (production_id, variety_id)
        $hasNewUnique = collect(DB::select(
            "SHOW INDEX FROM production_summaries WHERE Key_name = 'unique_production_summary'"
        ))->isNotEmpty();

        if (!$hasNewUnique) {
            Schema::table('production_summaries', function (Blueprint $table) {
                $table->unique(['production_id', 'variety_id'], 'unique_production_summary');
            });
        }

        // 9. Limpiar índice temporal de variety_id
        //    MySQL usa este índice para la FK de variety_id → hay que soltar la FK primero
        $varietyIdxStillExists = collect(DB::select(
            "SHOW INDEX FROM production_summaries WHERE Key_name = 'ps_variety_id_tmp_idx'"
        ))->isNotEmpty();

        if ($varietyIdxStillExists) {
            // Encontrar las FKs que usan variety_id
            $varietyFks = DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'production_summaries'
                  AND COLUMN_NAME = 'variety_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            // Soltar esas FKs temporalmente
            foreach ($varietyFks as $fk) {
                DB::statement("ALTER TABLE `production_summaries` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }

            // Ahora sí se puede soltar el índice temporal
            Schema::table('production_summaries', function (Blueprint $table) {
                $table->dropIndex('ps_variety_id_tmp_idx');
            });

            // Recrear la FK en variety_id
            if (!empty($varietyFks)) {
                Schema::table('production_summaries', function (Blueprint $table) {
                    $table->foreign('variety_id')->references('id')->on('varieties')->cascadeOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        // 1. Agregar de vuelta season_id y team_id (nullable)
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->dropUnique('unique_production_summary');
            $table->unsignedBigInteger('season_id')->nullable()->after('production_id');
            $table->unsignedBigInteger('team_id')->nullable()->after('season_id');
        });

        // 2. Restaurar datos desde productions
        $summaries = DB::table('production_summaries as ps')
            ->join('productions as p', 'ps.production_id', '=', 'p.id')
            ->select('ps.id', 'p.season_id', 'p.team_id')
            ->get();

        foreach ($summaries as $summary) {
            DB::table('production_summaries')
                ->where('id', $summary->id)
                ->update(['season_id' => $summary->season_id, 'team_id' => $summary->team_id]);
        }

        // 3. Hacer NOT NULL y agregar FKs
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->unsignedBigInteger('season_id')->nullable(false)->change();
            $table->unsignedBigInteger('team_id')->nullable(false)->change();
            $table->foreign('season_id')->references('id')->on('seasons')->cascadeOnDelete();
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
        });

        // 4. Soltar production_id FK y columna
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->dropForeign(['production_id']);
            $table->dropColumn('production_id');
        });

        // 5. Restaurar unique constraint original
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->unique(['variety_id', 'season_id', 'team_id'], 'unique_production_summary');
        });

        // 6. Soltar tabla productions
        Schema::dropIfExists('productions');
    }
};
