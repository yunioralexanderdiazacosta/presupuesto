<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // 1. Crear índices simples en team_id para que MySQL pueda soltar los unique
        //    (la FK team_id_foreign necesita un índice que la respalde)
        // =====================================================================
        if (!$this->indexExists('rnp_prices', 'rnp_prices_team_id_index')) {
            Schema::table('rnp_prices', function (Blueprint $table) {
                $table->index('team_id', 'rnp_prices_team_id_index');
            });
        }
        if (!$this->indexExists('variety_cost_params', 'variety_cost_params_team_id_index')) {
            Schema::table('variety_cost_params', function (Blueprint $table) {
                $table->index('team_id', 'variety_cost_params_team_id_index');
            });
        }
        if (!$this->indexExists('kg_yield_costs', 'kg_yield_costs_team_id_index')) {
            Schema::table('kg_yield_costs', function (Blueprint $table) {
                $table->index('team_id', 'kg_yield_costs_team_id_index');
            });
        }

        // =====================================================================
        // 2. ELIMINAR unique viejos (si existen) — idempotente ante fallo parcial
        // =====================================================================
        $this->dropUniqueIfExists('rnp_prices', 'rnp_prices_team_id_variety_id_week_unique');
        $this->dropUniqueIfExists('variety_cost_params', 'variety_cost_params_team_id_variety_id_unique');
        $this->dropUniqueIfExists('kg_yield_costs', 'kg_yield_costs_team_id_kg_ha_unique');

        // =====================================================================
        // 2. Agregar columna (si no existe) — idempotente ante fallo parcial
        // =====================================================================
        if (!Schema::hasColumn('rnp_prices', 'project_evaluation_id')) {
            Schema::table('rnp_prices', function (Blueprint $table) {
                $table->foreignId('project_evaluation_id')->nullable()->after('team_id')
                    ->constrained('project_evaluations')->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('variety_cost_params', 'project_evaluation_id')) {
            Schema::table('variety_cost_params', function (Blueprint $table) {
                $table->foreignId('project_evaluation_id')->nullable()->after('team_id')
                    ->constrained('project_evaluations')->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('kg_yield_costs', 'project_evaluation_id')) {
            Schema::table('kg_yield_costs', function (Blueprint $table) {
                $table->foreignId('project_evaluation_id')->nullable()->after('team_id')
                    ->constrained('project_evaluations')->cascadeOnDelete();
            });
        }

        // =====================================================================
        // 2. Limpiar datos viejos (usuario los reingresará por evaluación)
        // =====================================================================
        DB::table('rnp_prices')->truncate();
        DB::table('variety_cost_params')->truncate();
        DB::table('kg_yield_costs')->truncate();

        // =====================================================================
        // 3. Crear nuevos unique constraints y hacer NOT NULL
        // =====================================================================
        Schema::table('rnp_prices', function (Blueprint $table) {
            $table->unique(['project_evaluation_id', 'variety_id', 'week']);
        });

        Schema::table('variety_cost_params', function (Blueprint $table) {
            $table->unique(['project_evaluation_id', 'variety_id']);
        });

        Schema::table('kg_yield_costs', function (Blueprint $table) {
            $table->unique(['project_evaluation_id', 'kg_ha']);
        });

        // Hacer NOT NULL ahora que todos los registros tienen valor
        Schema::table('rnp_prices', function (Blueprint $table) {
            $table->unsignedBigInteger('project_evaluation_id')->nullable(false)->change();
        });
        Schema::table('variety_cost_params', function (Blueprint $table) {
            $table->unsignedBigInteger('project_evaluation_id')->nullable(false)->change();
        });
        Schema::table('kg_yield_costs', function (Blueprint $table) {
            $table->unsignedBigInteger('project_evaluation_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        // Revertir constraints
        Schema::table('rnp_prices', function (Blueprint $table) {
            $table->dropUnique(['project_evaluation_id', 'variety_id', 'week']);
            $table->dropForeign(['project_evaluation_id']);
            $table->dropColumn('project_evaluation_id');
            $table->unique(['team_id', 'variety_id', 'week']);
        });

        Schema::table('variety_cost_params', function (Blueprint $table) {
            $table->dropUnique(['project_evaluation_id', 'variety_id']);
            $table->dropForeign(['project_evaluation_id']);
            $table->dropColumn('project_evaluation_id');
            $table->unique(['team_id', 'variety_id']);
        });

        Schema::table('kg_yield_costs', function (Blueprint $table) {
            $table->dropUnique(['project_evaluation_id', 'kg_ha']);
            $table->dropForeign(['project_evaluation_id']);
            $table->dropColumn('project_evaluation_id');
            $table->unique(['team_id', 'kg_ha']);
        });
    }

    /**
     * Drop a unique index only if it exists.
     */
    private function dropUniqueIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                $blueprint->dropUnique($indexName);
            });
        }
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        return count(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName])) > 0;
    }
};
